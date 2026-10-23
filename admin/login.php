<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

if (AuthService::check()) {
    redirect('admin/index.php');                       // already logged in
}

$username = '';
$error    = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = is_string($_POST['username'] ?? null) ? mb_substr(trim($_POST['username']), 0, 50) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';   // never trimmed or shown

    if (!csrf_valid()) {
        $error = 'The form has expired. Please try again.';                          // NFR-12
    } else {
        $result = ($username === '' || $password === '')
            ? 'invalid'
            : AuthService::attempt($username, $password);

        if ($result === 'ok') {
            flash('success', 'Welcome, ' . $username . '.');
            redirect('admin/index.php');                                             // FR-39
        }
        $error = $result === 'locked'
            ? 'Too many failed attempts. Please try again in ' . AuthService::LOCK_MINUTES . ' minutes.'   // FR-41
            : 'Invalid username or password';                                                             // FR-40
    }
}

$pageTitle = 'Administrator login';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-sm-9 col-md-6 col-lg-4">
    <h1 class="h3 mb-3">Administrator login</h1>

    <?php if ($error !== null): ?>
      <div class="alert alert-danger" role="alert"><?= h($error) ?></div>
    <?php endif; ?>

    <!-- FR-37 -->
    <form method="post" class="card card-body shadow-sm">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input id="username" name="username" class="form-control" maxlength="50"
               autocomplete="username" required autofocus value="<?= h($username) ?>">
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input id="password" name="password" type="password" class="form-control"
               autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn-success w-100">Log in</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
