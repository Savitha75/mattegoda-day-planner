<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$pageTitle   = 'Plan my day';
$pageScripts = ['plan-page.js'];
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-1">Plan my day</h1>
<p class="text-muted">Your plan is kept in this browser. Nothing is sent to the server unless you choose to save it.</p>

<div id="plan-app"
     data-api="<?= h(url('api/places.php')) ?>"
     data-list-url="<?= h(url('index.php')) ?>"
     data-map-url="<?= h(url('map.php')) ?>"
     aria-live="polite">
  <p class="text-muted">Loading your plan…</p>
</div>

<noscript>
  <div class="alert alert-warning">The day planner needs JavaScript. You can still
    <a href="<?= h(url('index.php')) ?>">browse places</a>.</div>
</noscript>
<?php require __DIR__ . '/includes/footer.php'; ?>