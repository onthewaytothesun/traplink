<?php
require __DIR__ . '/_session.php';
if (!($_SESSION['admin_auth'] ?? false)) { http_response_code(401); exit('Войдите в админку.'); }
// Preview cannot run imported HTML scripts, submit forms, or navigate the parent.
header("Content-Security-Policy: sandbox; default-src 'none'; style-src 'self' 'unsafe-inline' https:; img-src 'self' https: data:; font-src 'self' https: data:; script-src 'none'; form-action 'none'; base-uri 'none'; frame-ancestors 'self'");
header('Cache-Control: no-store');
header('Content-Type: text/html; charset=utf-8');
require_once dirname(__DIR__) . '/includes/page-templates.php';
require_once dirname(__DIR__) . '/page.php';
try {
    $cfg = require dirname(__DIR__) . '/config.php';
    $pdo = new PDO(sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4',$cfg['db_host']??'localhost',$cfg['db_name']??''),$cfg['db_user']??'',$cfg['db_pass']??'', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $service = new PageTemplateService($pdo);
    $service->ensureSchema();
    $snapshot = $service->get((string)($_GET['id']??''))['snapshot'];
    $page = $snapshot['page'];
    $page['_template_sections'] = $snapshot['sections'];
    $blocks = $snapshot['blocks'];
    foreach ($blocks as &$block) {
        $block['page_id'] = $page['id'];
        $block['options'] = json_encode($block['options'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
    unset($block);
    // No site-wide tracking/custom head code inside a preview.
    $page['theme']['_template_preview'] = true;
    renderPage($page, $blocks, $pdo);
} catch (OutOfBoundsException $e) {
    http_response_code(404); echo 'Шаблон не найден.';
} catch (Throwable $e) {
    error_log('Template preview: ' . $e->getMessage());
    http_response_code(500); echo 'Не удалось загрузить предпросмотр.';
}
