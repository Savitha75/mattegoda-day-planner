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

date_default_timezone_set('Asia/Colombo');