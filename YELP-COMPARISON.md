# Umma Directory vs. Yelp — How Close Are We?

*Comparison date: 2026-08-05 · Baseline: yelp.com (current) vs. this repo at `ce59158`*

---

## The verdict, in one paragraph

**Visually, you're ~80% there.** A person who knows Yelp would glance at your homepage, search results, and business pages and say "this is a Yelp clone" — red brand, red stars, Find/Near search, horizontal result cards, sponsored results, gray-on-white layout. **Functionally, you're ~45–50% of a core local-search product**, and **~15% of the full Yelp ecosystem** (transactions, community, moderation, personalization, scale). The gap isn't in how it *looks* anymore — it's in the layers of functionality and data around the core.

---

## 1. Design & visual comparison

| Yelp signature | Your site | Status |
|---|---|---|
| Red brand color (`#d32323`) | `--red: #d32323` everywhere | ✅ Match |
| Red star ratings | Red stars, half-star support | ✅ Match |
| White canvas, gray text, `#e6e6e6` borders | Same palette | ✅ Match |
| Wordmark ("yelp" in red script) | "umma!" in red with burst | ✅ Close (not script, but reads the same) |
| Display typeface (SoHo Gothic) | Archivo (close grotesque) | 🟡 Close, not identical |
| "Find / Near" search | Labeled "Find / Near" fields + red Search | ✅ Match |
| Horizontal result cards (photo left) | Same anatomy, "Open" status in green | ✅ Match |
| Sponsored results mixed into results | First sponsored card on top + one sprinkled | ✅ Match (2 ads; Yelp often shows more + "All Results" divider) |
| Category tiles with illustration art | White tiles with line icons | 🟡 Line icons vs. Yelp's colored SVG illustrations |
| Homepage photo carousel | None | ❌ |
| Homepage "Recent Activity" feed (friends' reviews) | None | ❌ |
| Homepage city/neighborhood tiles | None | ❌ |
| Map on search results (pins, redo search) | None — directions are a link to Google Maps | ❌ Big one |
| Embedded map on business page | None (link only) | ❌ Big one |
| Dense multi-column footer with sitemap | 4 light columns | 🟡 Lighter than Yelp's |
| Notifications bell in header | None | ❌ |
| "For Business" nav entry | None | ❌ |
| Photo gallery with lightbox + "add photo" | Simple 2-up gallery, no lightbox | 🟡 |
| Breadcrumb "city · category" on results | Category shown as meta text | 🟡 |

**Design score: ~8/10.** The remaining visual deltas are: map UI, photo carousel/lightbox, colored category illustrations, and Yelp's dense footer/header utilities.

---

## 2. Core local-search features

| Feature | Yelp | You | Notes |
|---|---|---|---|
| Search by keyword + location | ✅ | ✅ | No autocomplete/suggestions yet |
| Search by category | ✅ | ✅ | Via category chips + filter |
| Filters: price, rating, open-now, distance, hours | ✅ | 🟡 | Price ✅, rating ✅, **open-now filter exists but isn't applied in the API**, distance ❌, hours ❌ |
| Sort: rating, review count, name, distance | ✅ | 🟡 | rating/reviews/name ✅, distance ❌ |
| Pagination | ✅ | ✅ | ✅ |
| Business detail: hours, contact, price, website | ✅ | ✅ | ✅ |
| Green "Open / Closed" computed from hours | ✅ | 🟡 | Uses a static `is_open` flag, not computed from `opening_hours` |
| Directions / map | ✅ interactive | 🟡 link only | ❌ |
| Photo galleries (multiple, primary) | ✅ | ✅ | No upload or lightbox |
| Amenities & attributes | ✅ | ✅ | `amenities` JSON |
| Similar businesses | ✅ | ✅ | Same category |
| Reviews: write, stars, title+text | ✅ | ✅ | ✅ |
| Useful / Funny / Cool reactions | ✅ | ✅ | Toggle + counts, matches Yelp |
| Review photos | ✅ | ❌ | API for upload exists (`api/upload.php` was removed — needs re-adding); no review photo UI |
| Check-in | ✅ | ✅ | Daily cooldown; Yelp adds check-in photos + badges |
| Save / bookmark / collections | ✅ | ❌ | Schema has `favorites`; no UI, no API |
| Share listing | ✅ | ❌ | |
| Report review / listing | ✅ | ❌ | Schema has `reports`; no UI/API |
| Owner response to reviews | ✅ | 🟡 | Displayed if present; no UI for owners to write |
| Business claiming | ✅ | ❌ | Schema has `is_claimed`; no flow |
| Quote requests (home services) | ✅ | ❌ | Old `api/quotes.php` removed in the rebuild; schema has no table for it |
| Booking / appointments | ✅ | ❌ | |
| Deals / offers / gift cards | ✅ | ❌ | |

**Core score: ~5/10.** The search→browse→detail→review loop is complete and solid. The big functional holes: **map UI, bookmarks/collections, review photos, open-now filter actually working, owner/claim flow.**

---

## 3. Account & community

| Feature | Yelp | You | Notes |
|---|---|---|---|
| Register / login / logout | ✅ | ✅ | Sessions + CSRF |
| Password reset | ✅ | ❌ | `Auth::requestPasswordReset()` removed in rebuild; no page/API |
| Email verification | ✅ | ❌ | |
| Social / OAuth login | ✅ | ❌ | |
| Profile: stats, avatar | ✅ | ✅ | Stats ✅, avatar stored but no upload UI |
| My reviews | ✅ | ✅ | ✅ |
| My photos / check-ins / saved | ✅ | ❌ | |
| Badges & contributor levels | ✅ | 🟡 | Columns exist, not surfaced in UI |
| Friends / follow | ✅ | ❌ | |
| Activity feed (Recent Activity) | ✅ | ❌ | |
| Notifications (in-app) | ✅ | ❌ | Schema table exists |
| Private messages | ✅ | ❌ | Schema table exists |
| Admin moderation (reports, flags, content) | ✅ | ❌ | Schema tables exist; only ad management is admin-capable |
| Multilingual (EN/SW/AR) | ✅ (Yelp is multilingual) | ❌ | Schema has `name_sw`/`name_ar` |

**Account/community score: ~2.5/10.**

---

## 4. Vertical-specific features

| Feature | Yelp | You |
|---|---|---|
| Mosques + **prayer times** (computed locally) | — (Yelp has no prayer times) | ✅ **You beat Yelp here** — real differentiator |
| Mosque facilities, imam, capacity | — | ✅ |
| Fundis: skills, portfolio, rates, experience | ✅ (home services) | ✅ |
| Fundi quote request | ✅ | ❌ |
| Charities & donation campaigns (M-Pesa/PayPal/Stripe) | — | ❌ Schema complete, no code |
| Ads: sponsored listings, impressions, clicks, admin | ✅ | ✅ Full loop built |
| Ad targeting (category/city), reporting UI | ✅ | ❌ |

---

## 5. Platform & data

| Dimension | Yelp | You |
|---|---|---|
| Scale | ~200M reviews, tens of millions of listings | 6 businesses · 4 mosques · 3 fundis · 13 reviews · 5 ads (demo seed) |
| Real photos | Millions, user-generated | SVG placeholder illustrations |
| Real user content | Global community | Seed data only |
| Mobile | Native apps + PWA | Responsive web only; no manifest/service worker |
| SEO | Full server-rendered, every page indexable | Static shells; content rendered client-side → business/mosque/fundi pages return empty HTML to crawlers |
| Performance | CDN'd, edge-cached | Fine at this scale, no caching layer |
| Payments | Reservations, delivery, deals | None (MPESA/PayPal config constants only) |

---

## 6. What to build next (priority order)

**Tier 1 — ✅ DONE (2026-08-05):**
1. ✅ **Map view** on search results + embedded map on detail pages (Leaflet + OpenStreetMap)
2. ✅ **Bookmarks / collections** (`favorites`) — save buttons on cards + detail, "Saved" on profile
3. ✅ **Review photos** — secure `api/upload.php` + file input in review modal
4. ✅ **Open-now filter** — computed live from `opening_hours`
5. ✅ **Password reset** — forgot/reset pages + API

**Tier 2 — ✅ DONE (2026-08-05):**
6. ✅ **Owner dashboard** — claim, edit, respond to reviews, pending counts + stats
7. ✅ **Search autocomplete** — debounced suggestions (businesses/mosques/fundis/categories)
8. ✅ **Report review/listing** UI → `reports` table
9. ✅ **Badges & contributor levels** on reviews and profiles
10. ✅ **Homepage "Recent Activity" feed**

**Tier 3 — in progress:**
11. ✅ **Charities & donations** — campaigns, progress, M-Pesa STK push (Daraja, sandbox-able), PayPal, bank
12. ✅ **Fundi quote requests** — stored + delivered via **WhatsApp** (wa.me pre-filled links; messaging is WhatsApp-only by design)
13. Notifications
14. Admin content moderation console
15. Multilingual EN/SW/AR
16. PWA (manifest + service worker) for mobile "app" feel
17. SEO pass (server-render meta/JSON-LD per listing, or pre-render)

---

## Bottom line

| Dimension | Score |
|---|---|
| Visual design | **8 / 10** — reads as "Yelp" at a glance |
| Core local-search functionality | **5 / 10** — the loop works; map, saves, photos, owner flow missing |
| Community & accounts | **2.5 / 10** |
| Vertical depth (mosques/fundis) | **7 / 10** — prayer times are a genuine differentiator Yelp doesn't have |
| Data & scale | **1 / 10** — demo seed; real content comes from real users |
| **Overall "are we there yet?"** | **~50% of a Yelp-like product; ~90% of the Yelp *look*.** |

The look is the easy 10%. The remaining 50% is: map + saves + photos + owner/community tooling + real data. Tier 1 (≈3–4 days of work) closes most of the visible gaps.
