<?php
/**
 * smtp.bz HTTP notification handler.
 * URL: /smtpbz-hook.php?event={sent|delivered|open|...}&token={secret}
 *
 * smtp.bz doesn't document the payload, so the whole request (query, form, JSON)
 * is stored as-is; email / message id are picked from common field names.
 */

header('Content-Type: application/json; charset=utf-8');

$cfg = require __DIR__ . '/config.php';
require __DIR__ . '/includes/smtpbz.php';

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $cfg['db_host'] ?? 'localhost', $cfg['db_name'] ?? ''),
        $cfg['db_user'] ?? '', $cfg['db_pass'] ?? ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB']);
    exit;
}

$event = $_GET['event'] ?? '';
if (!isset(SMTPBZ_EVENTS[$event])) {
    http_response_code(400);
    echo json_encode(['error' => 'unknown event']);
    exit;
}
if (!hash_equals(smtpbz_webhook_token($pdo), (string)($_GET['token'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$raw  = (string)file_get_contents('php://input');
$json = json_decode($raw, true);
$data = array_merge(
    array_diff_key($_GET, ['event' => 1, 'token' => 1]),
    $_POST,
    is_array($json) ? $json : []
);

$pick = function(array $keys) use ($data): string {
    foreach ($keys as $k) {
        if (isset($data[$k]) && is_scalar($data[$k]) && $data[$k] !== '') return substr((string)$data[$k], 0, 255);
    }
    return '';
};
$email     = $pick(['email', 'to', 'recipient', 'rcpt', 'mail']);
$messageId = $pick(['messageid', 'message_id', 'messageId', 'id', 'msgid']);

$payload = $data ?: [];
if (!$payload && $raw !== '') $payload = ['raw' => $raw];

smtpbz_ensure_events_table($pdo);
$pdo->prepare("INSERT INTO `tap_smtpbz_events` (`event`,`email`,`message_id`,`payload`) VALUES (?,?,?,?)")
    ->execute([$event, $email, $messageId, substr(json_encode($payload, JSON_UNESCAPED_UNICODE), 0, 60000)]);

// Keep the table small
if (random_int(1, 50) === 1) {
    $pdo->exec("DELETE FROM `tap_smtpbz_events` WHERE `created_at` < NOW() - INTERVAL 90 DAY");
}

echo json_encode(['ok' => true]);
