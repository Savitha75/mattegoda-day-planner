<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Places';
$count = (int) Database::getConnection()
    ->query('SELECT COUNT(*) FROM place WHERE is_active = 1')
    ->fetchColumn();

require __DIR__ . '/includes/header.php';
?>
<h1 class="h3">Places near Mattegoda</h1>
<p><?= $count ?> active places in the database. The catalogue comes next.</p>
<?php require __DIR__ . '/includes/footer.php'; ?>