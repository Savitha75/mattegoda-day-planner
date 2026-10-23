<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
AuthService::requireLogin();                           // FR-42, FR-43: always the first line of an admin page

$current   = 'admin/index.php';                        // highlights "Admin" in the navbar
$pageTitle = 'Administration';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Administration</h1>
<p>You are logged in as <strong><?= h($_SESSION['admin_name']) ?></strong>.</p>
<p class="text-muted">Place management (FR-44 to FR-51) is added in the next step.</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
