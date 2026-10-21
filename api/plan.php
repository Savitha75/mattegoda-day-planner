<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

// Errors in an API must be JSON too, not an HTML page
set_exception_handler(function (Throwable $e): void {
    log_error('API plan: ' . get_class($e) . ': ' . $e->getMessage());
    json_response(500, ['error' => 'The plan could not be saved or opened. Please try again.']);
});

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// FR-35 (open): GET api/plan.php?code=K7M3QX9P
if ($method === 'GET') {
    $plan = VisitPlan::findByCode((string) ($_GET['code'] ?? ''));
    if ($plan === null) {
        json_response(404, ['error' => 'No saved plan has that reference code.']);
    }
    json_response(200, [
        'refCode'   => $plan->refCode,
        'visitDate' => $plan->visitDate,
        'startTime' => $plan->startTime,
        'stops'     => $plan->stops,
    ]);
}

// FR-35 (save): POST {"stops":[3,4,9],"startTime":"08:00","visitDate":"2026-10-25"}
if ($method !== 'POST') {
    header('Allow: GET, POST');
    json_response(405, ['error' => 'Use GET to open a plan or POST to save one.']);
}

// Only JSON is accepted. A normal HTML form on another website cannot send JSON,
// so another site cannot quietly fill our database through a visitor's browser.
if (!str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    json_response(415, ['error' => 'Send the plan as JSON.']);
}

$input = json_decode((string) file_get_contents('php://input', false, null, 0, 10000), true);
if (!is_array($input) || !is_array($input['stops'] ?? null)) {
    json_response(400, ['error' => 'The plan data is not valid.']);
}

try {
    $plan = VisitPlan::create(
        $input['stops'],
        is_string($input['startTime'] ?? null) ? $input['startTime'] : '',
        is_string($input['visitDate'] ?? null) ? $input['visitDate'] : ''
    );
} catch (InvalidArgumentException $e) {
    json_response(400, ['error' => $e->getMessage()]);   // our own message, safe to show
}

json_response(201, ['refCode' => $plan->refCode]);
