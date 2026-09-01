<?php
// Root entry point: serves main page from DB, or falls back to old dashboard
require __DIR__ . '/page.php';

$cfg = require __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $cfg['db_host'] ?? 'localhost', $cfg['db_name'] ?? ''),
        $cfg['db_user'] ?? '', $cfg['db_pass'] ?? ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // DB not available — show dashboard stub
    readfile(__DIR__ . '/index.html');
    exit;
}

// Check if tap_pages table exists
try {
    $page = $pdo->query("SELECT * FROM `tap_pages` WHERE `is_main`=1 LIMIT 1")->fetch();
} catch (PDOException $e) {
    // Table doesn't exist yet
    readfile(__DIR__ . '/index.html');
    exit;
}

if (!$page) {
    // No main page set — show dashboard
    readfile(__DIR__ . '/index.html');
    exit;
}

$blocks = $pdo->prepare(
    "SELECT * FROM `tap_blocks` WHERE `page_id`=? AND `is_visible`=1 ORDER BY `sort_order`,`created_at`"
);
$blocks->execute([$page['id']]);

renderPage($page, $blocks->fetchAll(), $pdo);
