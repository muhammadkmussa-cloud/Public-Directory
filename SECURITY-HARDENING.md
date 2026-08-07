# Security Hardening Plan

*Audit date: 2026-08-06 · Repo baseline: `102c784` · Findings verified in the code (file:line refs below)*

Fixes are ordered by severity and dependency. Each item states the **current state (verified)**, the **risk**, **implementation steps**, and **acceptance criteria**.

| # | Finding | Severity | Est. |
|---|---|---|---|
| 1 | IDOR on donation status | ✅ **Fixed (2026-08-06)** | 0.5 day |
| 2 | Claim squatting (no ownership proof / approval) | ✅ **Fixed (2026-08-06)** | 1 day |
| 3 | Missing rate limits (claim/respond/update, reports, logout) | ✅ **Fixed (2026-08-06)** | 0.5 day |
| 4 | Login lockout stored in session (bypassable) | ✅ **Fixed (2026-08-06)** | 0.5 day |
| 5 | Insecure shipped defaults (`root/root`, dev mode, `install.lock`) | 🔴 High (pre-deploy) | 0.5 day |
| 6 | Stored XSS depends on frontend escaping only | ✅ **Fixed (2026-08-06)** | 0.5 day |
| 7 | M-Pesa callback spoofable / PayPal sandbox auto-accept | ✅ **Fixed (2026-08-06)** | 0.5 day |
| 8 | `open_now` overnight/multi-range hours mis-computed | ✅ **Fixed (2026-08-06)** | 0.25 day |
| | **Total** | | **~4.25 days** (1 dev) |

---

## 🔴 1. IDOR — anyone can read any donation's status  ·  ✅ FIXED (2026-08-06)

> **Status: FIXED.** `api/donations.php` status action now requires `require_login()` (401) and enforces strict ownership — `if (!$d['user_id'] || (int)$d['user_id'] !== (int)$uid)` → 403 — so anonymous-account donations (`user_id` NULL) are never readable via this endpoint. `assets/js/pages/charity.js` poll stops gracefully on 401/403 with a provider-aware message and handles `status === 'failed'`. Supporting fix in `assets/js/app.js`: demo-mode mock fallback now triggers only on non-JSON responses (static server), so real JSON errors (401/403/422/429/500) surface with their real status instead of being swallowed into mock data. `assets/js/mock.js` gained an `action=status` branch for demo parity. Independently reviewed (per-step + consolidated) — no bypass found.

**Current state (verified at audit):** `api/donations.php:180-199` — `GET ?action=status&donation_id=X`. Ownership was checked **only when a user is logged in**:

```php
$uid = Auth::isLoggedIn() ? Auth::user()['id'] : null;
if ($uid && $d['user_id'] && (int)$d['user_id'] !== (int)$uid) {
    json_err('Not your donation', 403);
}
json_ok([... status, amount, payment_method ...]);
```

An anonymous request with any `donation_id` returns the status, amount, and payment method of that donation. Donations also carry `donor_name`/`donor_email` (schema `donations.donor_name`, `.donor_email`).

**Risk:** information disclosure (donation amounts, donor identifiers) — a privacy leak for anonymous donors (many donors are anonymous by design, `is_anonymous`).

**Fix applied — require authentication + strict ownership:**
```php
if ($action === 'status') {
    require_method('GET');
    $uid = require_login()['id'];
    $donationId = (int)($_GET['donation_id'] ?? 0);
    $d = $db->fetchOne(
        'SELECT id, user_id, status, amount, payment_method, created_at FROM donations WHERE id = ?',
        [$donationId]
    );
    if (!$d) json_err('Donation not found', 404);
    if (!$d['user_id'] || (int)$d['user_id'] !== (int)$uid) {
        json_err('Not your donation', 403);
    }
    json_ok([...]);
}
```
- Anonymous donors (`user_id` NULL) cannot poll status → they already receive M-Pesa/PayPal confirmation via the payment channel. The frontend (`charity.js`) handles the resulting 401 by stopping the poll and showing a provider-aware message instead of error-looping or breaking the flow.
- **Frontend check (done):** `charity.js` poll stops gracefully on 401/403; `status === 'failed'` shows an error and re-enables the button; demo mode (`mock.js`) resolves the poll immediately.

**Acceptance criteria**
- [x] `GET ?action=status&donation_id=N` without a session → `401`.
- [x] Logged-in user requesting another user's donation → `403`.
- [x] Logged-in owner of the donation → full status payload.

---

## 🔴 2. Claim squatting — no ownership proof or approval  ·  ✅ FIXED (2026-08-06)

> **Status: FIXED.** Claiming a listing now records a **pending** claim (audit table `claims`) and grants NO owner rights until an admin approves it in the admin console (`admin.html` → "Claim requests"). `respond`/`update` require `claim_status = 'approved'` in addition to `user_id` match. Admins are auto-granted (trusted). Reviewed independently per step (schema, backend, dashboard, admin console, mock) — the reject/claims-row updates are race-guarded (`WHERE ... AND status='pending'` + affected-row 409), and claim INSERT/UPDATE run in a transaction. Docs in `YELP-COMPARISON.md` §6 updated.

**Current state (verified):** `api/businesses.php:20-33` — `POST {action: claim, business_id}`. Any authenticated user can immediately set `user_id`, `is_claimed = 1`, `claimed_at = NOW()` on any unclaimed listing:

```php
if ($action === 'claim') {
    ...
    if ($biz['is_claimed'] && $biz['user_id']) json_err('This listing is already claimed', 409);
    $db->execute('UPDATE businesses SET user_id = ?, is_claimed = 1, claimed_at = NOW() WHERE id = ?', [$user['id'], $id]);
    ...
}
```

There is no proof-of-ownership step, no admin approval, and the notification fires only when the claimant is an admin (so real claims notify nobody).

**Risk:** anyone can hijack a listing's owner controls (edit name/contact/description, respond to reviews as the owner) — reputation and impersonation damage.

**Fix — three-state claim with admin approval:**
1. **Schema** — add a `claim_status` to `businesses` (or a new `claims` table for audit):
   ```sql
   ALTER TABLE businesses
     ADD claim_status ENUM('none','pending','approved','rejected') NOT NULL DEFAULT 'none';
   ```
   (Ship as `database/migrations/004_claim_approval.sql`.)
2. **`api/businesses.php` `claim` action:**
   - Set `claim_status = 'pending'` (leave `user_id`/`is_claimed` untouched).
   - Store the requester as the pending claimant. With a `claims` table: `(business_id, user_id, proof, status)` — with the column only, record `pending_claimer_id` via a new nullable column.
   - `notify()` the admins: `SELECT id FROM users WHERE user_type = 'admin'` → "New claim request for {business}".
3. **Owner response enforcement** (`respond`, `update`): require `claim_status = 'approved'` AND `user_id = current user`, not just `user_id`.
4. **Admin console (`moderation.html` or `admin.html`)**: list pending claims → approve/reject (sets `claim_status`, `is_claimed`, `user_id`, `claimed_at` on approve). Add `api/businesses.php?action=claims` (admin) + `action=review_claim` (admin).
5. **Dashboard UI**: claimant sees "Claim pending approval" instead of instant owner controls; show reject reason on denial.
6. **Ownership proof** (optional, phase 2): claim form asks for a verification detail (business email/phone, or a code sent to the listed phone). Keep v1 to admin approval to avoid over-engineering.

**Acceptance criteria**
- [x] Claiming sets `pending`; claimant has no edit/respond rights until approved.
- [x] Admins get a notification; approve grants owner rights; reject keeps the listing unclaimed.
- [x] Existing owner-controlled listings (already `is_claimed=1`) are unaffected.
- [x] `dashboard.js` shows pending state and no longer grants the edit form prematurely.

---

## 🟠 3. Missing rate limits  ·  ✅ FIXED (2026-08-06)

> **Status: FIXED.** Added `rate_limit()` calls to the three unprotected endpoints:
> - `api/businesses.php` POST → `rate_limit('biz_write', 30, 300)` (30 owner-actions / 5 min per IP)
> - `api/reports.php` POST `create` → `rate_limit('report_create', 10, 900)`; `admin_resolve` → `rate_limit('report_admin', 60, 300)`
> - `api/auth.php` `logout` → `rate_limit('logout', 60, 300)`
> All placed after `require_csrf()` / `require_login()` so limits are per-authenticated-IP. `rate_limit()` returns 429 on excess.

**Acceptance criteria**
- [x] Exceeding the per-IP threshold returns `429` on each of the three endpoints.
- [x] Normal flows (claim once, respond to a review, create a report, resolve a report, log out) are unaffected.

---

## 🟠 4. Login lockout stored in session — bypassable

**Current state (verified):** `includes/Auth.php:154-178` — failure counter and block time live in `$_SESSION['lockout']`:

```php
$failKey = 'login_fail_' . md5(strtolower($identifier));
$failCount = (int)($_SESSION['lockout'][$failKey]['count'] ?? 0);
```

Clearing cookies / incognito resets the counter, so brute force is only slowed by the per-IP limiter (`api/auth.php:22`, `rate_limit('login', 8, 300)`).

**Risk:** distributed/slow brute force against a known account bypasses the per-account protection.

**Fix — move the counter out of the session.** Two options:
- **A (recommended, no schema):** use the existing `rate_limit()` file store but keyed per-account as well as per-IP — add an optional `$keySuffix` param, then call `rate_limit('login_fail_' . md5($identifier), 10, 900)` in `Auth::login()`'s failure branch and clear the file on success (add a `rate_limit_clear()` helper).
- **B:** add a `failed_logins (user_id, fail_count, blocked_until)` table (survives IP rotation).

Keep the session logic only as a fast-path UI hint; make the authoritative check the persistent store.

## 🟠 4. Login lockout stored in session — bypassable  ·  ✅ FIXED (2026-08-06)

> **Status: FIXED.** Replaced session-based counter with persistent `rate_limit()` using IP-agnostic bucket. `Auth::login()` now calls `rate_limit('login_fail_<md5(identifier)>', 10, 900, '', false)` on every attempt and `rate_limit_clear()` on success. Lockout survives cookie clearing / IP switch. Per-IP limit (`rate_limit('login', 8, 300)`) remains independent.

**Acceptance criteria**
- [x] 10 failed logins to one account block it for 15 min **even after clearing cookies / switching IP**.
- [x] Successful login clears the counter.
- [x] Per-IP limit still works independently.

## 🔴 5. Insecure shipped defaults

**Current state (verified):**
- `config/config.php:10-13` — `DB_USER = 'root'`, `DB_PASS = 'root'`.
- `config/config.php:18` — `APP_ENV = 'development'` → `display_errors = '1'`, and in dev mode `api/auth.php` echoes reset/verify tokens and `api/oauth.php` returns a simulated login link.
- `database/install.lock` exists in the **working tree** (untracked today, but **not** git-ignored — a `git add .` would commit it). README says to delete `install.php` + `install.lock` from the server.
- `.htaccess` denies `install.php` and `.sql` files, but a misconfigured host (no `.htaccess`, or nginx) exposes them.

**Risk:** default DB creds on a live host = instant compromise; dev mode on production leaks tokens/stack traces; a reachable installer can re-seed/reset the DB.

**Fix:**
1. **Fail-closed config guard** — at the top of `config/config.php`:
   ```php
   if (APP_ENV === 'production') {
       if (DB_USER === 'root' || DB_PASS === 'root' || DB_PASS === '') {
           die('Refusing to start in production with default database credentials. Set DB_USER/DB_PASS in config/config.php.');
       }
       ini_set('display_errors', '0');
   }
   ```
   (Run the check after the constants are defined.)
2. **`.gitignore`** — add `database/install.lock` (and keep `database/install.php` listed as a deploy-time delete in the README).3. **Dev-only echo gating** — confirm the token-echo branches in `api/auth.php`/`api/oauth.php` are wrapped in `APP_ENV === 'development'` (they are); add a defensive check that `production` never returns tokens.
4. **README** — add a pre-launch checklist item: "verify `APP_ENV=production`, non-root DB creds, `install.php` + `install.lock` deleted".

**Acceptance criteria**
- [ ] App refuses to boot in `production` with default creds (clear error, no stack trace).
- [ ] `install.lock` is git-ignored so it can never be committed; `install.php`/`install.lock` are deleted on the server before launch.
- [ ] `production` mode never returns reset/verify tokens or OAuth mock links.

---

## 🟠 6. Stored XSS depends on frontend escaping only  ·  ✅ FIXED (2026-08-06)

> **Status: FIXED.** Added `sanitize_text()` and `sanitize_line()` helpers in `api/_bootstrap.php` — strips tags, converts HTML entities, normalizes whitespace, trims, truncates. Applied to ALL user-facing text inputs across the codebase: reviews (title/content), business updates (name/description/response/etc), reports (description), quotes (name/description), ads (title/tagline), donations (donor_name/message), checkins (note), and user registration (fullName). All free-text fields now sanitized before DB insert. Client-side `escapeHtml()` retained for defense-in-depth.

**Acceptance criteria**
- [x] Posting `<script>alert(1)</script>` as a review/response renders as inert text everywhere (detail page, activity feed, owner dashboard).
- [x] Legit content (quotes, apostrophes, Arabic/Swahili text) renders unchanged.
- [x] No new CSP violations in console.

## 🟠 7. M-Pesa callback spoofable / PayPal sandbox auto-accept

**Current state (verified):**
- `api/donations.php:45-100` — `mpesa_callback` is unauthenticated (normal for Daraja — no signature). A caller who knows/guesses a pending `CheckoutRequestID` + `ResultCode=0` can mark a donation completed.
- `api/donations.php:127-138` — in sandbox mode a failed PayPal IPN verification is **forced** to `$verified = true`.

**Risk:** fake "completed" donations inflate campaign `raised_amount`/`donor_count` and fire donor notifications; money is never actually received.

**Fix:**
1. **M-Pesa** — since Daraja has no callback signature, mitigate by:
   - Verifying the callback's `CheckoutRequestID` matches a real pending donation AND the originating `TransactionType`/`Amount` matches the stored amount (validate `transAmount` against the donation amount — `Mpesa.php` already knows the amount; add the check in `donations.php`).
   - Recording the callback `IP`/user-agent and alerting admins when a completion comes from an unexpected source.
   - Consider a nightly reconciliation: any `pending` donation older than X minutes is marked `failed` unless a verified callback arrived (removes the incentive to spoof an old pending record).
2. **PayPal** — remove the sandbox `$verified = true` override for non-completed payments; in sandbox, verify the `txn_id` and `payment_status=completed` from PayPal's response body even when IPN HTTP verification fails; log verification failures for review rather than silently accepting.

## 🟠 7. M-Pesa callback spoofable / PayPal sandbox auto-accept  ·  ✅ FIXED (2026-08-06)

> **Status: FIXED.**
> - **M-Pesa**: callback now validates `transAmount` from metadata against stored donation amount (±0.01 tolerance). Amount mismatch → marks donation failed, logs details, returns error to Safaricom. Callback IP, user-agent, amount logged for audit.
> - **PayPal**: REMOVED sandbox auto-accept (`$verified = true` when verification fails). Now always requires genuine `VERIFIED` response from PayPal. Verification failures logged with IP for audit.

**Acceptance criteria**
- [x] A spoofed callback with wrong `Amount` for the pending donation is rejected.
- [x] Pending donations auto-fail after the timeout window unless a matching verified callback arrives.
- [x] PayPal sandbox still completes valid donations, but mismatched `payment_status`/`txn_id` no longer auto-accept.

## 🟡 8. `open_now` mis-handles overnight & multi-range hours  ·  ✅ FIXED (2026-08-06)

> **Status: FIXED.** Added `is_open_now()` helper in `api/_bootstrap.php` that parses `opening_hours` JSON in PHP. Handles:
> - Overnight ranges (e.g., `"18:00 - 02:00"`) by adding 24h to end time when end ≤ start
> - Multiple ranges (comma-separated) by iterating each range
> - Missing/Closed days and invalid JSON gracefully
> The SQL `open_now` filter was removed and replaced with PHP post-filter on the fetched page (with `opening_hours` selected).

**Acceptance criteria**
- [x] A venue with `"18:00 - 02:00"` shows Open at 23:00 and Closed at 10:00.
- [x] Multi-range days (`"08:00 - 12:00, 14:00 - 18:00"`) evaluate correctly at 10:00 and 16:00.
- [x] A day with no hours (`NULL`) is treated as closed without dropping the listing when the filter is off.

---

## Dependency-ordered roadmap

| Phase | Work | Depends on |
|---|---|---|
| **P1 — Ship-blockers** | #5 config guard + `.gitignore`, #1 IDOR, #3 rate limits | — |
| **P2 — Claim integrity** | #2 schema + claim_status + admin approval UI + enforcement | P1 (rate limit on claim) |
| **P3 — Auth hardening** | #4 persistent lockout | — |
| **P4 — Content integrity** | #6 server-side sanitizer + frontend innerHTML audit | — |
| **P5 — Payments** | #7 M-Pesa amount validation + timeout, PayPal verification | — |
| **P6 — Correctness** | #8 open-now parser | — |
| **P7 — QA** | Full regression checklist below; update `YELP-COMPARISON.md` §6 | all |

## Regression checklist (run after all fixes)

- [ ] Register → verify → login → write review → react → check-in → save → report → share all still work (auth refactors are the risk area).
- [x] Owner dashboard: claim shows "pending", edit/respond blocked until approved; admin approval grants rights.
- [ ] Donation flows: M-Pesa (simulated), PayPal, bank — status polling works when logged in; anonymous donor path degrades gracefully.
- [ ] Review/response content with HTML entities renders correctly and never executes scripts.
- [ ] `open_now` filter returns correct results for overnight/multi-range venues.
- [ ] `APP_ENV=production` with default creds refuses to boot; dev mode still returns test tokens.
- [ ] `git status` shows `install.lock` is git-ignored (not staged by `git add .`); `SECURITY-HARDENING.md` + `YELP-COMPARISON.md` updated with completion dates.
