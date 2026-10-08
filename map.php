<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

// FR-22: same filters as the list page
$allCategories = Category::findAll();
$selected      = selected_categories($allCategories);
$q             = search_keyword();
$query         = filter_query($selected, $q);
$apiUrl        = url('api/places.php' . ($query !== '' ? '?' . $query : ''));
$listUrl       = url('index.php' . ($query !== '' ? '?' . $query : ''));

$pageTitle   = 'Map';
$useMap      = true;
$pageScripts = ['map.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
  <div>
    <h1 class="h3 mb-1">Map of places</h1>
    <p class="text-muted mb-0">Starting point: Salgas Junction, Mattegoda</p>
  </div>
  <a class="btn btn-outline-success btn-sm" href="<?= h($listUrl) ?>">View as list</a>
</div>

<!-- FR-22: category filter for the markers -->
<form method="get" class="card card-body shadow-sm mb-3" aria-label="Filter map markers">
  <?php if ($q !== ''): ?>
    <input type="hidden" name="q" value="<?= h($q) ?>">
    <p class="small mb-2">Search: <strong><?= h($q) ?></strong>
      · <a href="<?= h(url('map.php' . ($selected !== [] ? '?' . filter_query($selected, '') : ''))) ?>">remove search</a></p>
  <?php endif; ?>
  <fieldset>
    <legend class="form-label small mb-1">Categories</legend>
    <div class="d-flex flex-wrap gap-2">
      <?php foreach ($allCategories as $cat): ?>
        <?php $id = 'cat-' . $cat->categoryId; ?>
        <input type="checkbox" class="btn-check" id="<?= $id ?>" name="cat[]"
               value="<?= $cat->categoryId ?>" autocomplete="off" onchange="this.form.submit()"
               <?= in_array($cat->categoryId, $selected, true) ? 'checked' : '' ?>>
        <label class="btn btn-sm btn-outline-secondary rounded-pill" for="<?= $id ?>">
          <span class="legend-dot" style="background-color: <?= h($cat->badgeColour) ?>"></span><?= h(ucfirst($cat->name)) ?>
        </label>
      <?php endforeach; ?>
    </div>
  </fieldset>
</form>

<p id="map-count" class="small mb-2" aria-live="polite">Loading places…</p>

<!-- FR-18, FR-19: the map; start point comes from RouteService -->
<div id="places-map" class="rounded border"
     data-api="<?= h($apiUrl) ?>"
     data-start-lat="<?= RouteService::SALGAS_LAT ?>"
     data-start-lng="<?= RouteService::SALGAS_LNG ?>"
     role="region" aria-label="Map of places near Mattegoda"></div>

<p class="small text-muted mt-2">
  <span class="start-dot">S</span> Salgas Junction (start) · Marker colour = primary category
  <?php if ($selected !== []): ?>· <a href="<?= h(url('map.php')) ?>">Show all categories</a><?php endif; ?>
</p>
<noscript><div class="alert alert-warning">The map needs JavaScript. You can still browse the <a href="<?= h($listUrl) ?>">place list</a>.</div></noscript>
<?php require __DIR__ . '/includes/footer.php'; ?>