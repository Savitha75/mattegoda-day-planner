<?php
$current = $current ?? basename($_SERVER['SCRIPT_NAME']);
$links = [
    'index.php' => 'Places',
    'map.php'   => 'Map',
    'plan.php'  => 'Plan my day',
];
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
               href="<?= url($file) ?>"><?= h($label) ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</nav>