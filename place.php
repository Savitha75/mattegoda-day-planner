<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

// FR-17: only a positive whole number is accepted; unknown or inactive IDs give null
$id    = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$place = $id ? Place::findById($id) : null;

if ($place === null) {
    http_response_code(404);
    $pageTitle = 'Place not found';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="text-center py-5">
      <h1 class="h3">Place not found</h1>
      <p class="text-muted">The place you asked for does not exist or is no longer listed.</p>
      <a class="btn btn-success" href="<?= h(url('index.php')) ?>">Back to all places</a>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$photos     = $place->getPhotos();                     // FR-14
$categories = $place->getCategories();
$nearby     = $place->findNearby(5.0, 5);              // FR-16
$isNatural  = $place->hasCategory('natural');          // NFR-08

// Closed days, e.g. ['Mon', 'Poya'] -> "Monday, Poya days"
$dayNames = [
    'Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday',
    'Fri' => 'Friday', 'Sat' => 'Saturday', 'Sun' => 'Sunday', 'Poya' => 'Poya days',
];
$closedText = $place->closedDays === []
    ? 'None (open every day)'
    : implode(', ', array_map(fn(string $d) => $dayNames[$d] ?? $d, $place->closedDays));

$is24h   = $place->openingTime === '00:00:00' && $place->closingTime === '23:59:00';
$updated = $place->updatedAt !== null ? (new DateTime($place->updatedAt))->format('j F Y') : null;

// Only link to the source if it is a real http(s) URL (blocks javascript: links)
$sourceUrl = $place->infoSource;
$sourceOk  = $sourceUrl !== null
    && filter_var($sourceUrl, FILTER_VALIDATE_URL) !== false
    && preg_match('#^https?://#i', $sourceUrl) === 1;

// FR-12 + FR-13: label => safe HTML (show_value escapes, or prints "Not available")
$details = [
    'Opening time'      => show_value(format_time($place->openingTime)),
    'Closing time'      => show_value(format_time($place->closingTime)),
    'Closed on'         => h($closedText),
    'Entry fee'         => show_value($place->entryFee),
    'Distance'          => show_value($place->distanceKm !== null
                              ? number_format($place->distanceKm, 1) . ' km from Salgas Junction' : null),
    'Travel time'       => show_value($place->travelMin !== null
                              ? $place->travelMin . ' min by road from Salgas Junction' : null),
    'Best time to visit'=> show_value($place->bestTime),
    'Suggested visit'   => show_value(format_duration($place->visitDurationMin)),
    'Entrance'          => show_value($place->accessPoint),
    'Facilities'        => show_value($place->facilities),
];

$pageTitle   = $place->name;
$useMap      = true;
$pageScripts = ['place-map.js'];
require __DIR__ . '/includes/header.php';
?>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= h(url('index.php')) ?>">Places</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= h($place->name) ?></li>
  </ol>
</nav>

<div class="row g-4">
  <!-- Left: photos, name, description -->
  <div class="col-lg-7">
    <?php if ($photos === []): ?>
      <!-- FR-14: placeholder when there is no photo -->
      <img src="<?= h(url('assets/img/placeholder.svg')) ?>" class="img-fluid rounded mb-3 place-photo w-100"
           alt="No photo available for <?= h($place->name) ?>">
    <?php else: ?>
      <!-- FR-14: all photos, each with its licence credit -->
      <div id="placePhotos" class="carousel slide mb-3">
        <div class="carousel-inner">
          <?php foreach ($photos as $i => $photo): ?>
            <figure class="carousel-item mb-0 <?= $i === 0 ? 'active' : '' ?>">
              <img src="<?= h(url($photo->filePath)) ?>" class="d-block w-100 rounded place-photo"
                   alt="<?= h($photo->caption ?? $place->name) ?>">
              <figcaption class="small text-muted mt-1">Photo: <?= h($photo->source) ?></figcaption>
            </figure>
          <?php endforeach; ?>
        </div>
        <?php if (count($photos) > 1): ?>
          <button class="carousel-control-prev" type="button" data-bs-target="#placePhotos" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous photo</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#placePhotos" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next photo</span>
          </button>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <h1 class="h2 mb-2"><?= h($place->name) ?></h1>
    <div class="d-flex flex-wrap gap-1 mb-2">
      <?php foreach ($categories as $cat): ?>
        <span class="badge badge-cat" style="background-color: <?= h($cat->badgeColour) ?>"><?= h(ucfirst($cat->name)) ?></span>
      <?php endforeach; ?>
      <?php if ($is24h): ?>
        <span class="badge text-bg-light border">Open 24 hours</span>
      <?php endif; ?>
    </div>

    <!-- NFR-07: when this record was last updated -->
    <p class="small text-muted">Last updated: <?= show_value($updated) ?></p>

    <!-- FR-26: add from the detail page -->
    <p>
      <button type="button" class="btn btn-outline-success btn-plan"
              data-add-to-plan="<?= $place->placeId ?>" data-name="<?= h($place->name) ?>">+ Add to plan</button>
      <a class="btn btn-link" href="<?= h(url('plan.php')) ?>">View my plan</a>
    </p>

    <?php if ($isNatural): ?>
      <!-- NFR-08: safety notice on natural places -->
      <div class="alert alert-warning small" role="note">
        <strong>Safety:</strong> visitors are responsible for observing site safety rules and any posted restrictions.
      </div>
    <?php endif; ?>

    <h2 class="h5 mt-4">About</h2>
    <p><?= show_value($place->description) ?></p>

    <h2 class="h5 mt-4">Travel tips</h2>
    <p><?= show_value($place->travelTips) ?></p>
  </div>

  <!-- Right: visitor information, map, nearby places -->
  <div class="col-lg-5">
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h5">Visitor information</h2>
        <dl class="row mb-0 small">
          <?php foreach ($details as $label => $html): ?>
            <dt class="col-5"><?= h($label) ?></dt>
            <dd class="col-7"><?= $html ?></dd>
          <?php endforeach; ?>
        </dl>
      </div>
    </div>

    <!-- FR-15: map with one marker -->
    <div id="place-map" class="rounded border mb-4"
         data-lat="<?= h((string) $place->latitude) ?>"
         data-lng="<?= h((string) $place->longitude) ?>"
         data-name="<?= h($place->name) ?>"
         role="region" aria-label="Map showing <?= h($place->name) ?>"></div>

    <!-- FR-16: up to five places within 5 km -->
    <h2 class="h5">Nearby places (within 5 km)</h2>
    <?php if ($nearby === []): ?>
      <p class="small text-muted">No other places within 5 km.</p>
    <?php else: ?>
      <div class="list-group mb-4">
        <?php foreach ($nearby as $n): ?>
          <a class="list-group-item list-group-item-action d-flex justify-content-between"
             href="<?= h(url('place.php?id=' . $n->placeId)) ?>">
            <span><?= h($n->name) ?></span>
            <span class="text-muted small"><?= h(number_format((float) $n->nearbyKm, 1)) ?> km away</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($sourceOk): ?>
      <p class="small text-muted">
        Information source:
        <a href="<?= h($sourceUrl) ?>" target="_blank" rel="noopener noreferrer">official or published page</a>
      </p>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>