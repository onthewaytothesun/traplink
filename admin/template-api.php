<?php
// Included only by authenticated api.php; direct requests must not access the service.
if (!isset($pdo, $action, $body) || !($_SESSION['admin_auth'] ?? false)) {
    http_response_code(403); exit;
}
require_once dirname(__DIR__) . '/includes/page-templates.php';
try {
    $method = $_SERVER['REQUEST_METHOD'];
    if (($action === 'templates' && $method !== 'GET') || ($action !== 'templates' && $method !== 'POST')) {
        http_response_code(405);
        header('Allow: ' . ($action === 'templates' ? 'GET' : 'POST'));
        echo json_encode(['error'=>'Неверный метод запроса.']);
        return;
    }
    $service = new PageTemplateService($pdo);
    $service->ensureSchema();
    if ($action === 'templates') {
        $result = ['templates'=>$service->list(), 'categories'=>$service->categories()];
    } elseif ($action === 'savePageTemplate') {
        $result = ['ok'=>true, 'template'=>$service->save((string)($body['page_id']??''),(string)($body['title']??''),(string)($body['description']??''),(string)($body['category']??''))];
    } elseif ($action === 'createFromTemplate') {
        $result = ['ok'=>true,'page'=>$service->create((string)($body['template_id']??''),(string)($body['request_id']??''))];
    } elseif ($action === 'savePageDesign') {
        $service->saveDesign((string)($body['page_id']??''),is_array($body['design']??null)?$body['design']:[]);
        $result = ['ok'=>true];
    } else {
        $service->delete((string)($body['id']??''));
        $result = ['ok'=>true];
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (OutOfBoundsException $e) {
    http_response_code(404); echo json_encode(['error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    http_response_code(422); echo json_encode(['error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Page templates: ' . $e->getMessage());
    http_response_code(500); echo json_encode(['error'=>'Не удалось выполнить действие. Попробуйте ещё раз.'], JSON_UNESCAPED_UNICODE);
}
