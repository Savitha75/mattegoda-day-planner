<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

// FR-43: log out only by POST with a valid token, so a link on another website cannot log the admin out
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    redirect('admin/index.php');
}

AuthService::logout();
flash('success', 'You have been logged out.');
redirect('admin/login.php');
