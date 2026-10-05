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
if (!array_key_exists($sort, $sortOptions)) {
    $sort = 'distance_asc';
}

$places     = Place::findAll([], $sort);                 // FR-01, FR-02
$total      = count($places);
$totalPages = max(1, (int) ceil($total / PER_PAGE));
$page       = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$page       = min($page, $totalPages);
$pagePlaces = array_slice($places, ($page - 1) * PER_PAGE, PER_PAGE);

$pageUrl = fn(int $n): string => url('index.php?' . http_build_query(['sort' => $sort, 'page' => $n]));

$pageTitle = 'Places';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
  <div>
    <h1 class="h3 mb-1">Places near Mattegoda</h1>
    <p class="text-muted mb-0">Within 25 km of Salgas Junction</p>
  </div>
  <form method="get" class="d-flex align-items-center gap-2">
    <label for="sort" class="form-label mb-0 small text-nowrap">Sort by</label>
    <select id="sort" name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
      <?php foreach ($sortOptions as $value => $label): ?>
        <option value="<?= h($value) ?>" <?= $value === $sort ? 'selected' : '' ?>><?= h($label) ?></option>
      <?php endforeach; ?>
    </select>
    <noscript><button class="btn btn-sm btn-outline-secondary">Apply</button></noscript>
  </form>
</div>

<?php if ($total === 0): ?>
  <!-- FR-05: empty state -->
  <div class="text-center py-5">
    <p class="lead mb-3">No places found</p>
    <a class="btn btn-outline-success" href="<?= url('index.php') ?>">Clear all filters</a>
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
          <a class="page-link" href="<?= h($pageUrl($page - 1)) ?>">Previous</a>
        </li>
        <?php for ($n = 1; $n <= $totalPages; $n++): ?>
          <li class="page-item <?= $n === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= h($pageUrl($n)) ?>"
               <?= $n === $page ? 'aria-current="page"' : '' ?>><?= $n ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?= $page === $totalPages ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= h($pageUrl($page + 1)) ?>">Next</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>