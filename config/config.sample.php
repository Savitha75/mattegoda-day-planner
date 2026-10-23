<?php
declare(strict_types=1);

// Database (XAMPP port 3306)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'mattegoda_planner');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application
define('APP_NAME', 'Mattegoda Day Planner');
define('BASE_URL', '/mattegoda-day-planner');
define('APP_DEBUG', false);

// Routing service (FR-23 to FR-25, FR-47)
define('OSRM_URL', 'https://router.project-osrm.org');

// Full Moon Poya days for 2026 (FR-34), from the Government holiday gazette. Update every year.
define('POYA_DATES', [
    '2026-01-03', '2026-02-01', '2026-03-02', '2026-04-01', '2026-05-01', '2026-05-30',
    '2026-06-29', '2026-07-29', '2026-08-27', '2026-09-26', '2026-10-25', '2026-11-24', '2026-12-23',
]);

// Administrator session (FR-43): logged out after this many minutes without activity
define('ADMIN_IDLE_MINUTES', 30);

date_default_timezone_set('Asia/Colombo');