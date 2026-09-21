<?php
require __DIR__ . '/_session.php';

if (!($_SESSION['admin_auth'] ?? false)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Upload error']);
    exit;
}

$allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp','image/x-icon'=>'ico','image/vnd.microsoft.icon'=>'ico','image/svg+xml'=>'svg','audio/mpeg'=>'mp3','audio/mp4'=>'m4a','audio/ogg'=>'ogg','audio/wav'=>'wav','audio/x-wav'=>'wav','audio/flac'=>'flac'];
$mime = mime_content_type($file['tmp_name']);
if (!array_key_exists($mime, $allowed)) {
    echo json_encode(['error' => 'Invalid file type']);
    exit;
}

$ext = $allowed[$mime];
$name = bin2hex(random_bytes(8)) . '.' . $ext;
$dir  = dirname(__DIR__) . '/uploads/';
$dest = $dir . $name;

if (!is_dir($dir)) mkdir($dir, 0755, true);

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['error' => 'Save error']);
    exit;
}

echo json_encode(['ok' => true, 'url' => '/uploads/' . $name]);
