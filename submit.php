<?php
file_put_contents(__DIR__ . '/mail-debug.log', date('Y-m-d H:i:s') . " submit.php HIT\n", FILE_APPEND);
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

// ── Send mailing on form submit ──────────────────────────────────────────
$_ml_log = function($msg) { file_put_contents(__DIR__ . '/mail-debug.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND); };
$formMailings = $opts['mailings'] ?? [];
$_ml_log('mailings in opts: ' . json_encode($formMailings));
$submitMailings = array_filter($formMailings, fn($m) => !empty($m['on_submit']));
$_ml_log('submit mailings: ' . count($submitMailings));
if ($submitMailings) {
    // Find recipient email from submitted data
    $recipientEmail = '';
    $recipientName  = '';
    foreach ($fields as $field) {
        $tid = (int)($field['type_id'] ?? 3);
        $val = trim($body['field_' . ($field['idx'] ?? 0)] ?? '');
        if ($tid === 6 && filter_var($val, FILTER_VALIDATE_EMAIL)) $recipientEmail = $val;
        if (($tid === 1 || $tid === 2) && $val !== '') $recipientName = $val;
        if ($tid === 3 && stripos($field['title'] ?? '', 'имя') !== false && $val !== '' && !$recipientName) $recipientName = $val;
    }
    $_ml_log("recipient=$recipientEmail name=$recipientName");
    if ($recipientEmail) {
        // Load email module settings
        try {
            $emailCfg = $pdo->prepare("SELECT `setting_value` FROM `tap_settings` WHERE `setting_key`='module_email'");
            $emailCfg->execute();
            $emailSettings = json_decode($emailCfg->fetchColumn() ?: '{}', true);
        } catch (PDOException $e) { $emailSettings = []; }

        if (!empty($emailSettings['domain']) && !empty($emailSettings['password'])) {
            $smtpUser = $emailSettings['domain'];
            $smtpPass = $emailSettings['password'];
            $smtpHost = ($emailSettings['provider'] ?? 'mail') === 'yandex' ? 'smtp.yandex.ru' : 'smtp.mail.ru';
            $sender   = $emailSettings['sender'] ?: $smtpUser;

            // Load and send each mailing
            $mailingIds = array_column($submitMailings, 'id');
            $inPlc = implode(',', array_fill(0, count($mailingIds), '?'));
            $mlStmt = $pdo->prepare("SELECT `subject`,`body`,`template` FROM `tap_mailings` WHERE `id` IN ($inPlc)");
            $mlStmt->execute($mailingIds);
            $mlRows = $mlStmt->fetchAll();

            // Find product info for variables
            $productTitle = '';
            $productPrice = '';
            $productId = $opts['product_id'] ?? '';
            if ($productId) {
                $prStmt = $pdo->prepare("SELECT `title`,`price`,`currency` FROM `tap_products` WHERE `id`=? LIMIT 1");
                $prStmt->execute([$productId]);
                $pr = $prStmt->fetch();
                if ($pr) {
                    $productTitle = $pr['title'];
                    $productPrice = number_format($pr['price'] / 100, 0, '', ' ') . ' ' . ($pr['currency'] ?: '₽');
                }
            }

            foreach ($mlRows as $ml) {
                $subj = $ml['subject'];
                $bodyTpl = $ml['body'];
                $vars = ['{{name}}' => $recipientName ?: 'Клиент', '{{product}}' => $productTitle ?: '—', '{{price}}' => $productPrice ?: '—'];
                $subj = str_replace(array_keys($vars), array_values($vars), $subj);
                $bodyTpl = str_replace(array_keys($vars), array_values($vars), $bodyTpl);

                // Build email body
                $isHtml = ($ml['template'] === 'html');
                if ($isHtml) {
                    $emailBody = $bodyTpl;
                } else {
                    $textHtml = nl2br(htmlspecialchars($bodyTpl));
                    $emailBody = "<html><body style=\"font-family:Arial,sans-serif;color:#333;line-height:1.6\">$textHtml</body></html>";
                }

                // Send via SMTP with fsockopen
                $_ml_log("SENDING to=$recipientEmail subj=$subj sender=$sender smtp=$smtpHost");
                $mailResult = _sendSmtp($smtpHost, 465, $smtpUser, $smtpPass, $sender, $recipientEmail, $subj, $emailBody);
                $_ml_log("RESULT: " . ($mailResult ? 'OK' : 'FAIL'));
            }
        }
    }
}

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

// ── SMTP sender ──────────────────────────────────────────────────────────
function _sendSmtp(string $host, int $port, string $user, string $pass, string $from, string $to, string $subject, string $htmlBody): bool {
    $log = function($msg) { file_put_contents(__DIR__ . '/mail-debug.log', date('H:i:s') . " SMTP: $msg\n", FILE_APPEND); };
    $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $sock = @stream_socket_client("ssl://$host:$port", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$sock) { $log("CONNECT FAIL: $errstr"); return false; }

    $read = function() use ($sock, $log) { $r = fgets($sock, 4096); $log("< " . trim($r)); return $r; };
    $write = function(string $cmd) use ($sock, $log) { $safe = strpos($cmd, 'AUTH') !== false || strlen($cmd) > 100 ? substr($cmd, 0, 30) . '...' : $cmd; $log("> $safe"); fwrite($sock, $cmd . "\r\n"); };

    $read(); // greeting
    $write("EHLO localhost"); $read();
    while (strpos($line = $read(), '250-') === 0) {}

    $authPlain = base64_encode("\0$user\0$pass");
    $write("AUTH PLAIN $authPlain"); $resp = $read();
    if (strpos($resp, '235') === false) {
        $log("AUTH PLAIN failed, trying LOGIN");
        $write("AUTH LOGIN"); $read();
        $write(base64_encode($user)); $read();
        $write(base64_encode($pass)); $resp = $read();
        if (strpos($resp, '235') === false) { $log("AUTH LOGIN FAIL too"); fclose($sock); return false; }
    }

    $write("MAIL FROM:<$from>"); $read();
    $write("RCPT TO:<$to>"); $read();
    $write("DATA"); $read();

    $boundary = md5(uniqid());
    $headers  = "From: $from\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: base64\r\n";
    $headers .= "\r\n";
    $headers .= chunk_split(base64_encode($htmlBody));
    $headers .= "\r\n.";

    $write($headers); $read();
    $write("QUIT");
    fclose($sock);
    return true;
}
