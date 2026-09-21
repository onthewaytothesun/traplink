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
    $tid = (int)($field['type_id'] ?? 3);
    $val = trim($body[$key] ?? '');
    // Checkbox: treat missing as "Нет"
    if ($tid === 11) {
        $val = $val !== '' ? 'Да' : 'Нет';
    }
    if (!empty($field['required']) && $val === '') {
        echo json_encode(['error' => 'Заполните все обязательные поля']); exit;
    }
    $data[$field['title'] ?? $key] = $val;
}

$pdo->prepare("INSERT INTO `tap_submissions` (`block_id`,`page_id`,`data`) VALUES (?,?,?)")
    ->execute([$blockId, $pageId ?: null, json_encode($data, JSON_UNESCAPED_UNICODE)]);

// If form has a linked product, create a payment order
$productId = preg_replace('/[^a-z0-9\-]/', '', $opts['product_id'] ?? '');
if ($productId) {
    // Load product
    $prodStmt = $pdo->prepare("SELECT * FROM `tap_products` WHERE `id`=? AND `is_active`=1 LIMIT 1");
    $prodStmt->execute([$productId]);
    $product = $prodStmt->fetch();

    if ($product) {
        // Load active GetPlatinum config
        $pm = $pdo->query("SELECT * FROM `tap_payments` WHERE `provider`='getplatinum' AND `is_active`=1 LIMIT 1")->fetch();
        if ($pm) {
            $creds   = json_decode($pm['credentials'] ?: '{}', true);
            $apiKey  = $creds['api_key'] ?? '';
            $baseUrl = rtrim($creds['base_url'] ?? 'https://example.getplatinum.ru/api/public/v2/pay', '/');
            $vat     = $creds['vat'] ?? 'none';
            $prefix  = intval($creds['position_prefix'] ?? 12);

            if ($apiKey) {
                $amountKop = (int)$product['price'];
                $dealId    = 'D-' . bin2hex(random_bytes(8));
                $orderId   = bin2hex(random_bytes(8));

                $scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host      = $_SERVER['HTTP_HOST'] ?? '';
                $notifyUrl = $scheme . '://' . $host . '/payment-callback.php';

                // Success URL: product's success page or site root
                $successUrl = $scheme . '://' . $host . '/';
                if ($product['success_page_id']) {
                    $pgStmt = $pdo->prepare("SELECT `slug`,`is_main` FROM `tap_pages` WHERE `id`=? LIMIT 1");
                    $pgStmt->execute([$product['success_page_id']]);
                    $pg = $pgStmt->fetch();
                    if ($pg) {
                        $successUrl = $pg['is_main'] ? ($scheme . '://' . $host . '/') : ($scheme . '://' . $host . '/p/' . $pg['slug']);
                    }
                }

                $clientParams = ['clientId' => 'c-' . bin2hex(random_bytes(4))];
                // Try to pick email/phone from submitted data
                foreach ($data as $k => $v) {
                    if (str_contains(mb_strtolower($k), 'email') && filter_var($v, FILTER_VALIDATE_EMAIL)) {
                        $clientParams['email'] = $v;
                    }
                    if (str_contains(mb_strtolower($k), 'телефон') || str_contains(mb_strtolower($k), 'phone') || str_contains(mb_strtolower($k), 'тел')) {
                        $clientParams['phone'] = $v;
                    }
                }

                $payload = [
                    'dealId'          => $dealId,
                    'amount'          => $amountKop,
                    'currency'        => $product['currency'] ?: 'RUB',
                    'positions'       => [[
                        'prefix'   => $prefix,
                        'name'     => $product['title'],
                        'price'    => $amountKop,
                        'quantity' => 1,
                        'vat'      => $vat,
                    ]],
                    'clientParams'    => $clientParams,
                    'notificationUrl' => $notifyUrl,
                    'successUrl'      => $successUrl,
                ];

                $ch = curl_init($baseUrl . '/init-payment-url');
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => json_encode($payload),
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $apiKey,
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 30,
                ]);
                $resp     = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $gpData = json_decode($resp, true);

                if ($httpCode === 200 && empty($gpData['errorCode']) && !empty($gpData['formUrl'])) {
                    $pdo->prepare("INSERT INTO `tap_orders` (`id`,`deal_id`,`payment_id`,`amount`,`currency`,`status`,`form_url`,`custom_params`) VALUES (?,?,?,?,?,?,?,?)")
                        ->execute([$orderId, $dealId, $pm['id'], $amountKop, $product['currency'] ?: 'RUB', 'pending', $gpData['formUrl'], json_encode(['product_id' => $productId, 'block_id' => $blockId])]);

                    echo json_encode(['ok' => true, 'form_url' => $gpData['formUrl']]);
                    exit;
                }
                // If payment init failed, still return ok (submission saved) but no redirect
            }
        }
    }
}

echo json_encode(['ok' => true]);
