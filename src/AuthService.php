<?php
declare(strict_types=1);

/** Administrator login, lockout, page guard and logout (FR-37 to FR-43, NFR-09). */
final class AuthService
{
    public const MAX_FAILS    = 5;    // FR-41: after five failed attempts in a row,
    public const LOCK_MINUTES = 15;   // that username is locked for 15 minutes

    // bcrypt hash of a random string. When the username does not exist we still check a password
    // against it, so a wrong username takes as long as a wrong password (no timing clue).
    private const DUMMY_HASH = '$2y$10$FlhQ9Tu2ATPc1UCUkW3S6uSK8ZlqjqHdKTFJObtkZAaqiViinuoGC';

    /** FR-39 to FR-41. Returns 'ok', 'invalid' or 'locked'. */
    public static function attempt(string $username, string $password): string
    {
        $db   = Database::getConnection();
        $stmt = $db->prepare('SELECT admin_id, username, password_hash, failed_attempts,
                                     (locked_until IS NOT NULL AND locked_until > NOW()) AS is_locked
                                FROM admin_user
                               WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user === false) {
            password_verify($password, self::DUMMY_HASH);    // same amount of work as a real check
            return 'invalid';
        }
        if ((int) $user['is_locked'] === 1) {
            return 'locked';                                 // FR-41: no password check while locked
        }

        if (!password_verify($password, $user['password_hash'])) {
            $fails = (int) $user['failed_attempts'] + 1;
            if ($fails >= self::MAX_FAILS) {
                $db->prepare('UPDATE admin_user
                                 SET failed_attempts = 0,
                                     locked_until = NOW() + INTERVAL ' . self::LOCK_MINUTES . ' MINUTE
                               WHERE admin_id = ?')->execute([$user['admin_id']]);
                log_error('Admin login: "' . $user['username'] . '" locked for ' . self::LOCK_MINUTES
                    . ' minutes after ' . self::MAX_FAILS . ' failed attempts.');
            } else {
                $db->prepare('UPDATE admin_user SET failed_attempts = ? WHERE admin_id = ?')
                   ->execute([$fails, $user['admin_id']]);
            }
            return 'invalid';
        }

        // Correct password: keep the hash up to date with PHP's current algorithm and cost
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $db->prepare('UPDATE admin_user SET password_hash = ? WHERE admin_id = ?')
               ->execute([password_hash($password, PASSWORD_DEFAULT), $user['admin_id']]);
        }
        $db->prepare('UPDATE admin_user SET failed_attempts = 0, locked_until = NULL, last_login = NOW()
                       WHERE admin_id = ?')->execute([$user['admin_id']]);

        session_regenerate_id(true);           // new session ID after login (protects against session fixation)
        unset($_SESSION['csrf']);              // and a fresh form token
        $_SESSION['admin_id']      = (int) $user['admin_id'];
        $_SESSION['admin_name']    = $user['username'];
        $_SESSION['last_activity'] = time();
        return 'ok';
    }

    public static function check(): bool
    {
        return isset($_SESSION['admin_id']);
    }

    /** FR-42, FR-43: call this first on every administration page. */
    public static function requireLogin(): void
    {
        header('Cache-Control: no-store');     // after logout, the Back button cannot show admin pages

        if (!self::check()) {
            flash('warning', 'Please log in to continue.');
            redirect('admin/login.php');
        }
        if (time() - ($_SESSION['last_activity'] ?? 0) > ADMIN_IDLE_MINUTES * 60) {
            self::logout();
            flash('warning', 'You were logged out after ' . ADMIN_IDLE_MINUTES . ' min of inactivity.');
            redirect('admin/login.php');
        }
        $_SESSION['last_activity'] = time();   // still active: restart the 30-minute clock
    }

    /** FR-43: forget the login and replace the session ID (the old one is destroyed on the server). */
    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}
