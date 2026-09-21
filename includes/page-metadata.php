<?php
function updatePageMetadata(PDO $pdo, array $body): void {
    $id = $body['id'] ?? '';
    $title = mb_substr(trim($body['title'] ?? ''), 0, 255);
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($body['slug'] ?? ''));
    // Sidebar renames do not submit theme: keep the saved template design.
    $theme = array_key_exists('theme', $body) ? json_encode($body['theme'], JSON_THROW_ON_ERROR) : null;
    $folderId = array_key_exists('folder_id', $body) ? (($body['folder_id'] ?? '') ?: null) : false;
    if (!$id || !$title) return;
    if ($folderId !== false) {
        $q = $pdo->prepare('UPDATE tap_pages SET title=?,slug=?,theme=COALESCE(?,theme),folder_id=? WHERE id=?');
        $q->execute([$title,$slug,$theme,$folderId,$id]);
    } else {
        $q = $pdo->prepare('UPDATE tap_pages SET title=?,slug=?,theme=COALESCE(?,theme) WHERE id=?');
        $q->execute([$title,$slug,$theme,$id]);
    }
}
