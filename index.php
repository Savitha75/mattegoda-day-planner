<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

const PER_PAGE = 12;                                     // FR-04

$sortOptions = [                                         // FR-03
    'distance_asc'  => 'Distance (nearest first)',
    'distance_desc' => 'Distance (farthest first)',
    'name'          => 'Name (A–Z)',
];
$sort = $_GET['sort'] ?? 'distance_asc';
if (!is_string($sort) || !array_key_exists($sort, $sortOptions)) {
    $sort = 'distance_asc';
}

// FR-06, FR-07: selected categories. Keep only digits that are real category IDs.
$allCategories = Category::findAll();
$selected = [];
foreach ((array) ($_GET['cat'] ?? []) as $value) {
    if (is_string($value) && ctype_digit($value) && isset($allCategories[(int) $value])) {
        $selected[] = (int) $value;
    }
}
$selected = array_values(array_unique($selected));

// FR-09: keyword, trimmed and limited to 100 characters
$q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$q = mb_substr($q, 0, 100);

$filtersActive = $selected !== [] || $q !== '';          // FR-11

$places     = Place::findAll(['categories' => $selected, 'q' => $q], $sort);
$total      = count($places);
$totalPages = max(1, (int) ceil($total / PER_PAGE));
$page       = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$page       = min($page, $totalPages);
$pagePlaces = array_slice($places, ($page - 1) * PER_PAGE, PER_PAGE);

// Links keep the current filters and sort
$baseQuery = array_filter(['q' => $q, 'cat' => $selected, 'sort' => $sort], fn($v) => $v !== '' && $v !== []);
$pageUrl   = fn(int $n): string => url('index.php?' . http_build_query($baseQuery + ['page' => $n]));
$clearUrl  = url('index.php?' . http_build_query(['sort' => $sort]));

$pageTitle = 'Places';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-1">Places near Mattegoda</h1>
<p class="text-muted">Within 25 km of Salgas Junction</p>

<!-- FR-06 to FR-10: search, categories and sort in ONE form, so they always work together -->
<form method="get" class="card card-body shadow-sm mb-4" role="search" aria-label="Filter places">
  <div class="row g-2 align-items-end">
    <div class="col-md-6">
      <label for="q" class="form-label small mb-1">Search name or description</label>
      <input type="search" id="q" name="q" class="form-control" value="<?= h($q) ?>"
             maxlength="100" placeholder="e.g. temple, lake, museum">
    </div>
    <div class="col-7 col-md-4">
      <label for="sort" class="form-label small mb-1">Sort by</label>
      <select id="sort" name="sort" class="form-select" onchange="this.form.submit()">
        <?php foreach ($sortOptions as $value => $label): ?>
          <option value="<?= h($value) ?>" <?= $value === $sort ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-5 col-md-2 d-grid">
      <button type="submit" class="btn btn-success">Search</button>
    </div>
  </div>

  <fieldset class="mt-3">
    <legend class="form-label small mb-1">Categories</legend>
    <div class="d-flex flex-wrap gap-2">
      <?php foreach ($allCategories as $cat): ?>
        <?php $id = 'cat-' . $cat->categoryId; ?>
        <input type="checkbox" class="btn-check" id="<?= $id ?>" name="cat[]"
               value="<?= $cat->categoryId ?>" autocomplete="off" onchange="this.form.submit()"
               <?= in_array($cat->categoryId, $selected, true) ? 'checked' : '' ?>>
        <label class="btn btn-sm btn-outline-secondary rounded-pill" for="<?= $id ?>">
          <?= h(ucfirst($cat->name)) ?>
        </label>
      <?php endforeach; ?>
    </div>
  </fieldset>
</form>

<?php if ($filtersActive && $total > 0): ?>
  <!-- FR-11: match count -->
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3" aria-live="polite">
    <span><strong><?= $total ?></strong> <?= $total === 1 ? 'place matches' : 'places match' ?> your filters</span>
    <a class="btn btn-sm btn-link" href="<?= h($clearUrl) ?>">Clear filters</a>
  </div>
<?php endif; ?>

<?php if ($total === 0): ?>
  <!-- FR-05: empty state -->
  <div class="text-center py-5" aria-live="polite">
    <p class="lead mb-3">No places found</p>
    <a class="btn btn-outline-success" href="<?= h($clearUrl) ?>">Clear all filters</a>
  </div>
<?php else: ?>
  <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
    <?php foreach ($pagePlaces as $place): ?>
      <div class="col">
        <article class="card h-100 place-card shadow-sm">
          <img src="<?= h(url($place->thumbPath ?? 'assets/img/placeholder.svg')) ?>"
               class="card-img-top" alt="<?= h($place->name) ?>" loading="lazy">
          <div class="card-body d-flex flex-column">
            <h2 class="h5 card-title">
              <a class="stretched-link text-decoration-none text-reset"
                 href="<?= h(url('place.php?id=' . $place->placeId)) ?>"><?= h($place->name) ?></a>
            </h2>
            <div class="mb-2 d-flex flex-wrap gap-1">
              <?php foreach ($place->getCategories() as $cat): ?>
                <span class="badge badge-cat" style="background-color: <?= h($cat->badgeColour) ?>">
                  <?= h(ucfirst($cat->name)) ?>
                </span>
              <?php endforeach; ?>
            </div>
            <p class="card-text small flex-grow-1"><?= h($place->summary) ?></p>
            <p class="card-text small text-muted mb-0">
              <?= $place->distanceKm !== null
                    ? h(number_format($place->distanceKm, 1)) . ' km from Salgas Junction'
                    : 'Distance not available' ?>
            </p>
          </div>
        </article>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <!-- FR-04: pagination -->
    <nav class="mt-4" aria-label="Place list pages">
      <ul class="pagination justify-content-center">
        <li class="page-item <?= $page === 1 ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= h($pageUrl($page - 1)) ?>"
             <?= $page === 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>>Previous</a>
        </li>
        <?php for ($n = 1; $n <= $totalPages; $n++): ?>
          <li class="page-item <?= $n === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= h($pageUrl($n)) ?>"
               <?= $n === $page ? 'aria-current="page"' : '' ?>><?= $n ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?= $page === $totalPages ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= h($pageUrl($page + 1)) ?>"
             <?= $page === $totalPages ? 'tabindex="-1" aria-disabled="true"' : '' ?>>Next</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>