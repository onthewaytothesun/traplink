<?php
// Root entry point: serves the main page from DB. Until there is one, shows
// index.html if it exists, or a hint to open the admin panel.
require __DIR__ . '/page.php';

function showFallback(): void {
    if (is_file(__DIR__ . '/index.html')) {
        readfile(__DIR__ . '/index.html');
        return;
    }
    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Мультиссылка</title><body style="font-family:system-ui,sans-serif;max-width:480px;margin:15vh auto;padding:0 16px;color:#343a40">'
        . '<h1 style="font-size:24px">Главная страница ещё не выбрана</h1>'
        . '<p>Откройте <a href="/admin/">админку</a>, создайте страницу и отметьте её главной.</p></body>';
}

$cfg = require __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $cfg['db_host'] ?? 'localhost', $cfg['db_name'] ?? ''),
        $cfg['db_user'] ?? '', $cfg['db_pass'] ?? ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // DB not available
    showFallback();
    exit;
}

// Check if tap_pages table exists
try {
    $page = $pdo->query("SELECT * FROM `tap_pages` WHERE `is_main`=1 LIMIT 1")->fetch();
} catch (PDOException $e) {
    // Tables are created on the first visit to the admin panel
    showFallback();
    exit;
}

if (!$page) {
    // No main page set
    showFallback();
    exit;
}

$blocks = $pdo->prepare(
    "SELECT * FROM `tap_blocks` WHERE `page_id`=? AND `is_visible`=1 ORDER BY `sort_order`,`created_at`"
);
$blocks->execute([$page['id']]);

renderPage($page, $blocks->fetchAll(), $pdo);
