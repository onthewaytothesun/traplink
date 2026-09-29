<?php
// Private storage outside the document root: shared-host PHP cleanup must not
// remove our sessions using another application's shorter lifetime.
$ttl = 7 * 24 * 3600;
$sessionDirectory = dirname(__DIR__, 2) . '/.traplink-admin-sessions';
if (!is_dir($sessionDirectory) && !mkdir($sessionDirectory, 0700, true) && !is_dir($sessionDirectory)) {
    throw new RuntimeException('Cannot create admin session storage');
}
session_save_path($sessionDirectory);
ini_set('session.gc_maxlifetime', (string) $ttl);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => $ttl,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (!session_start()) {
    throw new RuntimeException('Cannot start admin session');
}
$now = time();
if (!empty($_SESSION['admin_auth'])) {
    $lastActivity = (int) ($_SESSION['admin_last_activity'] ?? 0);
    if ($lastActivity && $lastActivity <= $now - $ttl) {
        // Expiry is enforced even when probabilistic garbage collection has not run.
        $_SESSION = [];
        session_regenerate_id(true);
    } else {
        $_SESSION['admin_last_activity'] = $now;
        setcookie(session_name(), session_id(), [
            'expires' => $now + $ttl,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
