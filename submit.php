<?php
header('Content-Type: application/json; charset=utf-8');

$cfg = require __DIR__ . '/config.php';
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $cfg['db_host'] ?? 'localhost', $cfg['db_name'] ?? ''),
        $cfg['db_user'] ?? '', $cfg['db_pass'] ?? ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['error' => 'DB unavailable']); exit;
}

$pdo->exec("CREATE TABLE IF NOT EXISTS `tap_submissions` (
    `id` int NOT NULL AUTO_INCREMENT,
    `block_id` varchar(64) DEFAULT NULL,
    `page_id` varchar(64) DEFAULT NULL,
    `data` longtext,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_block` (`block_id`),
    KEY `idx_page` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$body    = json_decode(file_get_contents('php://input'), true) ?? [];
$blockId = preg_replace('/[^a-f0-9]/', '', $body['block_id'] ?? '');
$pageId  = preg_replace('/[^a-z0-9\-]/', '', $body['page_id'] ?? '');

if (!$blockId) { echo json_encode(['error' => 'Invalid request']); exit; }

$stmt = $pdo->prepare("SELECT `options` FROM `tap_blocks` WHERE `id`=? AND `is_visible`=1 LIMIT 1");
$stmt->execute([$blockId]);
$block = $stmt->fetch();
if (!$block) { echo json_encode(['error' => 'Block not found']); exit; }

$opts   = json_decode($block['options'] ?? '{}', true) ?: [];
$fields = $opts['fields'] ?? [];
$data   = [];

foreach ($fields as $field) {
    $key = 'field_' . ($field['idx'] ?? 0);
    $val = trim($body[$key] ?? '');
    if (!empty($field['required']) && $val === '') {
        echo json_encode(['error' => 'Заполните все обязательные поля']); exit;
    }
    $data[$field['title'] ?? $key] = $val;
}

$pdo->prepare("INSERT INTO `tap_submissions` (`block_id`,`page_id`,`data`) VALUES (?,?,?)")
    ->execute([$blockId, $pageId ?: null, json_encode($data, JSON_UNESCAPED_UNICODE)]);

echo json_encode(['ok' => true]);
