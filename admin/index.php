<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
AuthService::requireLogin();                           // FR-42, FR-43: always the first line of an admin page

$places = Place::adminList();                          // FR-44: active and deleted places
$active = count(array_filter($places, fn(array $p) => (int) $p['is_active'] === 1));

$current   = 'admin/index.php';                        // highlights "Admin" in the navbar
$pageTitle = 'Manage places';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
  <div>
    <h1 class="h3 mb-1">Manage places</h1>
    <p class="text-muted mb-0"><?= count($places) ?> places: <?= $active ?> active, <?= count($places) - $active ?> deleted</p>
  </div>
  <a class="btn btn-success" href="<?= h(url('admin/place-form.php')) ?>">+ Add a place</a>
</div>

<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th scope="col">#</th>
        <th scope="col">Name</th>
        <th scope="col">Main category</th>
        <th scope="col" class="text-end">Road km</th>
        <th scope="col">Status</th>
        <th scope="col">Last updated</th>
        <th scope="col" class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($places as $p): ?>
      <?php $isActive = (int) $p['is_active'] === 1; ?>
      <tr class="<?= $isActive ? '' : 'text-muted' ?>">
        <td><?= (int) $p['place_id'] ?></td>
        <td><?= h($p['name']) ?></td>
        <td><?= h(ucfirst((string) $p['primary_category'])) ?></td>
        <td class="text-end"><?= $p['distance_km'] !== null ? number_format((float) $p['distance_km'], 1) : '–' ?></td>
        <td>
          <span class="badge <?= $isActive ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $isActive ? 'Active' : 'Deleted' ?></span>
        </td>
        <td class="small"><?= h(date('j M Y, g:i a', strtotime($p['updated_at']))) ?></td>
        <td class="text-end text-nowrap">
          <a class="btn btn-sm btn-outline-primary" aria-label="Edit <?= h($p['name']) ?>"
             href="<?= h(url('admin/place-form.php?id=' . (int) $p['place_id'])) ?>">Edit</a>
          <?php if ($isActive): ?>
            <a class="btn btn-sm btn-outline-danger" aria-label="Delete <?= h($p['name']) ?>"
               href="<?= h(url('admin/delete.php?id=' . (int) $p['place_id'])) ?>">Delete</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
