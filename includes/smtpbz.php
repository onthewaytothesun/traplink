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
    $msg  = is_array($data) ? ($data['message'] ?? $data['error'] ?? '') : '';
    if (is_array($msg)) $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
    if ($code === 401) return ['ok' => false, 'error' => 'Неверный API-ключ' . ($msg ? ": $msg" : '')];
    return ['ok' => false, 'error' => "smtp.bz вернул HTTP $code" . ($msg ? ": $msg" : '')];
}
