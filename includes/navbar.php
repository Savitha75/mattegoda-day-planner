<?php
$current = $current ?? basename($_SERVER['SCRIPT_NAME']);
$links = [
    'index.php' => 'Places',
    'map.php'   => 'Map',
    'plan.php'  => 'Plan my day',
];
if (AuthService::check()) {
    $links['admin/index.php'] = 'Admin';
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= url('index.php') ?>"><?= h(APP_NAME) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
            aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto">
        <?php foreach ($links as $file => $label): ?>
          <li class="nav-item">
            <a class="nav-link<?= $current === $file ? ' active' : '' ?>"
               <?= $current === $file ? 'aria-current="page"' : '' ?>
               href="<?= url($file) ?>"><?= h($label) ?><?php if ($file === 'plan.php'): ?>
                 <span class="badge rounded-pill text-bg-light ms-1" data-plan-count hidden></span><?php endif; ?></a>
          </li>
        <?php endforeach; ?>
        <?php if (AuthService::check()): ?>
          <li class="nav-item ms-lg-2 d-flex align-items-center">
            <form method="post" action="<?= url('admin/logout.php') ?>" class="my-1">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-sm btn-outline-light">Log out (<?= h($_SESSION['admin_name']) ?>)</button>
            </form>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>