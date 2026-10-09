<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

// Errors in an API must be JSON too, not an HTML page
set_exception_handler(function (Throwable $e): void {
    log_error('API route: ' . get_class($e) . ': ' . $e->getMessage());
    json_response(500, ['error' => 'The route could not be calculated.']);
});

// Input: ?ids=9,3,4  (place IDs in plan order, at most 20, no repeats)
$raw = trim((string) ($_GET['ids'] ?? ''));
if (!preg_match('/^\d{1,6}(,\d{1,6}){0,19}$/', $raw)) {
    json_response(400, ['error' => 'Give 1 to 20 place IDs, for example ?ids=3,4,9']);
}
$ids = array_map('intval', explode(',', $raw));
if (count($ids) !== count(array_unique($ids))) {
    json_response(400, ['error' => 'A place appears more than once.']);
}

// Start at Salgas Junction, then each stop in plan order (FR-23)
$points = [[RouteService::SALGAS_LAT, RouteService::SALGAS_LNG]];
foreach ($ids as $id) {
    $place = Place::findById($id);                       // active places only
    if ($place === null || $place->latitude === null || $place->longitude === null) {
        json_response(404, ['error' => "Place $id was not found."]);
    }
    $points[] = [$place->latitude, $place->longitude];
}

$route = RouteService::route($points);
if ($route === null) {
    json_response(503, ['error' => 'Route guidance is temporarily unavailable']);   // FR-25
}

// FR-24: totals, plus one leg per stop (used for the time budget, FR-32)
$legs = [];
foreach ($route['legs'] as $i => $leg) {
    $legs[] = [
        'toPlaceId'   => $ids[$i],
        'distanceKm'  => round($leg['distance'] / 1000, 1),
        'durationMin' => (int) round($leg['duration'] / 60),
    ];
}

json_response(200, [
    'distanceKm'  => round($route['distance'] / 1000, 1),
    'durationMin' => (int) round($route['duration'] / 60),
    'legs'        => $legs,
    'geometry'    => $route['geometry'],
]);