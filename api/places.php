<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

// Errors in an API must be JSON too, not an HTML page
set_exception_handler(function (Throwable $e): void {
    log_error('API places: ' . get_class($e) . ': ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Places could not be loaded.']);
});

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// FR-22: the same filters as the list page
$allCategories = Category::findAll();
$places = Place::findAll(
    ['categories' => selected_categories($allCategories), 'q' => search_keyword()],
    'distance_asc'
);

// FR-20, FR-21: only what the map needs
$data = array_map(fn(Place $p) => [
    'id'         => $p->placeId,
    'name'       => $p->name,
    'lat'        => $p->latitude,
    'lng'        => $p->longitude,
    'distanceKm' => $p->distanceKm,
    'categories' => array_map(fn(Category $c) => [
        'name'   => $c->name,
        'colour' => $c->badgeColour,
    ], $p->getCategories()),                       // primary category first
    'url'        => url('place.php?id=' . $p->placeId),
    // FR-32 to FR-34: what the time budget needs ("06:00:00" -> "06:00")
    'openingTime' => $p->openingTime !== null ? substr($p->openingTime, 0, 5) : null,
    'closingTime' => $p->closingTime !== null ? substr($p->closingTime, 0, 5) : null,
    'closedDays'  => $p->closedDays,
    'visitMin'    => $p->visitDurationMin,
], $places);

echo json_encode(
    ['count' => count($data), 'places' => $data],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
);