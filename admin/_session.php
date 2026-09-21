<?php
// 3 days session lifetime, refreshed on every request
$ttl = 3 * 24 * 3600;
ini_set('session.gc_maxlifetime', $ttl);
ini_set('session.cookie_lifetime', $ttl);
session_set_cookie_params($ttl, '/', '', true, true);
session_start();
// Refresh cookie expiry on every hit
if (isset($_SESSION['admin_auth']) && $_SESSION['admin_auth']) {
    setcookie(session_name(), session_id(), time() + $ttl, '/', '', true, true);
}
