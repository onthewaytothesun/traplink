<?php
session_start();

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

$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$mime = mime_content_type($file['tmp_name']);
if (!in_array($mime, $allowed, true)) {
    echo json_encode(['error' => 'Invalid file type']);
    exit;
}

$ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'][$mime];
$name = bin2hex(random_bytes(8)) . '.' . $ext;
$dir  = dirname(__DIR__) . '/uploads/';
$dest = $dir . $name;

if (!is_dir($dir)) mkdir($dir, 0755, true);

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['error' => 'Save error']);
    exit;
}

echo json_encode(['ok' => true, 'url' => '/uploads/' . $name]);
