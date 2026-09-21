<?php
/**
 * GetPlatinum payment callback handler.
 * URL: /payment-callback.php
 *
 * Receives POST with JSON body + X-Checksum header (HMAC-SHA256).
 * Updates tap_orders status based on notification.
 */

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
    http_response_code(500);
    echo json_encode(['error' => 'DB']);
    exit;
}

$rawBody = file_get_contents('php://input');
if (!$rawBody) {
    http_response_code(400);
    echo json_encode(['error' => 'empty body']);
    exit;
}

$data = json_decode($rawBody, true);
if (!$data || empty($data['dealId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid payload']);
    exit;
}

$dealId   = $data['dealId'];
$checksum = $_SERVER['HTTP_X_CHECKSUM'] ?? '';

// Find the order and its payment config
$stmt = $pdo->prepare("SELECT o.*, p.credentials FROM `tap_orders` o JOIN `tap_payments` p ON o.payment_id = p.id WHERE o.deal_id = ?");
$stmt->execute([$dealId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'order not found']);
    exit;
}

$creds  = json_decode($order['credentials'] ?: '{}', true);
$apiKey = $creds['api_key'] ?? '';

// Verify HMAC-SHA256 checksum
if ($apiKey && $checksum) {
    $expected = strtoupper(hash_hmac('sha256', $rawBody, $apiKey));
    if (!hash_equals($expected, strtoupper($checksum))) {
        http_response_code(403);
        echo json_encode(['error' => 'checksum mismatch']);
        exit;
    }
}

// Determine notification type
$notifType = intval($data['notificationType'] ?? 0);
$isSuccess = !empty($data['isSuccess']);

// Log callback data
$pdo->prepare("UPDATE `tap_orders` SET `callback_data` = ? WHERE `deal_id` = ?")
    ->execute([json_encode($data), $dealId]);

// Update status based on notification
if ($notifType === 1) {
    // Payment notification
    $newStatus = $isSuccess ? 'paid' : 'failed';
    $paidAt    = $isSuccess ? date('Y-m-d H:i:s') : null;

    $stmt = $pdo->prepare("UPDATE `tap_orders` SET `status` = ?, `paid_at` = ? WHERE `deal_id` = ? AND `status` = 'pending'");
    $stmt->execute([$newStatus, $paidAt, $dealId]);
} elseif ($notifType === 7) {
    // Deal created notification — order already exists, nothing to do
}

echo json_encode(['ok' => true]);
