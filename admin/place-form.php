<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
AuthService::requireLogin();

$allCategories = Category::findAll();

// ?id=5 edits place 5 (FR-50); no id adds a new place (FR-45)
$placeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$place   = $placeId !== null ? Place::findById($placeId, true) : null;   // deleted places can be edited too
if ($placeId !== null && $place === null) {
    flash('warning', 'That place does not exist.');
    redirect('admin/index.php');
}

// What the form shows: the saved record when editing, empty boxes when adding
$values = ['closed_days' => [], 'categories' => [], 'primary' => 0];
if ($place !== null) {
    $values = [
        'name'               => $place->name,
        'summary'            => $place->summary,
        'description'        => $place->description,
        'latitude'           => (string) $place->latitude,
        'longitude'          => (string) $place->longitude,
        'access_point'       => $place->accessPoint,
        'opening_time'       => $place->openingTime !== null ? substr($place->openingTime, 0, 5) : '',
        'closing_time'       => $place->closingTime !== null ? substr($place->closingTime, 0, 5) : '',
        'closed_days'        => $place->closedDays,
        'entry_fee'          => $place->entryFee,
        'best_time'          => $place->bestTime,
        'visit_duration_min' => (string) ($place->visitDurationMin ?? ''),
        'travel_tips'        => $place->travelTips,
        'facilities'         => $place->facilities,
        'info_source'        => $place->infoSource,
        'categories'         => $place->categoryIds,
        'primary'            => $place->categoryIds[0] ?? 0,     // primary category comes first
    ];
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v      = new PlaceValidator($_POST, $allCategories);
    $values = $v->values;                                   // show what was typed if there are errors
    $errors = csrf_valid() ? $v->errors : ['form' => 'The form has expired. Please submit it again.'];

    // FR-47: when everything else is valid, ask OSRM for the road distance from Salgas Junction
    $road = null;
    if ($errors === []) {
        $road = RouteService::fromStart($v->data['latitude'], $v->data['longitude']);
        if ($road === null) {
            $errors['location'] = 'The road distance could not be checked because the routing service is unavailable. Nothing was saved; please try again later.';
        } elseif ($road['distanceKm'] > RouteService::MAX_RADIUS_KM) {
            $errors['location'] = sprintf('This place is %.1f km by road from Salgas Junction. Places must be within %d km, so it was not saved.',
                $road['distanceKm'], RouteService::MAX_RADIUS_KM);
        }
    }

    if ($errors === []) {
        Place::save(
            $v->data + ['distance_km' => $road['distanceKm'], 'travel_min' => $road['travelMin']],
            $v->categoryIds,
            $v->primaryId,
            $placeId
        );
        flash('success', sprintf('"%s" was %s. Road distance from Salgas Junction: %.1f km.',
            $v->data['name'], $placeId === null ? 'added' : 'updated', $road['distanceKm']));
        redirect('admin/index.php');
    }
}

// Small helpers for the form below
$val     = fn(string $k): string => h((string) ($values[$k] ?? ''));
$invalid = fn(string $k): string => isset($errors[$k]) ? ' is-invalid' : '';
$error   = fn(string $k): string => isset($errors[$k]) ? '<div class="invalid-feedback">' . h($errors[$k]) . '</div>' : '';
$input   = fn(string $name, string $label, string $attrs = '', string $help = ''): string =>
    '<label for="' . $name . '" class="form-label">' . h($label) . '</label>'
    . '<input id="' . $name . '" name="' . $name . '" class="form-control' . $invalid($name) . '" value="' . $val($name) . '" ' . $attrs . '>'
    . $error($name)
    . ($help !== '' ? '<div class="form-text">' . h($help) . '</div>' : '');
$area    = fn(string $name, string $label, int $rows, string $attrs = ''): string =>
    '<label for="' . $name . '" class="form-label">' . h($label) . '</label>'
    . '<textarea id="' . $name . '" name="' . $name . '" rows="' . $rows . '" class="form-control' . $invalid($name) . '" ' . $attrs . '>' . $val($name) . '</textarea>'
    . $error($name);

$current   = 'admin/index.php';
$pageTitle = $place === null ? 'Add a place' : 'Edit place';
require __DIR__ . '/../includes/header.php';
?>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= h(url('admin/index.php')) ?>">Manage places</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= h($pageTitle) ?></li>
  </ol>
</nav>
<h1 class="h3 mb-1"><?= h($pageTitle) ?></h1>
<?php if ($place !== null): ?>
  <p class="small text-muted">
    <?= $place->isActive ? '' : '<span class="badge text-bg-secondary me-1">Deleted</span>' ?>
    Created <?= h(date('j M Y, g:i a', strtotime((string) $place->createdAt))) ?> ·
    last updated <?= h(date('j M Y, g:i a', strtotime((string) $place->updatedAt))) ?>
  </p>
<?php endif; ?>

<!-- FR-48: list every field in error -->
<?php if ($errors !== []): ?>
  <div class="alert alert-danger" role="alert">
    <p class="fw-semibold mb-1">The place was not saved. Please correct the following:</p>
    <ul class="mb-0">
      <?php foreach ($errors as $message): ?>
        <li><?= h($message) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" class="card card-body shadow-sm" novalidate>
  <?= csrf_field() ?>

  <div class="row g-3">
    <div class="col-md-8"><?= $input('name', 'Name *', 'maxlength="120" required') ?></div>
    <div class="col-md-4"><?= $input('visit_duration_min', 'Suggested visit (minutes)', 'type="number" min="5" max="720" step="5"') ?></div>

    <div class="col-12"><?= $input('summary', 'Short summary (shown on the place list)', 'maxlength="200"', 'Optional: if empty, the start of the description is used.') ?></div>
    <div class="col-12"><?= $area('description', 'Description *', 4, 'required') ?></div>

    <!-- FR-47 to FR-49: location -->
    <div class="col-md-6"><?= $input('latitude', 'Latitude *', 'inputmode="decimal" required placeholder="e.g. 6.879536"') ?></div>
    <div class="col-md-6"><?= $input('longitude', 'Longitude *', 'inputmode="decimal" required placeholder="e.g. 79.928933"') ?></div>
    <?php if (isset($errors['location'])): ?>
      <div class="col-12"><div class="alert alert-warning mb-0"><?= h($errors['location']) ?></div></div>
    <?php endif; ?>
    <div class="col-12 form-text mt-1">The road distance from Salgas Junction is calculated when you save. It must be <?= RouteService::MAX_RADIUS_KM ?> km or less.</div>
    <div class="col-12"><?= $input('access_point', 'Main entrance', 'maxlength="150"') ?></div>

    <!-- FR-46: categories -->
    <fieldset class="col-12">
      <legend class="form-label fs-6">Categories * <span class="text-muted small">(tick all that apply, then choose the main one for the map marker)</span></legend>
      <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-2<?= $invalid('categories') ?>">
        <?php foreach ($allCategories as $cat): ?>
          <?php $id = $cat->categoryId; ?>
          <div class="col">
            <div class="border rounded p-2 h-100">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="categories[]" value="<?= $id ?>" id="cat-<?= $id ?>"
                       <?= in_array($id, $values['categories'], true) ? 'checked' : '' ?>>
                <label class="form-check-label" for="cat-<?= $id ?>">
                  <span class="legend-dot" style="background-color: <?= h($cat->badgeColour) ?>"></span><?= h(ucfirst($cat->name)) ?>
                </label>
              </div>
              <div class="form-check small">
                <input class="form-check-input" type="radio" name="primary" value="<?= $id ?>" id="primary-<?= $id ?>"
                       <?= (int) $values['primary'] === $id ? 'checked' : '' ?>>
                <label class="form-check-label text-muted" for="primary-<?= $id ?>">Main category</label>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?= $error('categories') ?>
    </fieldset>

    <!-- Opening hours and closed days (used by FR-34) -->
    <div class="col-md-3"><?= $input('opening_time', 'Opening time', 'type="time"') ?></div>
    <div class="col-md-3"><?= $input('closing_time', 'Closing time', 'type="time"') ?></div>
    <fieldset class="col-md-6">
      <legend class="form-label fs-6">Closed on</legend>
      <?php foreach (PlaceValidator::DAYS as $day): ?>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" name="closed_days[]" value="<?= $day ?>" id="day-<?= $day ?>"
                 <?= in_array($day, $values['closed_days'], true) ? 'checked' : '' ?>>
          <label class="form-check-label" for="day-<?= $day ?>"><?= $day ?></label>
        </div>
      <?php endforeach; ?>
    </fieldset>

    <div class="col-md-6"><?= $input('entry_fee', 'Entry fee', 'maxlength="120"') ?></div>
    <div class="col-md-6"><?= $input('best_time', 'Best time to visit', 'maxlength="120"') ?></div>
    <div class="col-12"><?= $area('travel_tips', 'Travel tips', 3) ?></div>
    <div class="col-md-6"><?= $input('facilities', 'Facilities', 'maxlength="255"') ?></div>
    <div class="col-md-6"><?= $input('info_source', 'Information source (web address)', 'type="url" maxlength="255"') ?></div>
  </div>

  <div class="d-flex flex-wrap gap-2 mt-4">
    <button type="submit" class="btn btn-success"><?= $place === null ? 'Add place' : 'Save changes' ?></button>
    <a class="btn btn-outline-secondary" href="<?= h(url('admin/index.php')) ?>">Cancel</a>
  </div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
