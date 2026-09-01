<?php
// Serves pages at /p/{slug}
require __DIR__ . '/page.php';

$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['slug'] ?? ''));

if (!$slug) { http_response_code(404); echo '404'; exit; }

$cfg = require __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $cfg['db_host'] ?? 'localhost', $cfg['db_name'] ?? ''),
        $cfg['db_user'] ?? '', $cfg['db_pass'] ?? ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(503); echo 'DB unavailable'; exit;
}

$stmt = $pdo->prepare("SELECT * FROM `tap_pages` WHERE `slug`=? LIMIT 1");
$stmt->execute([$slug]);
$page = $stmt->fetch();

if (!$page) { http_response_code(404); echo '404 — страница не найдена'; exit; }

$blocks = $pdo->prepare(
    "SELECT * FROM `tap_blocks` WHERE `page_id`=? AND `is_visible`=1 ORDER BY `sort_order`,`created_at`"
);
$blocks->execute([$page['id']]);

renderPage($page, $blocks->fetchAll(), $pdo);
