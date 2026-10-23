<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
AuthService::requireLogin();

$id    = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$place = $id ? Place::findById($id, true) : null;
if ($place === null || !$place->isActive) {
    flash('warning', 'That place does not exist or has already been deleted.');
    redirect('admin/index.php');
}

// FR-51: only a POST from this confirmation page, with a valid token, deletes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrf_valid() && Place::deactivate($place->placeId)) {
        flash('success', '"' . $place->name . '" was deleted. Visitors no longer see it; the record is kept in the database.');
    } else {
        flash('danger', 'The place was not deleted. Please try again.');
    }
    redirect('admin/index.php');
}

$current   = 'admin/index.php';
$pageTitle = 'Delete place';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Delete place</h1>
<div class="card card-body shadow-sm border-danger" style="max-width: 40rem">
  <p class="mb-2">Are you sure you want to delete <strong><?= h($place->name) ?></strong>?</p>
  <p class="small text-muted">It will disappear from the place list, the map, search results and day plans.
    The record itself is kept in the database (soft delete), so it can be restored later if needed.</p>
  <form method="post" class="d-flex flex-wrap gap-2">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-danger">Yes, delete this place</button>
    <a class="btn btn-outline-secondary" href="<?= h(url('admin/index.php')) ?>">Cancel</a>
  </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
