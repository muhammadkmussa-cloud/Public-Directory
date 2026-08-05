# Yelp Parity Plan — Closing the 5 Critical Gaps

*Plan date: 2026-08-05 · Branch: `arena/019fd352-ummah-directory-3-0` · Baseline: `91577cf`*

This plan covers the five must-have gaps to reach Yelp parity. Each section states the **current state (verified in the repo)**, the **goal**, concrete **implementation steps** (file-by-file), **DB changes**, **security notes**, and **acceptance criteria**, ending with a **dependency-ordered roadmap** and a **testing checklist**.

> ⚠️ Important finding up front: **Gap #1 (interactive map) is already mostly built.** `assets/js/map.js` has Leaflet + OpenStreetMap helpers (`initDetailMap`, `renderResultsMap`), and Leaflet is wired into `businesses.html` (list/map toggle), `business.html`, `mosque.html`, `fundi.html`. The plan for #1 is *extend + polish*, not build-from-scratch. The four other gaps are genuinely missing.

---

## Implementation status (2026-08-05 — all 5 gaps IMPLEMENTED on branch `arena/019fd352`)

| # | Gap | Status now | What was done | Verified? |
|---|---|---|---|---|
| 1 | Interactive map UI (Leaflet + OSM) | ✅ **Done** | List/Map toggles on businesses **and** mosques/fundis (`wireResultsMapToggle` helper); user-location blue-dot pin; detail maps already existed | ✅ DOM smoke test |
| 2 | Distance sorting/filtering | ✅ **Done** | `distance_clause()` (Haversine) in `api/_bootstrap.php`; `lat/lng/radius` + `sort=distance` on businesses/mosques/fundis; "📍 Near me" button; km badges on cards; mock parity | ✅ near-me flow test |
| 3 | Email verification | ✅ **Done** | `email_verifications` table; token + mail on register; `verify`/`resend_verification` API; `verify.html`; unverified banner; seed users stay verified | ✅ DOM smoke test |
| 4 | Google OAuth login | ✅ **Done** | `oauth_links` table; `api/oauth.php` (state-protected redirect + callback); `Auth::startSession()` refactor; Google button on login/register; dev-mode simulated flow | ✅ DOM smoke test |
| 5 | Share functionality | ✅ **Done** | `share.js` (Web Share API + modal: WhatsApp/X/Facebook/Email/copy); buttons on detail pages + cards; OG meta tags on every page | ✅ DOM smoke test |

---

## TL;DR status (original plan, for reference)

| # | Gap | Status now | What's actually left | Est. effort |
|---|---|---|---|---|
| 1 | Interactive map UI (Leaflet + OSM) | 🟡 **~85% done** | Maps on `mosques.html`/`fundis.html`; map↔list refinement; "my location" pin; small CSP/policy tweak | 0.5–1 day |
| 2 | Distance sorting/filtering | ❌ none | API geolocation params + Haversine SQL; `sort=distance`; "Near me" button + radius filter; distance badges on cards | 1–1.5 days |
| 3 | Email verification | ❌ none | Token table + mail on register; verify/resend API; `verify.html`; login gating; reuses existing `users.is_verified` column | 1 day |
| 4 | Google OAuth login | ❌ none | `oauth_links` table; `api/oauth.php` (redirect + callback); config keys; "Continue with Google" on login/register | 1–1.5 days |
| 5 | Share functionality | ❌ none | `share.js` (Web Share API + fallback modal: WhatsApp/X/Facebook/Email/copy); buttons on detail + cards; OG meta tags | 0.5 day |
| | **Total** | | | **~4–5 days** (1 dev) |

**Suggested order:** 5 → 1 → 2 → 3 → 4 (cheapest first; #2 depends on #1's map, #3 and #4 both touch `Auth.php`/`auth.php` so do them back-to-back).

---

## Prerequisites (hosting/ops — do once, in parallel with coding)

1. **HTTPS everywhere** — required for geolocation, Google OAuth, and secure session cookies. `config.php` auto-detects it; `.htaccess` already forces secure cookies/HSTS.
2. **Working mail** — email verification uses the same `@mail()` pattern as password reset (`includes/Auth.php::requestPasswordReset`). Confirm the host sends mail (SPF/DKIM records for the domain; set `SMTP_FROM_EMAIL` in `config/config.php` to a real, verifiable address). If the host requires SMTP, swap `@mail()` for PHPMailer in one shared helper so both reset + verification use it.
3. **Google Cloud OAuth client** (for gap #4) — create credentials at console.cloud.google.com: *APIs & Services → Credentials → Create OAuth client ID* (Web application). Authorized redirect URI must be exactly `https://yourdomain.com/api/oauth.php?action=callback`. You'll get `GOOGLE_CLIENT_ID` + `GOOGLE_CLIENT_SECRET`.
4. **Geolocation permission** — `.htaccess` currently sets `Permissions-Policy: geolocation=(), ...`, which **blocks** the browser Geolocation API. Change to `geolocation=(self)` (see Gap #2, step 0). Also note `connect-src 'self'` in the CSP blocks fetch to tile servers — this is fine today because Leaflet loads tiles via `<img>` (`img-src https:` allows it); do **not** switch to a JS-fetching tile lib without updating CSP.

---

## Gap 1 — Interactive map UI (Leaflet + OSM)

### Current state (verified)
- ✅ `assets/js/map.js`: `initDetailMap()` (single-marker detail map) + `renderResultsMap()` (multi-marker search map), OSM tiles `https://{s}.tile.openstreetmap.org/...`, attribution, escaped popups, `fitBounds`.
- ✅ Leaflet CSS/JS (unpkg 1.9.4) + `map.js` included on: `businesses.html` (with List/Map toggle + `#mapContainer`), `business.html` (`#bizMap`), `mosque.html` (`#mosqueMap`), `fundi.html` (`#fundiMap`).
- ✅ Detail pages call `initDetailMap(...)` with real `latitude`/`longitude` from the API.
- ❌ `mosques.html` and `fundis.html` listing pages: **no Leaflet, no map toggle**.
- ❌ Map view on search results is read-only: no click-pin-to-refine, no "use my location", no marker count/result sync.

### Goal
A Yelp-like map experience everywhere listings are shown: results pages (businesses, mosques, fundis) get a List/Map toggle; detail pages keep their embedded map; the map view supports "Near me" and opens a detail link from any pin.

### Steps
1. **`mosques.html` + `fundis.html`**: copy the pattern from `businesses.html` — add Leaflet CSS `<link>`, `#mapContainer` div, List/Map `view-toggle`, and `map.js` + Leaflet scripts at the bottom. In `assets/js/pages/mosques.js` / `fundis.js`, add the same `showMap()/showList()` + `viewToggle` wiring as `assets/js/pages/businesses.js` (reuse via a small shared helper in `map.js`: `wireResultsMapToggle({containerId, toggleId, getItems, itemUrl})` to avoid triple duplication).
2. **Map marker sync**: when the user clicks a marker popup "View details →", it already links to the detail page (good). Add `marker.options` title/rating so `fitBounds` + popups match Yelp's "pin + snippet" feel. (Nice-to-have: custom `divIcon` pins with rating dot.)
3. **"Use my location" button** in the map view + results bar (see Gap #2 — shared geolocation helper `getCurrentPosition()` in `map.js`; drop a blue dot marker via `L.circleMarker`).
4. **Detail pages polish**: on `business.html`, add a small "Open in Google Maps / OpenStreetMap" link under the embedded map (Yelp has this); ensure `initDetailMap` handles `scrollWheelZoom:false` + a zoom control (already true).
5. **CSP check**: keep `img-src https:` (tiles) and `connect-src 'self'` (no change needed for `<img>` tiles). If adding a geocoder later (e.g. Nominatim for "near" text search), add `connect-src https://nominatim.openstreetmap.org`.

### Acceptance criteria
- [ ] List/Map toggle on businesses, mosques, and fundis result pages; map renders with OSM tiles and correct markers for all results on the current page.
- [ ] Detail maps on business/mosque/fundi render a single centered marker with popup.
- [ ] Map view works with **no API key** and on the demo/mock fallback (graceful empty-state when no items have coordinates — already implemented in `renderResultsMap`).
- [ ] No console CSP violations.

---

## Gap 2 — Distance-based sorting & filtering

### Current state (verified)
- ❌ `api/businesses.php`, `api/mosques.php`, `api/fundis.php` accept no `lat`/`lng`/`radius` params; sort map is only `rating | reviews | name` (`$orderMap` in `api/businesses.php:281`).
- ❌ Result cards show no distance; sort dropdown on `businesses.html` has no "Distance" option.
- ✅ All three entities already store `latitude`/`longitude` (businesses, mosques, fundis — verified in queries + `mock.js`).
- ❌ `.htaccess` `Permissions-Policy: geolocation=()` blocks the browser Geolocation API.
- ❌ `mock.js` fallback has no distance logic (harmless — sort client-side for parity).

### Goal
Yelp's "distance" story: user clicks **Near me** (or the API is given `lat`/`lng`), results are **filterable by max radius** and **sortable by distance**, each card shows "0.4 km" style distance, and the map re-centers on the user.

### Steps
1. **`.htaccess`**: `Permissions-Policy` → `geolocation=(self), microphone=(), camera=()`. (Same edit needs to go in any dev-server config if one is used.)
2. **`api/businesses.php`** (same pattern for `mosques.php`, `fundis.php`):
   - Accept `lat`, `lng` (floats, range-checked `[-90,90]` / `[-180,180]`) and `radius` (km, default e.g. 25 when `sort=distance`).
   - Add computed column using MySQL Haversine (radius of earth 6371 km):
     ```sql
     (6371 * ACOS(LEAST(1, COS(RADIANS(?)) * COS(RADIANS(b.latitude))
        * COS(RADIANS(b.longitude) - RADIANS(?)) + SIN(RADIANS(?))
        * SIN(RADIANS(b.latitude))))) AS distance_km
     ```
   - When `lat/lng` present: append `HAVING distance_km <= ?` (or repeat the formula in a `WHERE` — MySQL allows it), add `'distance' => 'distance_km ASC'` to `$orderMap`, and always select `distance_km` so the frontend can display it.
   - Validate: distance sort without coords → fall back to `rating`; clamp page size; keep the existing `WHERE b.is_open = 1` behavior.
3. **Frontend (`businesses.html` + `assets/js/pages/businesses.js`; same for `mosques.html`/`fundis.html`)**:
   - Add `<option value="distance">Distance</option>` to the sort dropdown; add a "Near me" toggle/button that calls `navigator.geolocation.getCurrentPosition(...)` (with graceful deny → toast "Location off — showing all results").
   - On success: store `lat/lng` in the form (hidden inputs), auto-select `sort=distance`, reload, and pass coords to `showMap()` so the map centers on the user.
   - Card renderers (`businessCard` in `assets/js/app.js` + mosque/fundi card renderers): show `distance_km` badge ("1.2 km") when present, Yelp-style in the meta row. Add the same to `mock.js` (compute from a fixed "Nairobi center" when lat/lng present).
4. **Map sync**: after a distance sort, `fitBounds` markers + center on user pin (Gap #1 step 3).
5. **URL state**: persist `lat/lng/radius` in the query string so "Distance" sort survives refresh/share (same `history.replaceState` pattern already in `businesses.js`).

### Acceptance criteria
- [ ] `api/businesses.php?lat=-1.2864&lng=36.8231&sort=distance` returns items ordered by `distance_km` with the field present; `radius=5` excludes farther items.
- [ ] "Near me" works on HTTPS with permission; deny path is graceful.
- [ ] Distance badge appears on business, mosque, fundi cards; hidden when no coords.
- [ ] Same behavior mirrored in mock/demo mode.

---

## Gap 3 — Email verification on registration

### Current state (verified)
- ❌ `Auth::register()` (includes/Auth.php:26) inserts the user, auto-logs-in, and returns — **no verification token, no email**.
- ❌ `api/auth.php` has no `verify` / `resend_verification` actions; no `verify.html` page exists.
- ✅ `users.is_verified TINYINT(1) DEFAULT 0` **already exists** (currently unused for gating — only surfaced in `Auth::currentUser()` and used as a listing-verified flag on *businesses*; user-level `is_verified` is free to claim for email verification).
- ✅ The full mail-token pattern already exists for password reset: `Auth::requestPasswordReset()` → random token + `password_resets` table + `@mail()` + dev-mode token echo. Reuse this exact shape.

### Goal
Yelp-style: after registering, the user must click a link in an email before the account is fully usable. Existing/seed accounts remain functional (or are grandfathered verified) so the change doesn't break current users.

### Steps
1. **Schema (`database/schema.sql` + a migration snippet)** — new table:
   ```sql
   CREATE TABLE email_verifications (
     id INT UNSIGNED NOT NULL AUTO_INCREMENT,
     user_id INT UNSIGNED NOT NULL,
     token VARCHAR(64) NOT NULL,
     expires_at TIMESTAMP NOT NULL,
     used TINYINT(1) NOT NULL DEFAULT 0,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     PRIMARY KEY (id),
     UNIQUE KEY token (token),
     KEY user_id (user_id),
     KEY expires_at (expires_at),
     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
   ```
   (Also ship it as `database/migrations/003_email_verification.sql` alongside the installer, or fold into `schema.sql` + a `UPDATE users SET is_verified=1` for seed users so demo accounts keep working.)
2. **`includes/Auth.php`**:
   - `register()`: after insert, generate `bin2hex(random_bytes(32))` token, insert into `email_verifications`, `@mail()` a "Confirm your email" message with `{APP_URL}/verify.html?token=...` (mirror `requestPasswordReset` — dev mode returns `verification_link` in the response for testing without SMTP). **Keep auto-login but mark unverified.**
   - `verifyEmail($token)`: look up unexpired unused token → `UPDATE users SET is_verified = 1` → mark token used → return ok. Rate-limit at the API layer.
   - `resendVerification($email)`: only for existing unverified users; 15-min cooldown; don't reveal whether the email exists.
   - `login()`: **soft gate** — allow login but include `is_verified: 0` in the returned user (frontend shows a banner with resend). *(Optional hard gate: return 403 "Please verify your email" — recommend soft gate to avoid locking out testers.)*
3. **`api/auth.php`**: add `?action=verify` (POST `{token}`) and `?action=resend_verification` (POST `{email}`), both `require_csrf()` + `rate_limit()`, mirroring the existing forgot/reset handlers.
4. **`verify.html`** (new page): reads `?token=`, POSTs to `verify`, shows success (→ "You're verified — continue to your dashboard") or error with a "Resend email" link. Add it to `manifest.json`/`sw.js` precache list.
5. **UI touch-points**:
   - `register.html` + `assets/js/pages/auth.js`: success message "Check your email to confirm your account" + a "Resend" link; only redirect to dashboard if already verified.
   - Header/user menu (`assets/js/app.js`): show "Verify email" pill next to the avatar when `user.is_verified === 0`.
   - `dashboard.html` + `assets/js/pages/dashboard.js`: dismissible banner "Confirm your email — resend".
   - `profile.html`: show verified badge (reuse `verification_badge`/`is_verified` display already partially present).

### Acceptance criteria
- [ ] New registration does NOT auto-activate; user receives an email with a working one-time link (or dev-mode token echo).
- [ ] `verify` marks the account verified; token is single-use and expires (1h).
- [ ] Unverified users see the banner everywhere and can resend (rate-limited).
- [ ] Seed users (`admin@example.com`, `demo@example.com`) and pre-existing rows are `is_verified = 1` — nothing breaks.
- [ ] Register/login flows still pass on the mock/no-backend fallback (frontend must not crash when the verify field is absent).

---

## Gap 4 — Social / OAuth login (Google)

### Current state (verified)
- ❌ No OAuth anywhere: `api/` has `auth.php`, `csrf.php`, etc. — no `oauth.php`; `config.php` has no Google keys; `login.html`/`register.html` are email/password only.
- ✅ Sessions + CSRF + `user_sessions` revocation already exist — OAuth login can hook straight into `Auth::loginUserById()`-style session issuance (reuse the token/session code in `Auth::login` by extracting a small `startSession($userId)` helper).
- ✅ `users.email` is UNIQUE — safe to link OAuth accounts by verified email.
- ⚠️ CSP note: we will use a **server-side redirect flow** (full page navigation via `Location`), so no CSP changes are needed (top-level navigations aren't gated by `connect-src`/`script-src`).

### Goal
A "Continue with Google" button on login + register that: redirects to Google, comes back with a verified identity, logs the user in (creating an account on first use, with email pre-verified since Google verified it), and lets a user with an existing email/password account link their Google identity for future one-click logins.

### Steps
1. **`config/config.php`**: add
   ```php
   // ---- Google OAuth (leave empty to run in dev/demo mode) ----
   define('GOOGLE_CLIENT_ID', '');
   define('GOOGLE_CLIENT_SECRET', '');
   define('GOOGLE_REDIRECT_URI', (APP_URL ?: '') . '/api/oauth.php?action=callback');
   define('OAUTH_STATE_LIFETIME', 600);
   ```
2. **Schema**: `oauth_links` table:
   ```sql
   CREATE TABLE oauth_links (
     id INT UNSIGNED NOT NULL AUTO_INCREMENT,
     user_id INT UNSIGNED NOT NULL,
     provider ENUM('google') NOT NULL,
     provider_user_id VARCHAR(64) NOT NULL,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     PRIMARY KEY (id),
     UNIQUE KEY provider_user (provider, provider_user_id),
     KEY user_id (user_id),
     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
   ```
3. **`api/oauth.php`** (new endpoint):
   - `?action=login&provider=google`: generate `random_bytes(32)` state, stash in session, `header('Location: https://accounts.google.com/o/oauth2/v2/auth?client_id=...&redirect_uri=...&response_type=code&scope=openid%20email%20profile&state=...')`. If no client ID configured → dev mode: return a fake "authorize" JSON with instructions (mirrors password-reset dev behavior) or auto-simulate a test user.
   - `?action=callback&provider=google`: verify `state` matches session; exchange `code` via POST to `https://oauth2.googleapis.com/token` (curl/file_get_contents; add `connect-src https://oauth2.googleapis.com` to CSP if the server-to-server call is made via JS — but do it server-side in PHP so no CSP change is needed); fetch `https://www.googleapis.com/oauth2/v3/userinfo`; verify the `email_verified` claim.
   - Account logic:
     - Existing `oauth_links` row → log in that user.
     - Else email matches a `users` row → **link** (insert `oauth_links`), log in.
     - Else → **create** user (username from email prefix w/ uniqueness suffix, `password_hash` = unguessable random, `is_verified = 1` since Google verified it), insert `oauth_links`, log in.
     - Never auto-login when the local account is banned (`is_active = 0`) — reuse the same checks as `Auth::login`.
   - On success: `header('Location: ' . APP_URL . '/dashboard.html')` (or return JSON in dev mode).
4. **`includes/Auth.php`**: extract `startSession($userId)` from the existing `login()` body (session token + `user_sessions` insert + `$_SESSION` set) so both password and OAuth logins share it. Add `findOrCreateOAuthUser(...)` helper.
5. **UI**: `login.html` + `register.html` — official "Sign in with Google" button above the divider ("or"), styled per Google brand guidelines (white button, G logo SVG inline — no external font/script needed). `assets/js/pages/auth.js`: `location.href = 'api/oauth.php?action=login&provider=google'` on click. Add a note on `login.html`: "First time? We'll create your account automatically."
6. **Docs**: README "Installation" section + plan: add a "Google sign-in setup" subsection (Cloud console steps, redirect URI, key entry).

### Acceptance criteria
- [ ] Full flow works over HTTPS: click Google button → Google consent → back → logged in; brand-new users get an account with email pre-verified.
- [ ] Same-email existing account links; subsequent clicks log straight in.
- [ ] State param prevents CSRF login; provider errors surface a friendly message, never a crash.
- [ ] Dev/demo mode (no keys configured) returns a simulated link so the UI is testable without Google credentials.
- [ ] Logout revokes the OAuth-backed session like any other (`user_sessions`).

---

## Gap 5 — Share functionality for listings

### Current state (verified)
- ❌ No share buttons, no Web Share API usage, no copy-link, no `og:` meta tags on any page.

### Goal
Yelp's "Share" on every listing: a share button on detail pages (business, mosque, fundi, charity) and on result cards; tapping it opens native share where supported (mobile) or a modal with copy-link + WhatsApp / X / Facebook / Email. Links posted to WhatsApp/X show a decent card preview via `og:` meta tags.

### Steps
1. **`assets/js/share.js`** (new):
   - `shareListing({title, url, text})` → try `navigator.share` (Web Share API, with `navigator.canShare` guard); fallback opens `#shareModal`.
   - Modal content: copy-link button (Clipboard API + `execCommand` fallback), and pre-filled share URLs:
     - WhatsApp: `https://wa.me/?text={encoded title + " " + url}`
     - X: `https://twitter.com/intent/tweet?text=...&url=...`
     - Facebook: `https://www.facebook.com/sharer/sharer.php?u=...`
     - Email: `mailto:?subject=...&body=...`
     - (Optional, low effort) Telegram: `https://t.me/share/url?url=...&text=...`
   - Copy success toast (reuse existing toast util if present, else small inline one). All URLs opened via `window.open` / `<a target="_blank" rel="noopener">`.
2. **Detail pages**: add a "Share" icon-button to the action row of `business.html`, `mosque.html`, `fundi.html`, `charity.html` (next to save/favorite where present); `assets/js/pages/*.js` call `shareListing({title: <name>, url: location.href, text: <short_description>})`.
3. **Result cards**: add a subtle share icon on `businessCard`/`mosqueTile`/`fundiTile` (in `assets/js/app.js`) that calls `shareListing` with the item's URL — stop propagation so it doesn't navigate.
4. **OG/meta tags**: add to `<head>` of each listing page template (static defaults) **and** update them client-side before sharing: `og:title`, `og:description`, `og:image` (primary photo), `og:url`, `twitter:card=summary_large_image`. Note in README/plan: because pages are static shells, social crawlers see the static defaults unless a pre-render step is added — acceptable for v1, worth a follow-up (the existing JSON-LD on detail pages already helps search engines).
5. **PWA**: add share targets? Not needed — Web Share API + manual links cover it. Add `verify.html`/share CSS to `sw.js` cache list if it caches page shells.

### Acceptance criteria
- [ ] Share button on all 4 detail page types + result cards; no dead clicks on desktop without Web Share API (modal opens).
- [ ] Copy-link works (clipboard) with visual confirmation; WhatsApp/X/Facebook links open with correct pre-filled text.
- [ ] `og:` tags present and correct per page; shared links render a preview on WhatsApp/X.
- [ ] Works in demo/mock mode (no backend dependency).

---

## Roadmap (dependency-ordered)

| Phase | Work | Depends on | Est. |
|---|---|---|---|
| **P0 — Config & ops** | `.htaccess` Permissions-Policy `geolocation=(self)`; SMTP/SPF check; Google Cloud OAuth client; HTTPS confirm | — | 0.5 day |
| **P1 — Share (#5)** | `share.js` + modal, buttons on detail + cards, OG tags | P0 (none really) | 0.5 day |
| **P2 — Maps (#1)** | `mosques.html`/`fundis.html` map toggle; shared toggle helper; user-pin; "Open in OSM" links | P0 (geolocation policy for the pin) | 0.5–1 day |
| **P3 — Distance (#2)** | API `lat/lng/radius` + Haversine + `sort=distance`; Near-me button; radius filter; distance badges; map centering | P1 (card renderers touched), P2 (map centering) | 1–1.5 days |
| **P4 — Email verification (#3)** | `email_verifications` table; token+mail in `Auth::register`; verify/resend API; `verify.html`; banners; seed migration | P0 (mail) | 1 day |
| **P5 — Google OAuth (#4)** | `oauth_links` table; `api/oauth.php`; `Auth::startSession()` refactor; Google button on login/register; README setup docs | P4 (both touch Auth/auth — do after to avoid merge churn) | 1–1.5 days |
| **P6 — QA pass** | Full test checklist below; update `YELP-COMPARISON.md` statuses + this plan's checkboxes | all | 0.5 day |

Total ≈ **4–5 focused days**.

---

## Testing checklist (run after each phase)

**Maps**
- [ ] Toggle List↔Map on businesses, mosques, fundis pages; markers match result count and open correct detail pages.
- [ ] Detail maps render on business/mosque/fundi; popup links work; no API key required.
- [ ] No CSP errors in console (tiles via `img-src https:`).

**Distance**
- [ ] API: `?lat=&lng=&sort=distance` ordering verified with known coordinates; `radius` filtering verified (e.g. Kisumu items excluded when centered on Nairobi with radius 25).
- [ ] Browser: Near me grants/denies permission gracefully; sort dropdown has Distance; card badges show km; URL keeps `lat/lng`.
- [ ] Mock fallback still renders (client-side distance).

**Email verification**
- [ ] Fresh registration → email arrives (or dev token returned); link verifies once; second use fails (token single-use); expiry enforced.
- [ ] Unverified login shows banner + resend works (rate-limited); verified users see nothing.
- [ ] Seed users and pre-existing rows stay verified; `database/install.php` still completes.

**Google OAuth**
- [ ] Button → Google consent → back → logged in (new + returning users; email-link case).
- [ ] State mismatch / bad code / revoked token → friendly error, no partial session.
- [ ] Dev mode without keys → simulated flow, UI testable.
- [ ] Logout works; banned users cannot OAuth in.

**Share**
- [ ] Desktop (no Web Share API): modal opens; copy works; WA/X/FB/Email links pre-fill correctly.
- [ ] Mobile: native share sheet appears; link previews render.
- [ ] Share icons don't trigger card navigation (stopPropagation).

**Regression**
- [ ] Existing flows: login/register/forgot/reset, reviews, favorites, check-in, donations, quotes, ads, moderation — all still pass (auth refactor in P5 is the risk area).
- [ ] `git` history clean per phase; `YELP-COMPARISON.md` updated (mark items 1–5 done with dates).

---

## Risks & notes

- **Mail deliverability** is the #1 external risk for gap #3 (and password-reset already relies on `@mail()`). If the host's mail is unreliable, budget a PHPMailer swap (one shared helper used by reset + verify).
- **Geolocation** requires HTTPS and *user consent*; never block the whole search when denied — degrade to text-location search.
- **OAuth scope**: only request `openid email profile` — no contacts/calendar; store only what's needed; never store Google tokens beyond the exchange (we don't need refresh tokens).
- **CSP**: keep `connect-src 'self'`; only widen if a feature genuinely needs it (Nominatim geocoding, server-side token exchange should never touch the browser).
- **Mock/demo mode**: every new feature must degrade gracefully when the PHP API is unreachable (the site's core fallback philosophy — `assets/js/mock.js`).
