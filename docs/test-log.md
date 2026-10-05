# Test Log – Local Tourist Day-Visit Planner (Mattegoda)

ITE2953 · J A S R Perera (E2410994)

Environment: Windows 11, XAMPP 8.2.12 (Apache 2.4.58, PHP 8.2.12, MariaDB 10.4.32 on port 3307), Google Chrome.

## Security tests

| Test ID | Requirement | Scenario | Steps | Expected | Actual | Status | Date | Evidence |
|---|---|---|---|---|---|---|---|---|
| TC-S01 | §5.3 Security | Direct browser access to the config folder | Open http://localhost/mattegoda-day-planner/config/ | 403 Forbidden | 403 Forbidden | Pass | 28 Sep 2026 | test-evidence/TC-S01.png |
| TC-S02 | Security | Directory listing disabled | Open http://localhost/mattegoda-day-planner/api/ | 403 Forbidden (no file list) | 403 Forbidden | Pass | 28 Sep 2026 | test-evidence/TC-S02.png |
| TC-S03 | NFR-13 | Scripts cannot run from the uploads folder | Create uploads/places/test.php (echo 'RAN'); request it in the browser; delete the file | 403 Forbidden; "RAN" never shown | 403 Forbidden (Apache/2.4.58); "RAN" not shown | Pass | 4 Oct 2026 | test-evidence/TC-S03.png |

## Database tests

| Test ID | Requirement | Scenario | Steps | Expected | Actual | Status | Date | Evidence |
|---|---|---|---|---|---|---|---|---|
| TC-DB-01 | BR-02 | Duplicate category name | Insert category 'natural' twice | Second insert rejected | #1062 Duplicate entry 'natural' for key 'uq_category_name' | Pass | 30 Sep 2026 | test-evidence/TC-DB-01.png |
| TC-DB-02 | FR-08, data integrity | Link a category to a place that does not exist | Insert place_category (999, 1, 1) | Rejected by foreign key | #1452 Cannot add or update a child row (fk_pc_place) | Pass | 30 Sep 2026 | test-evidence/TC-DB-02.png |
| TC-DB-03 | FR-54 | Delete a category still used by a place | Insert a place, link it to category 1, delete category 1 | Delete rejected | #1451 Cannot delete or update a parent row (fk_pc_category) | Pass | 30 Sep 2026 | test-evidence/TC-DB-03.png |
| TC-DB-04 | NFR-07, FR-53 | Last-updated time maintained automatically | Read the place row, update its name, read it again | updated_at changes; created_at stays the same | 01:06:22 → 01:07:02; created_at unchanged | Pass | 30 Sep 2026 | test-evidence/TC-DB-04a.png, test-evidence/TC-DB-04b.png |
| TC-DB-05 | FR-08, BR-03 | Dual-category places stored once, linked twice | Run the place/category JOIN query after seeding | 16 rows; Mount Lavinia and Viharamahadevi appear under 2 categories each | 16 rows as expected | Pass | 2 Oct 2026 | test-evidence/TC-DB-05a.png, test-evidence/TC-DB-05b.png |

## Foundation tests

| Test ID | Requirement | Scenario | Steps | Expected | Actual | Status | Date | Evidence |
|---|---|---|---|---|---|---|---|---|
| TC-L01 | NFR-20, NFR-21 | Shared layout renders and connects through the Database class | Open home page | Navbar, footer at bottom, title "Places \| Mattegoda Day Planner", "14 active places" | Layout rendered; "14 active places in the database."; tab title correct | Pass | 4 Oct 2026 | test-evidence/TC-L01.png |
| TC-L02 | NFR-17, NFR-18 | Layout is responsive from 320 px to 1920 px | DevTools device mode: iPhone 12 Pro (open ☰), 320 px, 1920 px; check computed font-size | Menu collapses and opens; no horizontal scroll at 320 px; body text 16 px | ☰ menu opened; no horizontal scroll at 320 px; computed font-size 16px | Pass | 4 Oct 2026 | test-evidence/TC-L02a.png, test-evidence/TC-L02b.png, test-evidence/TC-L02c.png, test-evidence/TC-L02d.png |
| TC-L03 | §5.3 Security | Internal folders cannot be opened in a browser | Request /config/config.php, /includes/header.php, /src/Database.php | 403 Forbidden for all three; home page still works | 403 Forbidden ×3; home page loaded normally | Pass | 4 Oct 2026 | test-evidence/TC-L03a.png, test-evidence/TC-L03b.png, test-evidence/TC-L03c.png |
| TC-L04 | NFR-24 | Database failure is logged and hidden from users | Set DB_PASS='wrong', APP_DEBUG=false; reload; inspect logs/app.log; restore values | Generic "Sorry…" message, no paths or SQL; log line with timestamp and PDOException 1045 | "Sorry, something went wrong…" only; log: [2026-10-04 00:26:30] PDOException: SQLSTATE[HY000] [1045] Access denied | Pass | 4 Oct 2026 | test-evidence/TC-L04a.png, test-evidence/TC-L04b.png |
| TC-L05 | FR-51 | Inactive places are excluded from visitor pages | phpMyAdmin → place → Browse → Edit Colombo Lotus Tower, set is_active=0, Go; reload; Edit again, set is_active=1 | Count drops from 14 to 13, then returns to 14 | 13 shown, then 14 after restore | Pass | 4 Oct 2026 | test-evidence/TC-L05.png |

## Catalogue tests (UI-01)

| Test ID | Requirement | Scenario | Steps | Expected | Actual | Status | Date | Evidence |
|---|---|---|---|---|---|---|---|---|
| TC-001 | FR-01 | All active places are listed | Open home page; count cards on page 1 and page 2 | 14 cards in total (12 + 2) | Page 1: 12 cards; page 2: 2 cards (Viharamahadevi Park, Colombo Lotus Tower); total 14 | Pass | 5 Oct 2026 | test-evidence/TC-001a.png, test-evidence/TC-001b.png |
| TC-002 | FR-02 | Each card shows name, all categories, thumbnail, distance and summary | Inspect Gammanaya and Mount Lavinia Beach cards | All five items on each card; dual-category place shows both badges; placeholder when no photo | Gammanaya: Dining badge, placeholder image, 3.0 km, summary. Mount Lavinia: photo, Natural + Recreational badges, 15.0 km, summary | Pass | 5 Oct 2026 | test-evidence/TC-002a.png, test-evidence/TC-002b.png |
| TC-003 | FR-03 | List can be sorted three ways; invalid sort is ignored | Select Farthest first, Name A–Z, go to page 2 under Name; open index.php?sort=DROP | Farthest: Lotus Tower first. Name: Apē Gama first. Sort kept across pages. Invalid value falls back to nearest first | Lotus Tower first; Apē Gama first; page 2 under Name shows Mount Lavinia and Viharamahadevi with sort kept; ?sort=DROP showed Gammanaya first, no error | Pass | 5 Oct 2026 | test-evidence/TC-003a.png, test-evidence/TC-003b.png, test-evidence/TC-003c.png, test-evidence/TC-003d.png |
| TC-004 | FR-04 | Pages of 12 with navigation; out-of-range page numbers handled | Open ?page=2, ?page=99, ?page=abc | Page 2: 2 cards. 99 shows last page. abc shows page 1. No errors | ?page=99 showed page 2; ?page=abc showed page 1; no errors | Pass | 5 Oct 2026 | test-evidence/TC-004a.png, test-evidence/TC-004b.png |
| TC-005 | FR-05 | Empty list shows message and clear control | SQL: set is_active = 0 for all (updated_at kept); reload; click Clear all filters; restore is_active = 1 | "No places found" with Clear all filters button; 14 places return after restore | Message and button shown, no cards or pagination; 14 places shown after restore | Pass | 5 Oct 2026 | test-evidence/TC-005a.png, test-evidence/TC-005b.png |
