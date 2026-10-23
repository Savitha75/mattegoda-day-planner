</main>
<footer class="bg-light border-top py-3 mt-auto">
  <div class="container small text-muted d-flex flex-wrap justify-content-between gap-2">
    <span>&copy; <?= date('Y') ?> <?= h(APP_NAME) ?> · ITE2953 · E2410994 · <a class="link-secondary" href="<?= url('admin/login.php') ?>">Administrator login</a></span>
    <span>Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors</span>
  </div>
</footer>

<!-- Messages from the day planner (FR-26, FR-27) -->
<div id="toast-area" class="toast-container position-fixed bottom-0 end-0 p-3" aria-live="polite" aria-atomic="true"></div>

<script src="<?= url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<?php if (!empty($useMap)): ?>
<script src="<?= url('assets/vendor/leaflet/leaflet.js') ?>"></script>
<?php endif; ?>
<script src="<?= url('assets/js/plan.js') ?>"></script>
<?php foreach ($pageScripts ?? [] as $js): ?>
<script src="<?= url('assets/js/' . $js) ?>"></script>
<?php endforeach; ?>
<?php if (str_contains($_SERVER['SCRIPT_NAME'], '/admin/')): ?>
<script>
  // FR-43: an admin page restored from the Back/Forward cache is reloaded, so after logout
  // the server sends the login page instead of the browser showing an old copy from memory
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) { window.location.reload(); }
  });
</script>
<?php endif; ?>
</body>
</html>