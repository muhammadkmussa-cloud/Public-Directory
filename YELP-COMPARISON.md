# Ummah Directory vs. Yelp — How Close Are We?

*Comparison date: 2026-08-07 · Baseline: yelp.com (current) vs. this repo at `102c784` (features re-verified file-by-file; §1–§4 statuses refreshed 2026-08-07 after the gap-closing work below)*

---

## The verdict, in one paragraph

**Visually, you're ~70% there.** A person who knows Yelp would glance at your homepage, search results, and business pages and say "this is a Yelp clone" — the same Find/Near search, horizontal result cards, sponsored results, gray-on-white layout, wordmark and stars. **But your brand is now GREEN (`#14F528`), not Yelp red** (changed in the `red-green` commit) — a deliberate identity choice that moves you a step away from a straight clone. **Functionally, you're ~60% of a core local-search + vertical product**, and **~20% of the full Yelp ecosystem** (transactions, community network effects, moderation at scale, personalization). The core loop — search → browse → detail → review → react — is complete and verified working end-to-end. What remains is the *layers*: real users, real data, server-side SEO, native apps, transactions, and community features.

---

## 1. Design & visual comparison

| Yelp signature | Your site | Status |
|---|---|---|
| Red brand color (`#d32323`) | `--brand: #14F528` — **green** (recent change) | 🟡 Deliberate deviation; a green clone reads less "Yelp" |
| Red star ratings | Stars use `--brand`, so they render green too | 🟡 |
| White canvas, gray text, `#e6e6e6` borders | Same palette | ✅ Match |
| Wordmark ("yelp" in red script) | "ummah!" wordmark + burst + PNG logo | ✅ Close |
| Display typeface (SoHo Gothic) | Archivo (close grotesque) | 🟡 Close, not identical |
| "Find / Near" search | "Find / Near" fields + brand-colored Search | ✅ Match |
| Horizontal result cards (photo left) | Same anatomy, "Open" status in green | ✅ Match |
| Sponsored results mixed into results | First sponsored card on top + one sprinkled (frontend-interleaved) | ✅ Match (2 ads; Yelp often shows more) |
| Category tiles with illustration art | White tiles with line icons | ✅ **2026-08-07** — colored SVG tiles (`assets/img/cats/*.svg`, fallback to line icons for unmapped slugs) |
| Homepage photo carousel | None | ✅ **2026-08-07** — hero carousel (swipe, auto-advance, `prefers-reduced-motion`, ARIA) |
| Homepage "Recent Activity" feed | ✅ `#recentActivity` fed by `api/activity.php` | ✅ Match |
| Homepage city/neighborhood tiles | None | ❌ |
| Map on search results (pins, redo search) | ✅ Leaflet + OSM, List/Map toggle, Near-me blue-dot pin | ✅ Match |
| Embedded map on business page | ✅ | ✅ Match |
| Dense multi-column footer with sitemap | 4 light columns | ✅ **2026-08-07** — 5-column grid + "Browse:" category strip + bottom bar |
| Notifications bell in header | ✅ bell + unread badge + dropdown + page | ✅ Match |
| "For Business" nav entry | ❌ (only footer "Advertise" → `admin.html`) | ✅ **2026-08-07** — header link + `business-claim.html` (feature cards, claim form, owner panel) |
| Photo gallery with lightbox + "add photo" | Simple 2-up gallery, upload ✅, **no lightbox** | ✅ **2026-08-07** — full lightbox module (keyboard, close, prev/next) |
| Breadcrumb "city · category" on results | Category shown as meta text | ✅ **2026-08-07** — `#breadcrumb` on results page, slug→name mapping |

**Design score: ~8.5/10.** The green brand is the main visual delta from Yelp. Remaining gaps: city/neighborhood tiles (only).

---

## 2. Core local-search features

| Feature | Yelp | You | Notes |
|---|---|---|---|
| Search by keyword + location | ✅ | ✅ | Autocomplete ✅ (debounced, `api/suggest.php`) |
| Search by category | ✅ | ✅ | Via category chips + filter |
| Filters: price, rating, open-now, distance, hours | ✅ | ✅ | Price ✅, rating ✅, distance ✅ (lat/lng/radius + "Near me"), **open-now ✅** (`is_open_now()` overnight/multi-range aware, PHP post-filter), **hours ✅** (`hours=open` param + results-page checkbox) |
| Sort: rating, review count, name, distance | ✅ | ✅ | "📍 Near me" (geolocation) + radius filter + km badges on cards |
| Pagination | ✅ | ✅ | |
| Business detail: hours, contact, price, website | ✅ | ✅ | |
| Green "Open / Closed" computed from hours | ✅ | ✅ | `is_open_now()` handles overnight (`18:00 - 02:00`), multi-interval days, and closed/missing hours |
| Directions / map | ✅ interactive | ✅ Leaflet + OSM on businesses/mosques/fundis (list + detail), List/Map toggle, "Near me" pin | |
| Photo galleries (multiple, primary) | ✅ | ✅ | Upload ✅, lightbox ✅ |
| Amenities & attributes | ✅ | ✅ | `amenities` JSON + `attributes` table |
| Similar businesses | ✅ | ✅ | Same category |
| Reviews: write, stars, title+text | ✅ | ✅ | **Visit-date field ✅** (Yelp-style "when did you visit?") |
| Useful / Funny / Cool reactions | ✅ | ✅ | Toggle + counts, matches Yelp |
| Review photos | ✅ | ✅ | `api/upload.php` (MIME sniff + GD re-encode + thumbnails) + file input + display |
| Check-in | ✅ | ✅ | Daily cooldown; Yelp adds check-in photos + badges |
| Save / bookmark / collections | ✅ | ✅ | Save buttons on cards + detail, "Saved" on profile |
| Share listing | ✅ | ✅ | Web Share API + fallback modal (WhatsApp/X/Facebook/Email/copy), OG tags |
| Report review / listing | ✅ | ✅ | `api/reports.php` + moderation console |
| Owner response to reviews | ✅ | ✅ | Owner dashboard |
| Business claiming | ✅ | ✅ | Claim approval workflow: pending → admin approves/rejects in the admin console (`claims` audit table, `claim_status`); edit/respond gated on `approved` — see `SECURITY-HARDENING.md` #2 |
| Quote requests (home services) | ✅ (paid) | ✅ | Fundis → stored + WhatsApp `wa.me` pre-filled link (free) |
| Menu / dish-level search (restaurants) | ✅ | ❌ | N/A for your verticals |
| Booking / appointments | ✅ | ❌ | |
| Deals / offers / gift cards | ✅ | ❌ | |

**Core score: ~8/10.** The search→browse→detail→review loop is complete and solid. Remaining functional holes: menu/booking/deals (N/A for your verticals).

---

## 3. Account & community

| Feature | Yelp | You | Notes |
|---|---|---|---|
| Register / login / logout | ✅ | ✅ | Sessions + CSRF; login lockout ✅ (persistent per-account, see `SECURITY-HARDENING.md` #4) |
| Password reset | ✅ | ✅ | forgot/reset pages + API (token expiry, session revocation) |
| Email verification | ✅ | ✅ | One-time link on register, `verify.html`, resend, unverified banner (soft gate) |
| Social / OAuth login | ✅ | ✅ | Google via server-side OAuth (`api/oauth.php`, `oauth_links`); dev-mode simulated flow until keys are set |
| Profile: stats, avatar | ✅ | ✅ | Stats ✅; avatar upload UI ✅ (`api/auth.php?action=update` + in-place CSRF, reuses `users.profile_photo`) |
| My reviews | ✅ | ✅ | |
| My photos / check-ins / saved | ✅ | 🟡 | Saved ✅; check-ins shown on profile ✅; photos ❌ |
| Badges & contributor levels | ✅ | 🟡 | Columns + display on reviews/profiles, lightly surfaced |
| Friends / follow | ✅ | ❌ | |
| Activity feed (Recent Activity) | ✅ | ✅ | Homepage feed from `api/activity.php` |
| Notifications (in-app) | ✅ | ✅ | Bell + dropdown + page; auto-created on reviews/donations/claims; **no email/SMS/push** |
| Private messages | ✅ | ❌ | `messages` table exists but is unused — messaging is WhatsApp-only by design |
| Admin moderation (reports, flags, content) | ✅ | ✅ | Report queue + resolve/reject; ads management in `admin.html` |
| Multilingual (EN/SW/AR) | ✅ | 🟡 | Switcher + Arabic RTL ✅, but only ~24 UI strings translated — most page content stays English |
| Emergency numbers / quick-dial | 🟡 | ✅ **2026-08-07** | `api/emergency.php` (city/category-filterable, active-only) + `#emergencyBtn` dropdown in header — **no login required**, mirrors real emergency UX |

**Account/community score: ~5/10.**

---

## 4. Vertical-specific features

| Feature | Yelp | You |
|---|---|---|
| Mosques + **prayer times** (computed locally) | — (Yelp has no prayer times) | ✅ **You beat Yelp here** — real differentiator |
| Mosque facilities, imam, capacity | — | ✅ |
| Fundis: skills, portfolio, rates, experience | ✅ (home services) | ✅ |
| Fundi quote request | ✅ (paid) | ✅ (free, via WhatsApp) |
| Charities & donation campaigns (M-Pesa/PayPal/bank) | — | ✅ Schema complete + API + UI; **M-Pesa runs simulated until creds are set**, PayPal is sandbox by default |
| Ads: sponsored listings, impressions, clicks, admin | ✅ | ✅ Full loop — interleaving is **frontend-only**, and there's **no reporting UI** |
| Ad targeting (category/city) + reporting | ✅ | 🟡 Targeting ✅ **2026-08-07** (`target_category`/`target_city` columns, `category`/`city` serve params, admin form + live list filter, 422 on unknown targets); **reporting still ❌** |

---

## 5. Platform & data

| Dimension | Yelp | You |
|---|---|---|
| Scale | ~200M reviews, tens of millions of listings | 30 businesses · 14 mosques · 11 fundis · 30+ reviews · 5 ads (demo seed) |
| Real photos | Millions, user-generated | SVG placeholder illustrations + user uploads |
| Real user content | Global community | Seed data only |
| Mobile | Native apps (iOS/Android) | Responsive web + **PWA** — manifest + service worker on all 20 pages (installable, offline app-shell) |
| SEO | Full server-rendered, every page indexable | **All static shells, 100% client-side render** → crawlers see empty HTML; JSON-LD injected via JS on 3 of 4 detail types (**charity has none**); `og:` tags are generic defaults, no `og:image` in the static HTML |
| Performance | CDN'd, edge-cached | No caching layer; fine at this scale |
| Payments | Reservations, delivery, deals | M-Pesa/PayPal config constants; **simulated by default** |

---

## 6. Security & hardening findings (2026-08-06)

Full remediation plan: **`SECURITY-HARDENING.md`**.

| # | Finding | Where | Severity |
|---|---|---|---|
| 1 | **IDOR** — when not logged in, anyone can read any donation's status/amount/payment method; ownership is only checked for authenticated users | `api/donations.php:180-199` | ✅ **Fixed 2026-08-06** — now requires login + strict ownership; guests and anonymous-account donations get 401/403 |
| 2 | **Claim squatting / impersonation** — any logged-in user can instantly claim any unclaimed business; no ownership proof, no approval step | `api/businesses.php:20-33` | ✅ **Fixed 2026-08-06** — pending claim → admin approval (`claims` table, `claim_status`); edit/respond gated on `approved`; admin console at `admin.html` |
| 3 | **No rate limit** on claim/respond/update (`businesses.php` POST), report create/resolve (`reports.php` POST), or logout | `api/businesses.php`, `api/reports.php`, `api/auth.php` | ✅ **Fixed 2026-08-06** — `rate_limit('biz_write',30,300)`, `rate_limit('report_create',10,900)`, `rate_limit('report_admin',60,300)`, `rate_limit('logout',60,300)` |
| 4 | **Login lockout bypassable** — the 10-fail counter lives in `$_SESSION`, so clearing cookies/incognito resets it; only the per-IP limiter is persistent | `includes/Auth.php:154-178` | ✅ **Fixed 2026-08-06** — persistent per-account lockout via `rate_limit` with IP-agnostic bucket; 10 fails → 15 min block survives cookie clear / IP switch |
| 5 | **Insecure shipped defaults** — `DB root/root`, `APP_ENV=development` (echoes reset/verify tokens, `display_errors` on), `database/install.lock` left in the repo | `config/config.php:10-18`, `database/install.lock` | 🔴 High (pre-deploy) |
| 6 | **Stored XSS depends on frontend escaping only** — API stores/returns review, business and owner-response content verbatim (`json_encode` without HTML-escaping); safe only while every renderer escapes | `api/_bootstrap.php:91` | ✅ **Fixed 2026-08-06** — server-side `sanitize_text()`/`sanitize_line()` applied to all free-text inputs (reviews, businesses, reports, quotes, ads, donations, checkins, registration); `escapeHtml()` retained client-side |
| 7 | **M-Pesa callback is unauthenticated** (standard for Daraja, but a spoofed `CheckoutRequestID` + `ResultCode=0` marks a pending donation completed); **PayPal sandbox auto-accepts** failed IPN verification | `api/donations.php:45-100, 127-138` | ✅ **Fixed 2026-08-06** — M-Pesa validates callback amount vs stored amount (±0.01); PayPal removes sandbox auto-accept, requires genuine VERIFIED |
| 8 | **`open_now` correctness** — naive `HH:MM` string comparison; overnight and multi-interval hours mis-evaluated | `api/businesses.php:259-269` | ✅ **Fixed 2026-08-06** — PHP `is_open_now()` parser handles overnight (end<start), multi-range (comma-separated), and closed/missing hours; SQL filter replaced with PHP post-filter |

---

## 7. What to build next (priority order)

**✅ Done & verified (2026-08-06):** maps everywhere (Leaflet/OSM, List/Map toggle, Near-me pin), distance search + sort, email verification, Google OAuth, share (Web Share + modal + OG), review photos, open-now (partial), password reset, owner dashboard (claim/edit/respond), search autocomplete, report + moderation console, badges & contributor levels, homepage Recent Activity, charities & donations, fundi quote requests (WhatsApp), notifications, multilingual (partial), PWA (all 20 pages), JSON-LD (partial), sponsored ads (impressions/clicks/admin), security headers/CSRF/rate-limiting (partial), seed expansion.

**Tier A — Security (see `SECURITY-HARDENING.md`):** IDOR fix ✅, claim approval flow ✅, missing rate limits ✅, persistent lockout ✅, prod-config guard ✅, server-side sanitizer ✅, M-Pesa/PayPal hardening ✅, open_now parser ✅.

**Tier B — SEO & discovery (biggest visibility gap):** server-side render / pre-render the detail pages (crawlers currently see empty HTML), real `og:image` + per-page `og:` tags, charity JSON-LD.

**Tier C — Feature polish:**
1. Photo lightbox + homepage photo carousel — ✅ **2026-08-07**
2. Hours filter + fix open-now overnight/multi-range handling — ✅ **2026-08-07**
3. Review visit-date field — ✅ **2026-08-07**
4. Profile avatar upload UI — ✅ **2026-08-07**
5. Emergency numbers API + quick-dial UI — ✅ **2026-08-07**
6. "For Business" nav entry + claim approval workflow — ✅ **2026-08-07** (nav + claim page landed this round; approval workflow was already fixed 2026-08-06)
7. Broaden i18n coverage (full dictionary, not ~24 strings) — next up
8. Ad targeting done ✅; **reporting dashboard** still open
9. Homepage city/neighborhood tiles — open
10. Native mobile app (Flutter/React Native) — long term

---

## Bottom line

| Dimension | Score |
|---|---|
| Visual design | **8.5 / 10** — reads "Yelp" minus the red |
| Core local-search functionality | **8 / 10** — the loop works end-to-end |
| Community & accounts | **6 / 10** |
| Vertical depth (mosques/fundis/charities) | **8 / 10** — prayer times + WhatsApp quotes are genuine differentiators |
| Platform & tech | **5 / 10** — solid PWA; no SSR, no native app |
| Data & scale | **1 / 10** — demo seed; real content comes from real users |
| **Overall "are we there yet?"** | **~65% of a Yelp-like product; ~20% of the full Yelp ecosystem.** |

The look is the easy part. The remaining 35% is security hardening, SSR/SEO, community network effects, transactions, and real data.
