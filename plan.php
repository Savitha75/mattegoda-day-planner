<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$pageTitle   = 'Plan my day';
$useMap      = true;                                // FR-23: Leaflet for the route map
$pageScripts = ['plan-route.js', 'plan-page.js'];  // route first: plan-page.js calls it
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-1">Plan my day</h1>
<p class="text-muted">Your plan is kept in this browser. Nothing is sent to the server unless you choose to save it.</p>

<div class="row g-4">
  <div class="col-lg-7">
    <div id="plan-app"
         data-api="<?= h(url('api/places.php')) ?>"
         data-list-url="<?= h(url('index.php')) ?>"
         data-map-url="<?= h(url('map.php')) ?>"
         aria-live="polite">
      <p class="text-muted">Loading your plan…</p>
    </div>
  </div>

  <!-- UI-05 route view: FR-23 to FR-25, NFR-06 -->
  <div class="col-lg-5">
    <section class="card shadow-sm" aria-labelledby="route-heading">
      <div class="card-body">
        <h2 id="route-heading" class="h5">Route from Salgas Junction</h2>
        <div id="plan-map" class="rounded border"
             data-route-api="<?= h(url('api/route.php')) ?>"
             data-start-lat="<?= RouteService::SALGAS_LAT ?>"
             data-start-lng="<?= RouteService::SALGAS_LNG ?>"
             role="region" aria-label="Map of your route"></div>
        <p id="route-summary" class="text-muted mt-2 mb-0" aria-live="polite">Loading the route…</p>

        <!-- NFR-06: safety notice on every route view -->
        <div class="alert alert-secondary small mt-3 mb-0" role="note">
          <strong>Advisory only.</strong> Route guidance is not based on live traffic conditions
          and must not be used as a live navigation aid while driving.
        </div>
      </div>
    </section>
  </div>
</div>

<noscript>
  <div class="alert alert-warning">The day planner needs JavaScript. You can still
    <a href="<?= h(url('index.php')) ?>">browse places</a>.</div>
</noscript>
<?php require __DIR__ . '/includes/footer.php'; ?>