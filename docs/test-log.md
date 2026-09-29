# Test Log – Local Tourist Day-Visit Planner (Mattegoda)

ITE2953 · J A S R Perera (E2410994)

Environment: Windows 11, XAMPP 8.2.12 (Apache 2.4.58, PHP 8.2.12, MariaDB 10.4.32 on port 3307), Google Chrome.

## Security tests

| Test ID | Requirement | Scenario | Steps | Expected | Actual | Status | Date | Evidence |
|---|---|---|---|---|---|---|---|---|
| TC-S01 | NFR-13, security | Direct browser access to the config folder | Open http://localhost/mattegoda-day-planner/config/ | 403 Forbidden | 403 Forbidden | Pass | 28 Sep 2026 | test-evidence/TC-S01.png |
| TC-S02 | Security | Directory listing disabled | Open http://localhost/mattegoda-day-planner/api/ | 403 Forbidden (no file list) | 403 Forbidden | Pass | 28 Sep 2026 | test-evidence/TC-S02.png |

## Database tests

| Test ID | Requirement | Scenario | Steps | Expected | Actual | Status | Date | Evidence |
|---|---|---|---|---|---|---|---|---|
| TC-DB-01 | BR-02 | Duplicate category name | Insert category 'natural' twice | Second insert rejected | #1062 Duplicate entry 'natural' for key 'uq_category_name' | Pass | 30 Sep 2026 | test-evidence/TC-DB-01.png |
| TC-DB-02 | FR-08, data integrity | Link a category to a place that does not exist | Insert place_category (999, 1, 1) | Rejected by foreign key | #1452 Cannot add or update a child row (fk_pc_place) | Pass | 30 Sep 2026 | test-evidence/TC-DB-02.png |
| TC-DB-03 | FR-54 | Delete a category still used by a place | Insert a place, link it to category 1, delete category 1 | Delete rejected | #1451 Cannot delete or update a parent row (fk_pc_category) | Pass | 30 Sep 2026 | test-evidence/TC-DB-03.png |
| TC-DB-04 | NFR-07, FR-53 | Last-updated time maintained automatically | Read the place row, update its name, read it again | updated_at changes; created_at stays the same | 01:06:22 → 01:07:02; created_at unchanged | Pass | 30 Sep 2026 | test-evidence/TC-DB-04.png |