<?php
// ── smtp.bz API sender ───────────────────────────────────────────────────
// Docs: https://docs.smtp.bz/#/api  (POST /v1/smtp/send, header Authorization: <api key>)
function smtpbz_send(string $apiKey, string $from, string $fromName, string $to, string $toName, string $subject, string $htmlBody): array {
    $fields = ['from' => $from, 'to' => $to, 'subject' => $subject, 'html' => $htmlBody];
    if ($fromName !== '') $fields['name'] = $fromName;
    if ($toName !== '')   $fields['to_name'] = $toName;
    $ch = curl_init('https://api.smtp.bz/v1/smtp/send');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $fields,
        CURLOPT_HTTPHEADER     => ['Authorization: ' . $apiKey],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $resp = (string)curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    file_put_contents(dirname(__DIR__) . '/mail-debug.log', date('Y-m-d H:i:s') . " SMTPBZ: HTTP $code " . ($err ?: substr($resp, 0, 500)) . "\n", FILE_APPEND);

    if ($code === 200) return ['ok' => true];
    if ($err) return ['ok' => false, 'error' => 'Ошибка соединения: ' . $err];
    $data = json_decode($resp, true);
    // Error shape: {"result":false,"errors":{"message":"..."}}
    $msg  = is_array($data) ? ($data['errors']['message'] ?? $data['message'] ?? $data['errors'] ?? $data['error'] ?? '') : '';
    if (is_array($msg)) $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
    if (stripos($msg, 'not verified') !== false) {
        return ['ok' => false, 'error' => "Домен отправителя не подтверждён в smtp.bz — добавьте и подтвердите его на https://smtp.bz/panel/domain ($msg)"];
    }
    if ($code === 401) return ['ok' => false, 'error' => 'Неверный API-ключ' . ($msg ? ": $msg" : '')];
    return ['ok' => false, 'error' => "smtp.bz вернул HTTP $code" . ($msg ? ": $msg" : '')];
}

// ── HTTP notifications (webhooks) ────────────────────────────────────────
// Order matches the fields in https://smtp.bz/panel/user
const SMTPBZ_EVENTS = [
    'sent'        => 'Отправлено',
    'delivered'   => 'Доставлено',
    'open'        => 'Открыто',
    'unsubscribe' => 'Отписка',
    'error'       => 'Ошибка',
    'return'      => 'Возвращено',
    'resent'      => 'Повтор',
    'cancel'      => 'Отменено',
];

function smtpbz_ensure_events_table(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `tap_smtpbz_events` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `event` varchar(32) NOT NULL,
        `email` varchar(255) NOT NULL DEFAULT '',
        `message_id` varchar(255) NOT NULL DEFAULT '',
        `payload` text,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// Secret token that protects the webhook URLs; generated on first use
function smtpbz_webhook_token(PDO $pdo): string {
    $st = $pdo->prepare("SELECT `setting_value` FROM `tap_settings` WHERE `setting_key`='smtpbz_webhook_token'");
    $st->execute();
    $token = (string)$st->fetchColumn();
    if ($token === '') {
        $token = bin2hex(random_bytes(16));
        $pdo->prepare("INSERT INTO `tap_settings` (`setting_key`,`setting_value`) VALUES ('smtpbz_webhook_token',?)")->execute([$token]);
    }
    return $token;
}
