# Umma Directory — Code Review

Scope reviewed: PHP JSON API (`api/*`, `includes/*`, `config/*`), DB schema + installer
(`database/*`), and frontend (`assets/js/*`, `*.html`).

## Fix status

| # | Severity | Fix applied |
|---|----------|-------------|
| 1 | Critical | ✅ `api/businesses.php` — removed the unconditional `require_method('GET')` at the top and enforce GET only after the POST block. Claim / respond / update now work. |
| 2 | Critical | ✅ `config/config.php` — added `SMTP_FROM_NAME` / `SMTP_FROM_EMAIL`. Forgot-password no longer throws. |
| 3 | High | ✅ `api/donations.php` — implemented the `mpesa_callback` handler (marks donation completed, updates campaign totals, notifies admin); the M-Pesa `CheckoutRequestID` is now stored in `transaction_id`; `APP_URL` auto-detects so the callback URL is absolute. |
| 4 | High | ✅ `api/donations.php` — added the PayPal IPN handler (`paypal_ipn`) that verifies with PayPal and marks donations completed; donation id is passed via PayPal's `custom` field. |
| 5 | High | ✅ PayPal amount is now converted from KES via `PAYPAL_EXCHANGE_RATE` (default 1.0) into `PAYPAL_CURRENCY` (default USD), so the charged amount and currency are consistent and configurable. |
| 6 | Medium | ✅ Client (`assets/js/app.js`) now sends `?action=impression` / `?action=click` to match the server's query-param contract; mock updated too. Impressions/clicks are recorded again. |
| 7 | Medium | ✅ `api/reviews.php` delete now decrements the author's `total_reviews` and `total_photos` (clamped ≥ 0). |
| 8 | Medium | ✅ `includes/Auth.php` now actually records sessions in `user_sessions` and validates the session token on every `currentUser()` call, so password reset / logout genuinely revoke sessions. Gracefully degrades on old installs. |
| 9 | Medium | ✅ `api/auth.php` logout now requires CSRF (frontend already sends the header). |
| 10 | Medium | ✅ `api/_bootstrap.php` rate limiting moved out of the session into a per-IP file store, so clearing cookies can't bypass it. |
| 11 | Low | ✅ `api/businesses.php` rating breakdown now filters `is_approved`/`is_hidden`, matching the visible reviews. |
| 12 | Low | ✅ WhatsApp numbers are normalized to `254…` via a new `waNumber()` helper used on fundi cards and the fundi detail page. |
| 13 | Low | ✅ `reviews.php?action=mine` now includes review photos. |

See the numbered findings below for the original analysis.

---

---

## Critical — breaks core functionality

### 1. `api/businesses.php` — every POST action is unreachable
At line 10 the endpoint calls `require_method('GET')` **unconditionally**, before the
owner‑dashboard POST block (claim / respond / update). Any `POST api/businesses.php`
request is rejected with `405 Method not allowed`, so the `if ($_SERVER['REQUEST_METHOD'] === 'POST')`
block is dead code.

Impact — all of these frontend flows in `dashboard.js` call `POST api/businesses.php`
and are therefore broken:
- Claim a listing  (`action: 'claim'`)
- Respond to a review (`action: 'respond'`)
- Edit a listing   (`action: 'update'`)

Fix: remove the top-level `require_method('GET')` and route on the real method, e.g. run
the POST block only for POST and let GET fall through to the read handlers.

### 2. `includes/Auth.php` — undefined constants in the password-reset email
`requestPasswordReset()` builds the mail header as `'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>'`.
Neither `SMTP_FROM_NAME` nor `SMTP_FROM_EMAIL` is defined in `config/config.php` (only
`PAYPAL_BUSINESS_EMAIL` etc. exist). On PHP 8 an undefined constant throws a fatal
`Error`, so the **forgot‑password flow crashes (HTTP 500)**; on PHP 7 it silently emits a
broken `From:` header with the literal names.

Fix: add the two constants to `config.php`, or hard-code a default sender.

---

## High — payments never complete

### 3. M-Pesa callback is not implemented
`config.php` sets `MPESA_CALLBACK_URL = APP_URL . '/api/donations.php?action=mpesa_callback'`,
but `donations.php` has **no branch for that action**. It unconditionally runs
`require_method('POST')` and requires `charity_id`/`amount`/`payment_method`. An M-Pesa
server callback would fail. In live M-Pesa mode donations stay `pending` forever and
campaign/charity raised totals never update.

Two extra problems in the same area:
- `APP_URL` is `''`, so `MPESA_CALLBACK_URL` becomes a relative path
  (`/api/donations.php?...`) — M-Pesa needs an absolute public HTTPS URL.
- There is no way for the frontend to later learn that a pending donation was paid
  (no status/verify endpoint).

### 4. PayPal donations never complete
`donations.php` writes `pending` and returns a PayPal URL, but there is no PayPal
IPN/PDT handler to mark the donation `completed`. Only simulated M-Pesa and bank pledges
ever reach `completed`; all real PayPal donations stay `pending`.

### 5. PayPal currency mismatch
The donation is validated/stored in Kenyan Shillings (`KES`, “Minimum donation is KSh 10”),
but the PayPal link is built with `currency_code => 'USD'` and the same amount. The amount
gets charged as USD, not KES.

---

## Medium

### 6. Ad impressions/clicks are silently dropped (frontend/backend contract mismatch)
`ads.php` determines the action from `$_GET['action']` (line 19), but the frontend
`recordAdImpression()` (app.js:863) and the click handler (app.js:1095) send
`{action:'impression'|'click'}` in the **JSON body** with no query string. Every
impression/click hits `json_err('Unknown action', 404)` and the errors are swallowed by
`.catch(() => {})`, so ad metrics never increment and `ad_impressions`/`ad_clicks` rows
are never written. (Admin create/toggle/delete work because `admin.js` puts `?action=…`
in the URL.)

Fix: either append `?action=impression`/`?action=click` on the client, or have `ads.php`
read the action from the body when the query param is absent.

### 7. Review delete leaves counters stale
`api/reviews.php` delete removes the review and recomputes the listing aggregates, but
does **not** decrement the author's `users.total_reviews` (or `total_photos`), and review
creation never bumps the listing's `photo_count`. Contributor counters drift out of sync.

### 8. `user_sessions` is unused / “invalidate sessions” is a no-op
Sessions are pure PHP `$_SESSION` (cookie-based); `Auth::login` never writes to the
`user_sessions` table. `resetPassword()` runs `DELETE FROM user_sessions WHERE user_id = ?`
believing it invalidates the user's other sessions, but nothing does. The stated security
property (“invalidate all existing sessions for this user”) is not actually enforced.

### 9. Logout endpoint has no CSRF protection
`api/auth.php?action=logout` does not call `require_csrf()`. An attacker can force-logout
a logged-in user with a simple cross-site request.

### 10. Session/rate-limit design notes
- `_bootstrap.php` calls `session_start()` for **every** request, including anonymous
  public reads, so each visitor gets a session file + cookie on every listing/homepage hit.
- `rate_limit()` stores per-IP buckets in `$_SESSION['rl']`. It is keyed by IP but scoped
  to a session, so it's trivially bypassed by clearing cookies and ineffective per-IP on
  shared hosting (the code comment acknowledges this, but it's worth flagging as it gives
  false assurance for login/register brute-force).

### 11. Rating breakdown can exceed the visible review count
In `api/businesses.php` detail, `rating_breakdown` counts all reviews (no
`is_approved`/`is_hidden` filter) while the visible `reviews` list filters them out — the
star breakdown can show a different total than the reviews actually listed.

---

## Minor / polish

- `api/reviews.php`: the `getTimes`/prayer handling is fine, but note `attachReviewPhotos`
  is only wired for detail endpoints; `reviews.php?action=mine` doesn't include photos.
- `assets/js/pages/fundi.js`: WhatsApp card links use the raw `f.whatsapp` number
  (`wa.me/${f.whatsapp}`), whereas the quote flow normalizes it to `254…` — inconsistent
  formatting (a `0…` number would produce a bad wa.me link).
- `api/upload.php`: GIFs are re-encoded as a single still frame (animation is lost);
  acceptable but worth noting if animated GIF support matters.
- `database/install.php`: comment-stripping via `preg_replace('/--[^\n]*\n/', ...)` will
  corrupt any SQL containing `--` mid-line or `;` inside string literals; the current
  schema/seed files happen to be compatible, but it's fragile for future edits.
- `includes/Auth.php::register` auto-login is fine, but the per-account lockout is stored
  in the session only, so it resets when the cookie is cleared (same class of issue as #10).

---

## Summary

| # | Severity | Location | Issue |
|---|----------|----------|-------|
| 1 | Critical | `api/businesses.php` | POST actions unreachable → claim/respond/edit broken |
| 2 | Critical | `includes/Auth.php` | Undefined `SMTP_FROM_*` constants → forgot-password crashes |
| 3 | High | `api/donations.php` | M-Pesa callback unimplemented; `APP_URL` empty |
| 4 | High | `api/donations.php` | No PayPal IPN → PayPal donations never complete |
| 5 | High | `api/donations.php` | PayPal `currency_code=USD` vs KES amounts |
| 6 | Medium | `ads.php` + `app.js` | Ad impressions/clicks silently dropped |
| 7 | Medium | `api/reviews.php` | Review delete doesn't decrement user counters |
| 8 | Medium | `includes/Auth.php` | `user_sessions` unused; session invalidation is a no-op |
| 9 | Medium | `api/auth.php` | Logout lacks CSRF protection |
| 10 | Medium | `_bootstrap.php` | Per-IP rate limit stored in session; sessions on every request |
| 11 | Low | `api/businesses.php` | Rating breakdown vs visible reviews mismatch |
| 12 | Low | frontend | WhatsApp link number normalization inconsistency; misc polish |

The highest-value fixes are **#1 and #2** (they break real user flows today), followed by
the **payments trio (#3–#5)** before any live-money deployment.
