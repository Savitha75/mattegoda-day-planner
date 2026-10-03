<?php
$pageTitle = $pageTitle ?? APP_NAME;
$current   = basename($_SERVER['SCRIPT_NAME']);   // e.g. "index.php", used by navbar
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($pageTitle) ?> | <?= h(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
  <?php if (!empty($useMap)): ?>
  <link rel="stylesheet" href="<?= url('assets/vendor/leaflet/leaflet.css') ?>">
  <?php endif; ?>
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="d-flex flex-column min-vh-100">
<a class="visually-hidden-focusable" href="#main">Skip to content</a>
<?php require __DIR__ . '/navbar.php'; ?>
<main id="main" class="container my-4 flex-grow-1">
<?php foreach (get_flashes() as $f): ?>
  <div class="alert alert-<?= h($f['type']) ?> alert-dismissible fade show" role="alert">
    <?= h($f['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endforeach; ?>