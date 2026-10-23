# Local Tourist Day-Visit Planner and Information System (Mattegoda)

ITE2953 Programming Group Project · University of Moratuwa (BIT) · J A S R Perera (E2410994)

A web application that helps visitors discover places of interest within 25 km of Salgas Junction,
Mattegoda, filter and search them, see them on a map, and build a one-day visit plan with road routing,
arrival times and opening-hour warnings. Administrators maintain the place records.

## Main features

- **Visitors:** place list with sorting, paging, category filters and search; place details with photos,
  opening hours and nearby places; map with coloured markers; day planner with reordering, start time and
  visit date, road route (OSRM), time budget, late-finish and closed-day warnings; save a plan with a
  reference code; printable plan.
- **Administrators:** secure login (hashed passwords, lockout after 5 failures, 30-minute idle timeout,
  CSRF tokens); list, add, edit and soft-delete places with a 25 km road-distance check.

## Technology

HTML, CSS, Bootstrap 5.3, JavaScript, Leaflet 1.9 with OpenStreetMap tiles, OSRM routing ·
PHP 8.2 · MariaDB / MySQL · Apache (tested on XAMPP 8.2.12, Windows 11, Google Chrome and Microsoft Edge).

## Setup on XAMPP

1. Install **XAMPP 8.2** (PHP 8.2+). Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy the `mattegoda-day-planner` folder into `C:\xampp\htdocs\`.
3. Open **http://localhost/phpmyadmin**, click **Import** (with no database selected) and import, in this order:
   1. `database/schema.sql` (creates the `mattegoda_planner` database and tables)
   2. `database/seed.sql` (14 places, 7 categories, photos and the demo administrator)
4. Copy `config/config.sample.php` to `config/config.php`. Check the values:
   - `DB_PORT`: `3306` is the XAMPP default (change it if your MySQL uses another port)
   - `DB_USER` / `DB_PASS`: `root` with an empty password on a default XAMPP
5. Make sure the PHP **curl** extension is on (it is by default in XAMPP):
   `C:\xampp\php\php.exe -m | findstr curl` should print `curl`.
6. Open **http://localhost/mattegoda-day-planner/**

An internet connection is needed for the map tiles (OpenStreetMap) and road routes (OSRM).
Without it, browsing and searching still work and the planner shows "Route guidance is temporarily unavailable".

## Administrator login (demo)

- Page: **http://localhost/mattegoda-day-planner/admin/login.php** (also linked in the footer)
- Username: `admin`
- Password: `DayPlanner123`

To set a different password (or create another administrator), run from the project folder:

```
C:\xampp\php\php.exe tools\create_admin.php admin
```

## Tests

- All test cases, results and evidence: `docs/test-log.md` and `docs/test-evidence/`
- Unit tests for the time budget (needs Node.js): `node tests/plan-budget.test.js`

## Folder structure

```
admin/       administrator pages (login, place list, place form, delete, logout)
api/         JSON endpoints: places.php, route.php (OSRM), plan.php (save/open plans)
assets/      css/, js/ (planner, map, route, time budget), vendor/ (Bootstrap, Leaflet)
config/      config.sample.php (copy to config.php); blocked from the web
database/    schema.sql, seed.sql
docs/        design diagrams, test log, test evidence
includes/    bootstrap, helpers, header, navbar, footer
src/         classes: Database, Place, Category, PlacePhoto, RouteService, VisitPlan,
             AuthService, PlaceValidator
tests/       JavaScript unit tests
tools/       create_admin.php (command line only; blocked from the web)
uploads/     place photos (PHP execution disabled)
```

## Known limitations

- Photo upload by administrators (FR-52) is not implemented; photos are added through the seed data.
- Poya days are listed in `config.php` for 2026 and must be updated each year.
- Routes use the public OSRM demo server and do not include live traffic (advisory only).

## Credits and licences

Map data © OpenStreetMap contributors (ODbL) · Routing: OSRM (BSD-2-Clause) · Leaflet (BSD-2-Clause) ·
Bootstrap (MIT). Place photos are credited on each place page with their source.

This project was developed for academic assessment (ITE2953). Place information was collected from the
sources linked on each place page and may change; visitors should confirm opening times before travelling.