<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$pageTitle   = 'Plan my day';
$useMap      = true;                                // FR-23: Leaflet for the route map
$pageScripts = ['plan-budget.js', 'plan-route.js', 'plan-page.js'];  // budget and route before the page
require __DIR__ . '/includes/header.php';
?>
<div class="plan-screen">
<h1 class="h3 mb-1">Plan my day</h1>
<p class="text-muted">Your plan is kept in this browser. Nothing is sent to the server unless you choose to save it.</p>

<div class="row g-4">
  <div class="col-lg-7">
    <div id="plan-app"
         data-api="<?= h(url('api/places.php')) ?>"
         data-list-url="<?= h(url('index.php')) ?>"
         data-map-url="<?= h(url('map.php')) ?>"
         data-poya="<?= h(json_encode(POYA_DATES)) ?>"
         aria-live="polite">
      <p class="text-muted">Loading your plan…</p>
    </div>

    <!-- FR-35: save and open; FR-36: print -->
    <section id="plan-save" class="card card-body shadow-sm mt-4" aria-labelledby="save-heading"
             data-api="<?= h(url('api/plan.php')) ?>">
      <h2 id="save-heading" class="h6">Save, open or print a plan</h2>
      <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-success btn-sm" id="save-plan">Save this plan</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="print-plan">Print plan</button>
      </div>
      <div id="save-result" class="mt-2" aria-live="polite"></div>

      <form id="load-form" class="row g-2 mt-1" autocomplete="off">
        <div class="col-sm-7">
          <label for="load-code" class="form-label small mb-1">Open a saved plan</label>
          <input id="load-code" class="form-control text-uppercase" maxlength="8"
                 placeholder="Reference code, e.g. K7M3QX9P" required>
        </div>
        <div class="col-sm-5 d-flex align-items-end">
          <button type="submit" class="btn btn-outline-success w-100">Open</button>
        </div>
      </form>
      <p class="small text-muted mt-2 mb-0">Saving stores only the places, date and start time. No personal details are kept, and saved plans are deleted after 90 days.</p>
    </section>
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
</div><!-- /.plan-screen -->

<!-- FR-36: printable summary. plan-page.js fills it in; it is hidden on screen and is the only part that prints -->
<section id="print-summary" class="d-none d-print-block"></section>
<?php require __DIR__ . '/includes/footer.php'; ?>