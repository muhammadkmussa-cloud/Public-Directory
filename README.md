# Umma Directory

A Yelp-style directory for the Muslim community — **halal businesses, mosques with prayer times, and trusted fundis (skilled workers)** — with reviews, ratings, check-ins and search.

## Architecture

```
┌─────────────────────────────┐      fetch()/JSON       ┌──────────────────────────────┐
│  FRONTEND (static)          │ ──────────────────────▶ │  BACKEND (PHP 7.4+/MySQL)    │
│  HTML · CSS · vanilla JS    │ ◀────────────────────── │  /api/*.php  — REST-style JSON│
│  No server-side rendering   │    JSON responses       │  PDO prepared statements     │
└─────────────────────────────┘                         └──────────────────────────────┘
```

- **Frontend** — plain HTML files at the site root (`index.html`, `businesses.html`, `business.html`, …) plus shared `assets/css/style.css`, `assets/js/app.js` and one script per page in `assets/js/pages/`. No build step, no framework.
- **Backend** — PHP endpoints in `api/` that read/write MySQL. Sessions + CSRF for authentication. The frontend falls back to bundled sample data (`assets/js/mock.js`) if the API can't be reached — useful for previewing the design before the backend is installed.

## Project structure

```
├── index.html / businesses.html / business.html / mosques.html / mosque.html
│   fundis.html / fundi.html / login.html / register.html / profile.html / 404.html
├── assets/
│   ├── css/style.css            # all styles (Yelp-inspired, responsive)
│   ├── js/
│   │   ├── app.js               # API client, auth state, layout, card renderers
│   │   ├── mock.js              # sample data used only when the API is offline
│   │   └── pages/*.js           # one script per page
│   └── img/                     # favicon + sample placeholder images
├── api/
│   ├── _bootstrap.php           # sessions, JSON helpers, CSRF, error handling
│   ├── auth.php                 # register / login / logout / me
│   ├── businesses.php           # list, search, filters, detail, featured
│   ├── mosques.php              # list, detail, prayer times (computed locally)
│   ├── fundis.php               # list, detail, portfolio
│   ├── reviews.php              # create / helpful / delete / my reviews
│   ├── checkin.php              # check-ins (businesses & mosques)
│   ├── categories.php           # category list
│   └── csrf.php                 # CSRF token endpoint
├── config/config.php            # ← EDIT: database credentials + app settings
├── includes/                    # Database, Auth, PrayerTimes classes
├── database/
│   ├── schema.sql               # full schema (28 tables) + base seed data
│   ├── seed.sql                 # sample businesses, mosques, fundis, reviews
│   └── install.php              # one-command installer
└── uploads/.htaccess            # blocks PHP execution in uploads
```

## Installation (DirectAdmin / shared hosting)

1. Upload the project to your web root (e.g. `public_html`).
2. Create a MySQL database and user in your control panel.
3. Edit `config/config.php` — set `DB_USER`, `DB_PASS`, `DB_NAME` (and `APP_ENV`).
4. Run the installer **once**:
   - SSH: `php database/install.php`
   - or web: open `https://yourdomain.com/database/install.php`
5. **Delete `database/install.php` (and `database/install.lock`) from the server.**
6. Done. Open your domain — the frontend will talk to `/api/*` automatically.

The installer creates the schema, loads sample data, and creates demo accounts:

| Role | Login | Password |
|---|---|---|
| Admin | `admin@example.com` | `Admin@123` |
| User | `demo@example.com` | `Demo@123` |

## Local preview without PHP

Open any `.html` file directly (or run `python3 -m http.server 8000`). The site detects that the API is unreachable and shows sample data with a small banner — perfect for previewing the design. Install the backend (above) for live data.

## API reference (quick)

All responses: `{"success": true, "data": ...}` or `{"success": false, "error": "..."}`.

| Endpoint | Method | Purpose |
|---|---|---|
| `api/auth.php?action=login` | POST | `{identifier, password}` |
| `api/auth.php?action=register` | POST | `{username, email, password, full_name, phone}` |
| `api/auth.php?action=logout` | POST | ends session |
| `api/auth.php?action=me` | GET | current user (or `data: null`) |
| `api/auth.php?action=forgot` | POST | `{email}` — sends reset link (returns token in dev mode) |
| `api/auth.php?action=reset` | POST | `{token, password}` — sets new password |
| `api/csrf.php` | GET | `{csrf_token}` — send as `X-CSRF-Token` header |
| `api/businesses.php` | GET | `?q=&location=&category=&price=&min_rating=&sort=&page=` |
| `api/businesses.php?id=N` | GET | detail + photos + reviews + similar |
| `api/businesses.php?featured=1` | GET | homepage set |
| `api/mosques.php` / `?id=N` / `?top=1` | GET | list / detail (+prayer times) |
| `api/fundis.php` / `?id=N` / `?top=1` | GET | list / detail (+portfolio) |
| `api/categories.php?type=` | GET | category chips |
| `api/reviews.php?action=mine` | GET | your reviews (login) |
| `api/reviews.php` | POST | `{action: create\|react\|delete, ...}` — react: `{reaction: useful\|funny\|cool}` (login + CSRF) |
| `api/checkin.php` | POST | `{checkinable_id, checkinable_type, note}` (login + CSRF) |
| `api/ads.php?placement=` | GET | active ads for a placement (`search_results`, `homepage_header`, `detail_page`, …) |
| `api/ads.php` | POST | `{action: impression\|click, ad_id}` — track views/clicks (CSRF) |
| `api/ads.php` | POST | `{action: create\|toggle\|delete, ...}` — manage ads (admin, CSRF) |
| `api/ads.php?action=list` | GET | all ads + stats (admin) |
| `api/favorites.php?action=mine` | GET | your saved listings (login) |
| `api/favorites.php?action=status` | GET | `?favoritable_id=&favoritable_type=` → `{saved}` (login) |
| `api/favorites.php` | POST | `{action: toggle, favoritable_id, favoritable_type}` (login + CSRF) |
| `api/upload.php` | POST | multipart `image` + `dir` → secure upload (login + CSRF) |
| `api/suggest.php?q=` | GET | autocomplete suggestions (businesses/mosques/fundis/categories) |
| `api/activity.php` | GET | recent reviews feed for the homepage |
| `api/reports.php` | POST | `{action: create, reportable_id, reportable_type, reason, description}` (login + CSRF) |
| `api/businesses.php?action=mine` | GET | your claimed listings + pending responses (login) |
| `api/businesses.php` | POST | `{action: claim\|respond\|update, ...}` — owner tools (login + CSRF) |

State-changing endpoints require login and the CSRF header (`X-CSRF-Token`), which the frontend attaches automatically.

## Security notes

- All SQL uses PDO prepared statements.
- Passwords hashed with bcrypt (`HASH_COST` in config).
- Sessions: `httponly` + `SameSite=Lax` cookies, regenerated on login.
- CSRF enforced on every state-changing API call.
- Uploads folder blocks PHP execution via `.htaccess`.
- Review reactions (Useful / Funny / Cool) are stored per-user per-type in `review_helpful` (toggle).
- Sample image files in `assets/img/sample/` are placeholders — replace with real photos in production (uploaded files go to `uploads/`, which is git-ignored).

## Built

- **Owner dashboard** (`dashboard.html`) — claim listings, edit your business
  (name, contact, price, descriptions), respond to customer reviews, see
  pending responses + per-listing stats (rating, check-ins).
- **Search autocomplete** — debounced suggestions on the homepage search and
  the businesses search (businesses, mosques, fundis, categories).
- **Report & moderation** — flag reviews or listings with a reason; stored in
  the `reports` table for admin review.
- **Badges & contributor levels** — verification badges (verified / premium /
  top contributor) and levels shown on reviews and profiles.
- **Recent Activity feed** on the homepage — the latest community reviews.
- **Map view** — Leaflet + OpenStreetMap: interactive map on search results
  (List/Map toggle with pins + popups) and embedded location maps on business,
  mosque and fundi pages.
- **Bookmarks** — save/unsave any listing (cards + detail pages), "Saved"
  section on your profile.
- **Review photos** — secure upload via `api/upload.php` (MIME sniff + GD
  re-encode + thumbnails), attached to reviews and displayed in review cards.
- **Open-now filter** — computed live from each business's `opening_hours`.
- **Password reset** — forgot/reset pages + API (token expiry, session
  invalidation; dev mode returns the link for testing without SMTP).
- **Sponsored ads** — Yelp-style sponsored results interleaved into search results,
  homepage banner strip, and detail-page sidebar; impression & click tracking;
  `admin.html` (admin-only) to create, pause and delete ads.
- Business claiming / owner dashboard (schema supports it)
- Photo uploads through `api/upload.php` (secure handler, ready to wire in)
- Multilingual UI (EN / SW / AR)
