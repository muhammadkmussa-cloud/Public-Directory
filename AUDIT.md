# Ummah Directory — Codebase Audit

**Audited:** 2026-08-05 · Branch: `arena/019fd2b8-ummah-directory-3-0` (at `bc87945`)
**Method:** Full static review of all 32 PHP files, schema, JS, CSS, and routing config. (PHP runtime was not available in the sandbox, so findings are based on code-level analysis — every "fatal" below is a direct, provable call/mismatch, not a guess.)

---

## Executive Summary

The repository contains a promising **schema** (`database/schema.sql`, 28 tables, good Yelp-style design) and a **partially built application layer** that currently cannot run. The headline problems:

1. **Every page fatally errors.** The shared header calls `Auth::check()` / `Auth::user()` — methods that don't exist on the `Auth` class — so home, businesses, login, and register all die with *"Call to undefined method"*.
2. **The code uses a different data model than the schema.** Almost every SQL query references tables/columns that don't exist in `schema.sql` (`b.status`, `b.category`, `r.business_id`, `check_ins`, `quote_requests`, `review_votes`, `photos`, `fundi_portfolio`, `photo_url`, `entity_id`, …). Even if the fatal method calls were fixed, every query would throw a SQL error.
3. **Two divergent halves of the app coexist.** A "new" set of pages uses `pages/layouts/` + a polymorphic `listing_id/listing_type` model; an "old" set (fundis, mosques, reviews) uses `views/layout/` + an `entity_id/entity_type` model and a different helper set (`renderStars()`, `generateCSRFToken()`, `$db->queryOne()`, `$db->prepare()`) — none of which exist.
4. **Auth is non-functional**: registration always throws (`register([...])` vs `register($username, $email, $password, …)` signature) and login always "succeeds" (checks a truthy array).
5. **APIs are all broken**: undefined `Database` methods, missing tables/columns, missing CSRF, missing `config/database.php` includes.

Estimated effort to reach a runnable MVP: **medium** — the schema and some view markup are solid, but the PHP layer needs to be reconciled against one data model, one layout set, and one `Database`/`Auth` API.

---

## 1. BLOCKER — the app cannot execute any page

### 1.1 Undefined `Auth::check()` / `Auth::user()`
`includes/Auth.php` defines `isLoggedIn()`, `getCurrentUser()`, `getUserId()` — **not** `check()` or `user()`.

| Location | Impact |
|---|---|
| `pages/layouts/header.php:45,48` | Used on **every** page rendered with the new layout → fatal error site-wide (home, businesses, login, register, 404-adjacent). |
| `pages/businesses/show.php:88,139,174,301,308` | Fatal on the business detail page. |

Fix: replace with `Auth::isLoggedIn()` / `Auth::getCurrentUser()` (and `full_name`, not `name` — see §2).

### 1.2 Undefined helper functions
`includes/helpers.php` defines `starRating()` but **not** `renderStars()` or `generateCSRFToken()`.

| Location | Impact |
|---|---|
| `pages/fundis/index.php:191`, `pages/fundis/show.php:98,210`, `pages/mosques/index.php:192`, `pages/mosques/show.php:95,227`, `pages/reviews/index.php:78,140` | `renderStars()` → fatal on every fundi/mosque/review page. |
| `pages/reviews/index.php:199` | `generateCSRFToken()` → fatal in review modal. |

Fix: alias `renderStars()` → `starRating()`, `generateCSRFToken()` → `Auth::generateCsrfToken()` (or rename usages).

### 1.3 Undefined `Database` methods
`includes/Database.php` exposes only `query / fetchOne / fetchAll / insert / update / delete / beginTransaction / commit / rollback / escapeLike / getConnection`. The code calls `queryOne()`, `prepare()`, and `lastInsertId()`:

| Location | Call |
|---|---|
| `api/checkin.php:40`, `api/reviews.php:113,132,157` | `$db->queryOne(...)` |
| `pages/fundis/index.php:62`, `pages/mosques/index.php:73,82` | `$db->queryOne(...)` |
| `api/quotes.php:33,44`, `pages/fundis/index.php:70`, `pages/fundis/show.php:20,43,48`, `pages/mosques/index.php:89`, `pages/mosques/show.php:21,48,53` | `$db->prepare(...)` |
| `api/reviews.php:69` | `$db->lastInsertId()` (use `$db->insert()` instead) |

### 1.4 Includes pointing at directories that don't exist
`pages/fundis/show.php`, `pages/mosques/show.php`, and `pages/reviews/index.php` include `__DIR__ . '/layout/header.php'` (also `layout/404.php`, `layout/footer.php`). There is no `pages/*/layout/` directory — the files live at `views/layout/`. Pages render without header/footer (or fatal earlier).

### 1.5 Missing `config/database.php` include → undefined constants
- `api/quotes.php` and `api/upload.php` never `require` `config/database.php`. `Database::getInstance()` builds the DSN from `DB_HOST`/`DB_NAME`/… — undefined constants are a fatal `Error` in PHP 8 → every request to these APIs dies before doing anything.
- `api/checkin.php` and `api/reviews.php` *do* include config — the inconsistency itself is a smell.

---

## 2. BLOCKER — code queries don't match the schema

The schema is internally consistent (polymorphic `reviewable_id/reviewable_type`, `business_categories` join table, `*_photos` with `photo_path`, `checkins` with `checkinable_id/checkinable_type/note`). The PHP code consistently uses a *different*, older model. **Every listing query will throw a SQL error.**

### Key mismatches

| Code uses | Schema has | Used at |
|---|---|---|
| `businesses.status = 'active'` | no `status` column on `businesses` | home, businesses index/show |
| `businesses.category` | no `category` (join via `business_categories` → `categories`) | home, businesses index/show |
| `businesses.features` | `amenities` (JSON) | businesses index/show |
| `businesses.owner_id` | `user_id` | businesses/show.php:301 |
| `business_photos.photo_url` | `photo_path` | home, businesses index/show |
| `reviews.business_id / r.mosque_id / r.fundi_id` | `reviewable_id` + `reviewable_type` | home, businesses pages |
| `reviews.listing_id / listing_type / text / status / photos` | `reviewable_id / reviewable_type / content / is_approved /` (no `photos` column) | api/reviews.php, businesses/show.php |
| `reviews.service_rating / value_rating / atmosphere_rating` | not present | api/reviews.php:52 |
| `users.name / avatar / avatar_url / role / check_in_count / city` | `full_name / profile_photo / user_type / total_checkins` (no `city`) | views/layout/header.php, businesses/show.php, fundis/mosques pages, api/* |
| `check_ins` table + `listing_id, listing_type, message` + `check_in_count` | `checkins` + `checkinable_id, checkinable_type, note` + `total_checkins` | api/checkin.php |
| `quote_requests` table | **does not exist** | api/quotes.php |
| `review_votes` table | `review_helpful` (with NOT NULL `is_helpful`) | api/reviews.php:113,120 |
| `photos` / `fundi_portfolio` tables | `fundi_photos` (with `is_portfolio`) | fundis/show.php, fundis/index.php, mosques/show.php |
| `fundis.name / slug / status / verified / category / services / location / badges / price_range / rating` | `profession / is_verified / is_available / skills (JSON) / working_hours (JSON)`; no slug/status/category | fundis pages |
| `mosques.status / jummah_prayer / wudu_facilities / parking / cover_photo / verified / rating / county / has_jummah / has_wudu / has_parking / wheelchair_accessible / women_section` | `is_verified / rating_average / facilities (JSON)`; no status/flag columns | mosques pages |
| `reviews.status = 'approved'` | `is_approved` | fundis/show.php, mosques/show.php |

**Recommendation:** pick the schema model (it's the better one) and rewrite the queries to it — or migrate the schema to the code model. Don't patch piecemeal; the two models are incompatible.

---

## 3. BLOCKER — authentication cannot work

1. **Registration always fails** — `pages/auth/register.php:43` calls `$auth->register([...])` with one array argument, but `Auth::register($username, $email, $password, $userType, $extraData)` requires three scalars → `ArgumentCountError`, caught, user sees a generic error. Also, the form collects `name/phone/city` but the method requires a `username`; and `users` has no `city` column.
2. **Login always "succeeds"** — `pages/auth/login.php:22`: `if ($auth->login(...))` — `Auth::login()` returns an array (`['success' => …]`), which is **always truthy**. Wrong passwords still redirect to `/dashboard`. It must be `$result = $auth->login(...); if ($result['success'])`.
3. **CSRF token is rendered but never verified** on login/register handlers (only `api/upload.php` checks it).
4. **Session fixation** — no `session_regenerate_id(true)` after login; session token is the raw `session_id()` stored in `user_sessions`.
5. **Session cookies are insecure** — `session_start()` in `config/database.php` without `httponly`, `secure`, or `samesite` flags, even in production.
6. **Broken redirect targets** — `Auth::requireLogin()`/`requireAdmin()` redirect to `/pages/auth/login.php` and `/pages/unauthorized.php`, neither of which is a route (→ 404). Should be `/login` and a real 403 page.
7. **Open redirect** — `pages/auth/login.php:24` uses `$_GET['redirect']` directly in `header('Location: …')` with no allow-list → open-redirect phishing vector. Same pattern in `businesses/show.php:142`.
8. **No rate limiting / lockout** on login or password reset → brute-force friendly. Min password length 6 is weak.
9. **Password reset flow is half-built** — `requestPasswordReset()`/`resetPassword()` exist and `mail()` is called, but there are no pages or routes for forgot-password/reset (links point to `/forgot-password`, which doesn't exist). `SMTP_*` config is unused.
10. **`Auth` is used inconsistently** — as static (`Auth::isAdmin()`, `Auth::generateCsrfToken()`) and via instance (`$auth->login()`, `new Auth($db)` in pages — `Auth` has no constructor, so the `$db` argument is silently ignored).

---

## 4. HIGH — APIs are broken and unguarded

| API | Problems |
|---|---|
| `api/checkin.php` | Fatal: `queryOne()` (§1.3). Wrong table/columns (§2). No CSRF check. No duplicate-check-in prevention, no rate limit. `check_in_count` column doesn't exist. |
| `api/reviews.php` | Fatal: `queryOne()` + `lastInsertId()`. Columns `listing_id/listing_type/text/status/service_rating/…` don't exist. "helpful" writes to non-existent `review_votes` and omits NOT NULL `is_helpful`. `catch` calls `$db->rollBack()` even when no transaction is open → `PDOException` → 500 instead of JSON. Admin check uses `$user['role']` (schema: `user_type`). Delete doesn't remove `review_photos` (orphans). Inline photo upload validates extension only (no MIME/content check) — bypasses the careful logic in `api/upload.php`. No CSRF, no duplicate-review prevention, no rate limit. |
| `api/quotes.php` | Missing config include → undefined constants. Fatal: `prepare()`. Table `quote_requests` doesn't exist. No CSRF. `$fundiId` untyped. Also requires `Auth.php` but never uses it. |
| `api/upload.php` | Missing config include → undefined constants. Otherwise the **best-written file in the project** (finfo MIME check, GD re-encode strips payloads, random names, thumbnails, permissions). `Auth::requireLogin()` on an API should return 401 JSON, not a redirect. No pixel-bomb/side limit on decompression. |

**Front-end wiring is also broken:**
- `assets/js/main.js` posts to `/api/reviews/helpful` and `/api/checkin` — routes that don't exist (API is `/api/reviews.php` + POST `action=helpful`, and `/api/checkin.php`).
- `pages/reviews/index.php` submits `action=create`; the API only handles `submit|helpful|delete` → "Invalid action".
- The reviews page's JS reads a `meta[name="csrf-token"]` tag that no layout emits.
- Router registers `api/reviews` → `api/reviews/create.php` and `api/checkin` → `api/checkin/create.php` — **both files are missing**.

---

## 5. HIGH — routing/navigation dead ends

Registered routes pointing at files that don't exist (blank page on hit, since `Router` uses `include`): `businesses/reviews.php`, `businesses/write-review.php`, `charities/index.php`, `charities/show.php`, `user/profile.php`, `user/dashboard.php`, `api/reviews/create.php`, `api/checkin/create.php`.

Links/URLs with no route or file behind them:
- `/listing/fundi/{id}`, `/listing/mosque/{id}` (used by fundis/mosques index — actual routes are `/fundi/{id}`, `/mosque/{id}`)
- `/businesses/write-review` (home "Add Business" CTA)
- `/reviews/write?entity=…` (all "Write a Review" buttons on fundis/mosques pages)
- `/forgot-password`, `/terms`, `/privacy`, `/advertise`, `/help`, `/guidelines`, `/dashboard/business/…`, `/success-stories`

Other routing issues:
- `mosques/show.php` and `fundis/show.php` read `$_GET['slug']`, but the router passes the URL segment as `$id` (route `mosque/{id}`) → always empty → redirect to list. Old-style pages were never adapted to the router.
- Inconsistent pluralization: `/businesses` list but `/business/{id}` detail; `/mosques` but `/mosque/{id}`; `/fundis` but `/fundi/{id}`.
- `Router::dispatch()` iterates `$this->routes[$method]` without checking the method exists (PUT/DELETE → warning). 404 branch doesn't `exit` (works today only because nothing follows). No error handling around `include` of missing handlers (should 404, not blank).
- No custom error handler wired (`public/500.php` exists but is never referenced; no `ErrorDocument`).

---

## 6. MEDIUM — security hardening

- **CSRF missing** on login, register, review submit/helpful/delete, check-in, quote-request (only upload has it). All are state-changing.
- **Open redirect** via `redirect` GET param (login).
- **Session**: no fixation protection, no secure cookie flags.
- **No rate limiting** anywhere (auth, reviews, checkins, quotes, uploads) → spam/abuse surface.
- **XSS**: output escaping is generally good (`e()`/`htmlspecialchars` everywhere it matters). Watch: `pages/auth/register.php` outputs `$error` unescaped (currently server-generated strings only, but brittle), and `$_GET` values reflected into `href="?<?= http_build_query($_GET) ...` (URL-encoded by `http_build_query`, OK).
- **SQLi**: parameterized queries throughout — good. Dynamic table names in `api/reviews.php` `updateListingRating()` are constrained to three literals — OK. Router param regex restricts to `[0-9a-zA-Z_-]` — OK.
- **Secrets**: `config/database.php` contains M-Pesa/PayPal placeholders and DB credentials in code — expected for this DirectAdmin style, but consider `.env`-style overrides; confirm none of the committed values are real.
- **Uploads**: `uploads/` directory doesn't exist yet (upload.php creates it). `.gitignore` excludes `uploads/` — good. Add a `.htaccess` in `uploads/` denying PHP execution (`php_flag engine off`) as defense-in-depth.

---

## 7. LOW — polish / correctness

- `config/database.php` `UPLOAD_PATH` points to `config/uploads/` — wrong; code uses `__DIR__/../uploads/`.
- `APP_URL` is a placeholder — `url()`/`asset()` produce broken absolute URLs until configured; helpers hard-code absolute URLs instead of relative, which also hurts local preview.
- Missing assets referenced: `favicon.ico`, `manifest.json`, `sw.js` (PWA references), `assets/img/default-avatar.png`, `assets/images/avatar.png`.
- Two near-duplicate header/footer pairs (`pages/layouts/` vs `views/layout/`) with different class systems (`--primary-color` custom CSS vs Bootstrap classes + Font Awesome CDN). `style.css` contains **zero** Bootstrap-style rules, so the old-style pages (fundis/mosques/reviews) will render unstyled.
- `pages/mosques/index.php:73` contains a nonsensical/dead line (`$totalResult = $db->queryOne($totalQuery, str_replace(...) ? $params : []);`).
- `PrayerTimes` class is unused by any page (mosques pages call a non-existent `getDayPrayers()` method); `PRAYER_TIMES_API` constant unused; Hijri approximation is rough; `getNextPrayer()` can report "Until tomorrow" without a countdown.
- `isOpenNow()` helper assumes open if hours unset — fine, but the "Open Now" filter on `/businesses` isn't actually applied (checkbox exists, query never uses it).
- `sanitize()` in helpers applies `htmlspecialchars` to input — escaping belongs at output; keep `e()` as the single escaping path.
- No seed data for businesses/mosques/fundis — even a fixed site shows empty lists until content is added.
- No tests, no CI, no `composer.json` (manual `require`s are fine for this size but make bootstrapping easy to get wrong, as evidenced by the missing-config bugs).
- README claims "Phase 1 complete"; that's optimistic — schema yes, application no.

---

## 8. What's actually good

- **Schema design** is thoughtful and complete (polymorphic reviews, JSON attributes, badges/check-ins, ads with impression/click tracking, reports, notifications, multilingual category seeds).
- **Parameterized queries** consistently used — no raw concatenation of user input into SQL.
- **`api/upload.php`** is a model of secure upload handling (finfo + re-encode + random filenames + thumbnails).
- **Escaping discipline** in views is mostly correct.
- Clean separation of `config/`, `includes/`, `pages/`, `api/`, `database/`, `assets/`.
- `.gitignore` excludes uploads, sessions, env files; `.htaccess` sets sensible security headers and disables index listing.

---

## 9. Recommended remediation order

1. **Unify the data model** — commit to the schema's `reviewable_id/reviewable_type` + join-table model; fix all queries in one pass (list of mismatches in §2).
2. **Unify `Auth` usage** — add `Auth::check()`/`Auth::user()` aliases or update call sites; fix `register()` call signature; make `login()`'s return contract boolean.
3. **Pick one layout set** — port old-style pages to `pages/layouts/` (or ship Bootstrap CSS); define `renderStars()`/`generateCSRFToken()` helpers.
4. **Add missing `Database` methods** (`queryOne`, `prepare`, `lastInsertId`) or refactor call sites to `fetchOne`/`query`.
5. **Fix routing** — remove routes to missing files, add missing pages or 404 them; align `/listing/...` and `/reviews/write` links; convert slug pages to router `$id`.
6. **Fix APIs** — include config everywhere; CSRF + auth guards; correct tables/columns; JSON error paths; reuse `api/upload.php` logic for review photos.
7. **Harden sessions** — `session_regenerate_id()` on login, cookie flags, CSRF on all state-changing forms, rate limiting, fix open redirect.
8. **Polish** — remove dead code, add seed data, wire `500.php`, create `uploads/.htaccess`, add missing assets or remove references.

---

## Appendix — quick reference

- PHP files: 27 · ~5,300 lines · Schema: 28 tables · No tests · No build tooling
- **Pages that would work today if dependencies existed:** none (all hit at least one fatal from §1–§3).
- **Files worth keeping as-is:** `database/schema.sql`, `api/upload.php` (after config fix), `includes/Database.php` (after adding methods), `includes/helpers.php` (after adding 2 aliases), `.htaccess`, `assets/js/main.js` (after endpoint fixes).
