</main>
<footer class="bg-light border-top py-3 mt-auto">
  <div class="container small text-muted d-flex flex-wrap justify-content-between gap-2">
    <span>&copy; <?= date('Y') ?> <?= h(APP_NAME) ?> · ITE2953 · E2410994</span>
    <span>Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors</span>
  </div>
</footer>
<script src="<?= url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<?php if (!empty($useMap)): ?>
<script src="<?= url('assets/vendor/leaflet/leaflet.js') ?>"></script>
<?php endif; ?>
<?php foreach ($pageScripts ?? [] as $js): ?>
<script src="<?= url('assets/js/' . $js) ?>"></script>
<?php endforeach; ?>
</body>
</html>