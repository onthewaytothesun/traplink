<?php
require __DIR__ . '/_session.php';

if (!($_SESSION['admin_auth'] ?? false)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$cfg = require dirname(__DIR__) . '/config.php';

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $cfg['db_host'] ?? 'localhost', $cfg['db_name'] ?? ''),
        $cfg['db_user'] ?? '', $cfg['db_pass'] ?? ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['error' => 'DB: ' . $e->getMessage()]);
    exit;
}

// Create tables
$pdo->exec("CREATE TABLE IF NOT EXISTS `tap_pages` (
    `id` varchar(64) NOT NULL,
    `title` varchar(255) NOT NULL DEFAULT 'Новая страница',
    `slug` varchar(128) NOT NULL DEFAULT '',
    `is_main` tinyint(1) NOT NULL DEFAULT 0,
    `sort_order` int NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS `tap_sections` (
    `id` varchar(64) NOT NULL,
    `page_id` varchar(64) DEFAULT NULL,
    `title` varchar(255) NOT NULL DEFAULT '',
    `sort_order` int NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_page` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS `tap_blocks` (
    `id` varchar(64) NOT NULL,
    `page_id` varchar(64) DEFAULT NULL,
    `section_id` varchar(64) DEFAULT NULL,
    `block_type_id` int NOT NULL,
    `block_type_name` varchar(64) NOT NULL,
    `options` longtext,
    `is_visible` tinyint(1) NOT NULL DEFAULT 1,
    `sort_order` int NOT NULL DEFAULT 0,
    `anchor` varchar(128) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_page` (`page_id`),
    KEY `idx_section` (`section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Migrate old tables: add page_id if missing, add theme column
foreach ([
    "ALTER TABLE `tap_sections` ADD COLUMN `page_id` varchar(64) DEFAULT NULL",
    "ALTER TABLE `tap_sections` ADD COLUMN `options` longtext DEFAULT NULL",
    "ALTER TABLE `tap_blocks` ADD COLUMN `page_id` varchar(64) DEFAULT NULL",
    "ALTER TABLE `tap_pages` ADD COLUMN `theme` longtext DEFAULT NULL",
    "ALTER TABLE `tap_pages` ADD COLUMN `folder_id` varchar(64) DEFAULT NULL",
] as $sql) {
    try { $pdo->exec($sql); } catch (PDOException $e) { /* column exists */ }
}

$pdo->exec("CREATE TABLE IF NOT EXISTS `tap_folders` (
    `id` varchar(64) NOT NULL,
    `title` varchar(255) NOT NULL DEFAULT 'Папка',
    `sort_order` int NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Create settings table
$pdo->exec("CREATE TABLE IF NOT EXISTS `tap_settings` (
    `setting_key` varchar(128) NOT NULL,
    `setting_value` longtext,
    PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

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

$pdo->exec("CREATE TABLE IF NOT EXISTS `tap_payments` (
    `id` varchar(64) NOT NULL,
    `provider` varchar(32) NOT NULL,
    `label` varchar(128) NOT NULL DEFAULT '',
    `credentials` longtext,
    `is_active` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS `tap_orders` (
    `id` varchar(64) NOT NULL,
    `deal_id` varchar(255) NOT NULL,
    `payment_id` varchar(64) NOT NULL COMMENT 'FK tap_payments',
    `amount` int NOT NULL COMMENT 'kopecks',
    `currency` varchar(3) NOT NULL DEFAULT 'RUB',
    `status` varchar(16) NOT NULL DEFAULT 'pending',
    `form_url` text,
    `callback_data` longtext,
    `custom_params` longtext,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `paid_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_deal` (`deal_id`),
    KEY `idx_payment` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS `tap_products` (
    `id` varchar(64) NOT NULL,
    `title` varchar(255) NOT NULL DEFAULT '',
    `price` int NOT NULL DEFAULT 0 COMMENT 'kopecks',
    `currency` varchar(3) NOT NULL DEFAULT 'RUB',
    `image_url` varchar(512) DEFAULT NULL,
    `success_page_id` varchar(64) DEFAULT NULL COMMENT 'FK tap_pages — page after payment',
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `sort_order` int NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS `tap_mailings` (
    `id` varchar(64) NOT NULL,
    `subject` varchar(500) NOT NULL DEFAULT '',
    `body` longtext,
    `template` varchar(32) NOT NULL DEFAULT 'plain',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$action = $_GET['action'] ?? '';
$body   = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (json_decode(file_get_contents('php://input'), true) ?? [])
    : [];

if (in_array($action, ['templates', 'savePageTemplate', 'createFromTemplate', 'deleteTemplate', 'savePageDesign'], true)) {
    require __DIR__ . '/template-api.php';
    exit;
}

switch ($action) {

    // ── Pages ───────────────────────────────────────────────────────────────

    case 'pages':
        $rows = $pdo->query("SELECT * FROM `tap_pages` ORDER BY `sort_order`, `created_at`")->fetchAll();
        foreach ($rows as &$r) {
            $r['is_main']   = (bool)(int)$r['is_main'];
            $r['theme']     = json_decode($r['theme'] ?: '{}', true) ?: (object)[];
            $r['folder_id'] = $r['folder_id'] ?? null;
        }
        echo json_encode(['pages' => $rows]);
        break;

    case 'addPage':
        $title    = substr(trim($body['title'] ?? 'Новая страница'), 0, 255);
        $slug     = preg_replace('/[^a-z0-9\-]/', '', strtolower($body['slug'] ?? ''));
        $folderId = ($body['folder_id'] ?? '') ?: null;
        $id       = 'p-' . bin2hex(random_bytes(4));
        $ord      = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM `tap_pages`")->fetchColumn();
        $pdo->prepare("INSERT INTO `tap_pages` (`id`,`title`,`slug`,`sort_order`,`folder_id`) VALUES (?,?,?,?,?)")
            ->execute([$id, $title, $slug, $ord + 10, $folderId]);
        echo json_encode(['ok' => true, 'page' => [
            'id' => $id, 'title' => $title, 'slug' => $slug,
            'is_main' => false, 'sort_order' => $ord + 10, 'folder_id' => $folderId,
        ]]);
        break;

    case 'updatePage':
        require_once dirname(__DIR__) . '/includes/page-metadata.php';
        updatePageMetadata($pdo, $body);
        echo json_encode(['ok' => true]);
        break;

    // ── Folders ─────────────────────────────────────────────────────────────

    case 'folders':
        $rows = $pdo->query("SELECT * FROM `tap_folders` ORDER BY `sort_order`, `created_at`")->fetchAll();
        echo json_encode(['folders' => $rows]);
        break;

    case 'addFolder':
        $title = substr(trim($body['title'] ?? 'Папка'), 0, 255);
        $id    = 'f-' . bin2hex(random_bytes(4));
        $ord   = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM `tap_folders`")->fetchColumn();
        $pdo->prepare("INSERT INTO `tap_folders` (`id`,`title`,`sort_order`) VALUES (?,?,?)")
            ->execute([$id, $title, $ord + 10]);
        echo json_encode(['ok' => true, 'folder' => ['id' => $id, 'title' => $title, 'sort_order' => $ord + 10]]);
        break;

    case 'updateFolder':
        $id    = $body['id'] ?? '';
        $title = substr(trim($body['title'] ?? ''), 0, 255);
        if ($id && $title) {
            $pdo->prepare("UPDATE `tap_folders` SET `title`=? WHERE `id`=?")->execute([$title, $id]);
        }
        echo json_encode(['ok' => true]);
        break;

    case 'deleteFolder':
        $id = $body['id'] ?? '';
        if ($id) {
            $pdo->prepare("UPDATE `tap_pages` SET `folder_id`=NULL WHERE `folder_id`=?")->execute([$id]);
            $pdo->prepare("DELETE FROM `tap_folders` WHERE `id`=?")->execute([$id]);
        }
        echo json_encode(['ok' => true]);
        break;

    case 'setMain':
        $id = $body['id'] ?? '';
        if ($id) {
            $pdo->exec("UPDATE `tap_pages` SET `is_main`=0");
            $pdo->prepare("UPDATE `tap_pages` SET `is_main`=1 WHERE `id`=?")->execute([$id]);
        }
        echo json_encode(['ok' => true]);
        break;

    case 'deletePage':
        $id = $body['id'] ?? '';
        if ($id) {
            $pdo->prepare("DELETE FROM `tap_pages` WHERE `id`=?")->execute([$id]);
            $pdo->prepare("UPDATE `tap_blocks` SET `page_id`=NULL WHERE `page_id`=?")->execute([$id]);
            $pdo->prepare("UPDATE `tap_sections` SET `page_id`=NULL WHERE `page_id`=?")->execute([$id]);
        }
        echo json_encode(['ok' => true]);
        break;

    // ── Sections ────────────────────────────────────────────────────────────

    case 'sections':
        $pid = $_GET['page_id'] ?? null;
        if ($pid) {
            $stmt = $pdo->prepare("SELECT * FROM `tap_sections` WHERE `page_id`=? ORDER BY `sort_order`,`created_at`");
            $stmt->execute([$pid]);
        } else {
            $stmt = $pdo->query("SELECT * FROM `tap_sections` ORDER BY `sort_order`,`created_at`");
        }
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['options'] = json_decode($r['options'] ?: '{}', true) ?: (object)[];
        }
        echo json_encode(['sections' => $rows]);
        break;

    case 'addSection':
        $title  = substr(trim($body['title'] ?? ''), 0, 255);
        $pageId = ($body['page_id'] ?? '') ?: null;
        if (!$title) { echo json_encode(['error' => 'empty title']); break; }
        $id  = 's-' . bin2hex(random_bytes(4));
        $ord = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM `tap_sections`")->fetchColumn();
        $pdo->prepare("INSERT INTO `tap_sections` (`id`,`title`,`sort_order`,`page_id`) VALUES (?,?,?,?)")
            ->execute([$id, $title, $ord + 10, $pageId]);
        echo json_encode(['ok' => true, 'section' => [
            'id' => $id, 'title' => $title, 'sort_order' => $ord + 10, 'page_id' => $pageId, 'options' => (object)[],
        ]]);
        break;

    case 'updateSection':
        $id   = $body['id'] ?? '';
        $opts = json_encode($body['options'] ?? []);
        if ($id) {
            $pdo->prepare("UPDATE `tap_sections` SET `options`=? WHERE `id`=?")->execute([$opts, $id]);
        }
        echo json_encode(['ok' => true]);
        break;

    case 'deleteSection':
        $id = $body['id'] ?? '';
        if ($id) {
            $pdo->prepare("DELETE FROM `tap_sections` WHERE `id`=?")->execute([$id]);
            $pdo->prepare("UPDATE `tap_blocks` SET `section_id`=NULL WHERE `section_id`=?")->execute([$id]);
        }
        echo json_encode(['ok' => true]);
        break;

    // ── Blocks ──────────────────────────────────────────────────────────────

    case 'blocks':
        $pid = $_GET['page_id'] ?? null;
        if ($pid) {
            $stmt = $pdo->prepare("SELECT * FROM `tap_blocks` WHERE `page_id`=? ORDER BY `sort_order`,`created_at`");
            $stmt->execute([$pid]);
        } else {
            $stmt = $pdo->query("SELECT * FROM `tap_blocks` ORDER BY `sort_order`,`created_at`");
        }
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['options']      = json_decode($r['options'] ?: '{}', true) ?: (object)[];
            $r['is_visible']   = (bool)$r['is_visible'];
            $r['block_type_id'] = (int)$r['block_type_id'];
            $r['sort_order']   = (int)$r['sort_order'];
        }
        echo json_encode(['blocks' => $rows]);
        break;

    case 'save':
        $id       = $body['id'] ?? null;
        $typeId   = (int)($body['block_type_id'] ?? 1);
        $typeName = preg_replace('/[^a-z0-9\-]/', '', $body['block_type_name'] ?? 'text');
        $pageId   = ($body['page_id'] ?? '') ?: null;
        $secId    = ($body['section_id'] ?? '') ?: null;
        $visible  = (int)(bool)($body['is_visible'] ?? true);
        $opts     = json_encode($body['options'] ?? []);
        $anchor   = ($body['anchor'] ?? '') ?: null;

        if ($id) {
            $pdo->prepare("UPDATE `tap_blocks`
                SET `page_id`=?,`section_id`=?,`options`=?,`is_visible`=?,`anchor`=?,`updated_at`=NOW()
                WHERE `id`=?")
                ->execute([$pageId, $secId, $opts, $visible, $anchor, $id]);
        } else {
            $id  = bin2hex(random_bytes(6));
            $ord = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM `tap_blocks`")->fetchColumn();
            $pdo->prepare("INSERT INTO `tap_blocks`
                (`id`,`page_id`,`block_type_id`,`block_type_name`,`section_id`,`options`,`is_visible`,`sort_order`,`anchor`)
                VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$id, $pageId, $typeId, $typeName, $secId, $opts, $visible, $ord + 10, $anchor]);
        }
        echo json_encode(['ok' => true, 'id' => $id]);
        break;

    case 'delete':
        $id = $body['id'] ?? '';
        if ($id) $pdo->prepare("DELETE FROM `tap_blocks` WHERE `id`=?")->execute([$id]);
        echo json_encode(['ok' => true]);
        break;

    case 'toggle':
        $id  = $body['id'] ?? '';
        $vis = (int)(bool)($body['is_visible'] ?? false);
        if ($id) $pdo->prepare("UPDATE `tap_blocks` SET `is_visible`=? WHERE `id`=?")->execute([$vis, $id]);
        echo json_encode(['ok' => true]);
        break;

    case 'reorder':
        foreach (($body['ids'] ?? []) as $i => $bid) {
            $pdo->prepare("UPDATE `tap_blocks` SET `sort_order`=? WHERE `id`=?")->execute([$i * 10, $bid]);
        }
        echo json_encode(['ok' => true]);
        break;

    case 'reorderFull':
        foreach (($body['blocks'] ?? []) as $i => $item) {
            $bid = $item['id'] ?? '';
            $sid = ($item['section_id'] ?? '') ?: null;
            if ($bid) {
                $pdo->prepare("UPDATE `tap_blocks` SET `sort_order`=?,`section_id`=? WHERE `id`=?")
                    ->execute([$i * 10, $sid, $bid]);
            }
        }
        echo json_encode(['ok' => true]);
        break;

    case 'getSettings':
        try {
            $rows = $pdo->query("SELECT `setting_key`, `setting_value` FROM `tap_settings`")
                ->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) { $rows = []; }
        echo json_encode(['settings' => [
            'screen'            => $rows['screen']            ?? '#ffffff',
            'text_color'        => $rows['text_color']        ?? '#343a40',
            'link_bg'           => $rows['link_bg']           ?? '#ffffff',
            'link_color'        => $rows['link_color']        ?? '#343a40',
            'link_radius'       => (int)($rows['link_radius']       ?? 7),
            'link_border_width' => (int)($rows['link_border_width']  ?? 0),
            'link_border_color' => $rows['link_border_color']  ?? '#ffffff',
            'link_shadow'       => $rows['link_shadow']        ?? 'none',
            'link_shadow_color' => $rows['link_shadow_color']  ?? 'rgba(0,0,0,.15)',
            'page_font'         => $rows['page_font']          ?? '',
        ]]);
        break;

    case 'saveSettings':
        require_once dirname(__DIR__) . '/page.php';
        $allowed = ['screen','text_color','link_bg','link_color','link_radius','link_border_width','link_border_color','link_shadow','link_shadow_color','page_font'];
        $colorKeys = ['screen' => '#ffffff', 'text_color' => '#343a40', 'link_bg' => '#ffffff', 'link_color' => '#343a40', 'link_border_color' => '#ffffff', 'link_shadow_color' => 'rgba(0,0,0,.15)'];
        $stmt = $pdo->prepare("INSERT INTO `tap_settings` (`setting_key`,`setting_value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`)");
        foreach ($allowed as $k) {
            if (array_key_exists($k, $body)) {
                $val = (string)($body[$k] ?? '');
                if (isset($colorKeys[$k])) {
                    $val = sanitizeCssColor($val, $colorKeys[$k]);
                }
                $stmt->execute([$k, $val]);
            }
        }
        echo json_encode(['ok' => true]);
        break;

    case 'submissions':
        $blockId = $_GET['block_id'] ?? null;
        if ($blockId) {
            $stmt = $pdo->prepare("SELECT s.*, p.title as page_title FROM `tap_submissions` s LEFT JOIN `tap_pages` p ON p.id=s.page_id WHERE s.`block_id`=? ORDER BY s.`created_at` DESC");
            $stmt->execute([$blockId]);
        } else {
            $stmt = $pdo->query("SELECT s.*, p.title as page_title FROM `tap_submissions` s LEFT JOIN `tap_pages` p ON p.id=s.page_id ORDER BY s.`created_at` DESC LIMIT 25");
        }
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['data'] = json_decode($r['data'] ?? '{}', true) ?: [];
        }
        echo json_encode(['submissions' => $rows]);
        break;

    case 'deleteSubmission':
        $id = (int)($body['id'] ?? 0);
        if ($id) $pdo->prepare("DELETE FROM `tap_submissions` WHERE `id`=?")->execute([$id]);
        echo json_encode(['ok' => true]);
        break;

    case 'getSiteSettings':
        try {
            $rows = $pdo->query("SELECT `setting_key`, `setting_value` FROM `tap_settings`")
                ->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) { $rows = []; }
        echo json_encode(['settings' => [
            'head_code'       => $rows['head_code']       ?? '',
            'seo_title'       => $rows['seo_title']       ?? '',
            'seo_description' => $rows['seo_description'] ?? '',
            'favicon_url'     => $rows['favicon_url']     ?? '',
        ]]);
        break;

    case 'saveSiteSettings':
        $stmt = $pdo->prepare("INSERT INTO `tap_settings` (`setting_key`,`setting_value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`)");
        foreach (['head_code', 'seo_title', 'seo_description', 'favicon_url'] as $k) {
            if (array_key_exists($k, $body)) {
                $stmt->execute([$k, (string)($body[$k] ?? '')]);
            }
        }
        echo json_encode(['ok' => true]);
        break;

    // ── Modules ───────────────────────────────────────────────────────────

    case 'getModuleSettings':
        $key = 'module_' . preg_replace('/[^a-z0-9_]/', '', $body['module'] ?? $_GET['module'] ?? '');
        try {
            $val = $pdo->prepare("SELECT `setting_value` FROM `tap_settings` WHERE `setting_key`=?");
            $val->execute([$key]);
            $raw = $val->fetchColumn();
        } catch (PDOException $e) { $raw = false; }
        echo json_encode(['settings' => $raw ? json_decode($raw, true) : null]);
        break;

    case 'saveModuleSettings':
        $module = preg_replace('/[^a-z0-9_]/', '', $body['module'] ?? '');
        if (!$module) { echo json_encode(['error' => 'module required']); break; }
        $key = 'module_' . $module;
        $val = json_encode($body['settings'] ?? [], JSON_UNESCAPED_UNICODE);
        $stmt = $pdo->prepare("INSERT INTO `tap_settings` (`setting_key`,`setting_value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`)");
        $stmt->execute([$key, $val]);
        echo json_encode(['ok' => true]);
        break;

    // ── Payments ────────────────────────────────────────────────────────────

    case 'payments':
        $rows = $pdo->query("SELECT `id`,`provider`,`label`,`credentials`,`is_active`,`created_at` FROM `tap_payments` ORDER BY `created_at`")->fetchAll();
        foreach ($rows as &$r) {
            $r['is_active']   = (bool)(int)$r['is_active'];
            $r['credentials'] = json_decode($r['credentials'] ?: '{}', true) ?: (object)[];
        }
        echo json_encode(['payments' => $rows]);
        break;

    case 'savePayment': {
        $id       = trim($body['id'] ?? '');
        $provider = preg_replace('/[^a-z0-9_]/', '', $body['provider'] ?? '');
        $label    = substr(trim($body['label'] ?? ''), 0, 128);
        $creds    = json_encode($body['credentials'] ?? []);
        $active   = (int)!empty($body['is_active']);
        if (!$provider) { echo json_encode(['error' => 'provider required']); break; }
        if (!$id) {
            $id = bin2hex(random_bytes(8));
            $pdo->prepare("INSERT INTO `tap_payments` (`id`,`provider`,`label`,`credentials`,`is_active`) VALUES (?,?,?,?,?)")
                ->execute([$id, $provider, $label, $creds, $active]);
        } else {
            $pdo->prepare("UPDATE `tap_payments` SET `provider`=?,`label`=?,`credentials`=?,`is_active`=? WHERE `id`=?")
                ->execute([$provider, $label, $creds, $active, $id]);
        }
        echo json_encode(['ok' => true, 'id' => $id]);
        break;
    }

    case 'togglePayment': {
        $id = trim($body['id'] ?? '');
        if ($id) $pdo->prepare("UPDATE `tap_payments` SET `is_active`=NOT `is_active` WHERE `id`=?")->execute([$id]);
        $stmt = $pdo->prepare("SELECT `is_active` FROM `tap_payments` WHERE `id`=?");
        $stmt->execute([$id]);
        echo json_encode(['ok' => true, 'is_active' => (bool)(int)$stmt->fetchColumn()]);
        break;
    }

    case 'deletePayment': {
        $id = trim($body['id'] ?? '');
        if ($id) $pdo->prepare("DELETE FROM `tap_payments` WHERE `id`=?")->execute([$id]);
        echo json_encode(['ok' => true]);
        break;
    }

    // ── Orders ─────────────────────────────────────────────────────────────

    case 'orders':
        $rows = $pdo->query("SELECT `id`,`deal_id`,`amount`,`currency`,`status`,`created_at`,`paid_at` FROM `tap_orders` ORDER BY `created_at` DESC LIMIT 50")->fetchAll();
        echo json_encode(['orders' => $rows]);
        break;

    case 'initPayment': {
        // Create a GetPlatinum payment via init-payment-url
        $amountRub  = floatval($body['amount'] ?? 0);
        $posName    = trim($body['position_name'] ?? 'Оплата');
        $clientId   = trim($body['client_id'] ?? ('c-' . bin2hex(random_bytes(4))));
        $clientEmail = trim($body['client_email'] ?? '');
        $clientPhone = trim($body['client_phone'] ?? '');
        $successUrl  = trim($body['success_url'] ?? '');
        $failUrl     = trim($body['fail_url'] ?? '');
        $customParams = $body['custom_params'] ?? [];

        if ($amountRub <= 0) { echo json_encode(['error' => 'amount required']); break; }

        // Get active GetPlatinum config
        $pm = $pdo->query("SELECT * FROM `tap_payments` WHERE `provider`='getplatinum' AND `is_active`=1 LIMIT 1")->fetch();
        if (!$pm) { echo json_encode(['error' => 'GetPlatinum не подключён или отключён']); break; }

        $creds   = json_decode($pm['credentials'] ?: '{}', true);
        $apiKey  = $creds['api_key'] ?? '';
        $baseUrl = rtrim($creds['base_url'] ?? 'https://example.getplatinum.ru/api/public/v2/pay', '/');
        $vat     = $creds['vat'] ?? 'none';
        $prefix  = intval($creds['position_prefix'] ?? 12);

        if (!$apiKey) { echo json_encode(['error' => 'API-ключ не задан']); break; }

        $amountKop = intval(round($amountRub * 100));
        $dealId    = 'D-' . bin2hex(random_bytes(8));
        $orderId   = bin2hex(random_bytes(8));

        $scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host      = $_SERVER['HTTP_HOST'] ?? '';
        $notifyUrl = $scheme . '://' . $host . '/payment-callback.php';
        if (!$successUrl) $successUrl = $scheme . '://' . $host . '/';

        $clientParams = ['clientId' => $clientId];
        if ($clientEmail) $clientParams['email'] = $clientEmail;
        if ($clientPhone) $clientParams['phone'] = $clientPhone;

        $payload = [
            'dealId'          => $dealId,
            'amount'          => $amountKop,
            'currency'        => 'RUB',
            'positions'       => [[
                'prefix'   => $prefix,
                'name'     => $posName,
                'price'    => $amountKop,
                'quantity' => 1,
                'vat'      => $vat,
            ]],
            'clientParams'    => $clientParams,
            'notificationUrl' => $notifyUrl,
            'successUrl'      => $successUrl,
        ];
        if ($failUrl) $payload['failUrl'] = $failUrl;
        if ($customParams) $payload['customParams'] = $customParams;

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
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($resp, true);

        if ($httpCode !== 200 || !empty($data['errorCode'])) {
            echo json_encode(['error' => $data['errorMessage'] ?? "HTTP $httpCode", 'raw' => $data]);
            break;
        }

        // Save order
        $pdo->prepare("INSERT INTO `tap_orders` (`id`,`deal_id`,`payment_id`,`amount`,`currency`,`status`,`form_url`,`custom_params`) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$orderId, $dealId, $pm['id'], $amountKop, 'RUB', 'pending', $data['formUrl'] ?? '', json_encode($customParams)]);

        echo json_encode(['ok' => true, 'deal_id' => $dealId, 'form_url' => $data['formUrl'] ?? '', 'order_id' => $orderId]);
        break;
    }

    case 'paymentStatus': {
        $dealId = trim($body['deal_id'] ?? '');
        if (!$dealId) { echo json_encode(['error' => 'deal_id required']); break; }

        $order = $pdo->prepare("SELECT * FROM `tap_orders` WHERE `deal_id`=?");
        $order->execute([$dealId]);
        $order = $order->fetch();
        if (!$order) { echo json_encode(['error' => 'order not found']); break; }

        echo json_encode(['ok' => true, 'order' => [
            'deal_id' => $order['deal_id'],
            'amount'  => (int)$order['amount'],
            'status'  => $order['status'],
            'paid_at' => $order['paid_at'],
        ]]);
        break;
    }

    // ── Products ────────────────────────────────────────────────────────

    case 'products':
        $rows = $pdo->query("SELECT * FROM `tap_products` ORDER BY `sort_order`, `created_at`")->fetchAll();
        foreach ($rows as &$r) {
            $r['price']     = (int)$r['price'];
            $r['is_active'] = (bool)(int)$r['is_active'];
            $r['sort_order'] = (int)$r['sort_order'];
        }
        echo json_encode(['products' => $rows]);
        break;

    case 'saveProduct': {
        $id       = trim($body['id'] ?? '');
        $title    = substr(trim($body['title'] ?? ''), 0, 255);
        $price    = max(0, intval($body['price'] ?? 0));
        $currency = strtoupper(substr(trim($body['currency'] ?? 'RUB'), 0, 3)) ?: 'RUB';
        $imageUrl = substr(trim($body['image_url'] ?? ''), 0, 512) ?: null;
        $successPageId = ($body['success_page_id'] ?? '') ?: null;
        $active   = (int)!empty($body['is_active']);

        if (!$title) { echo json_encode(['error' => 'title required']); break; }
        if ($price <= 0) { echo json_encode(['error' => 'price required']); break; }

        if (!$id) {
            $id  = 'prod-' . bin2hex(random_bytes(4));
            $ord = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM `tap_products`")->fetchColumn();
            $pdo->prepare("INSERT INTO `tap_products` (`id`,`title`,`price`,`currency`,`image_url`,`success_page_id`,`is_active`,`sort_order`) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([$id, $title, $price, $currency, $imageUrl, $successPageId, $active, $ord + 10]);
        } else {
            $pdo->prepare("UPDATE `tap_products` SET `title`=?,`price`=?,`currency`=?,`image_url`=?,`success_page_id`=?,`is_active`=? WHERE `id`=?")
                ->execute([$title, $price, $currency, $imageUrl, $successPageId, $active, $id]);
        }
        echo json_encode(['ok' => true, 'id' => $id]);
        break;
    }

    case 'deleteProduct': {
        $id = trim($body['id'] ?? '');
        if ($id) $pdo->prepare("DELETE FROM `tap_products` WHERE `id`=?")->execute([$id]);
        echo json_encode(['ok' => true]);
        break;
    }

    // ── Mailings ───────────────────────────────────────────────────────────

    case 'mailings':
        $rows = $pdo->query("SELECT `id`,`subject`,`body`,`template`,`created_at`,`updated_at` FROM `tap_mailings` ORDER BY `created_at` DESC")->fetchAll();
        echo json_encode(['mailings' => $rows]);
        break;

    case 'saveMailing': {
        $id       = trim($body['id'] ?? '');
        $subject  = substr(trim($body['subject'] ?? ''), 0, 500);
        $bodyText = $body['body'] ?? '';
        $template = preg_replace('/[^a-z0-9_]/', '', $body['template'] ?? 'plain');
        if (!$subject) { echo json_encode(['error' => 'subject required']); break; }
        if (!$id) {
            $id = 'm-' . bin2hex(random_bytes(8));
            $pdo->prepare("INSERT INTO `tap_mailings` (`id`,`subject`,`body`,`template`) VALUES (?,?,?,?)")
                ->execute([$id, $subject, $bodyText, $template]);
        } else {
            $pdo->prepare("UPDATE `tap_mailings` SET `subject`=?,`body`=?,`template`=? WHERE `id`=?")
                ->execute([$subject, $bodyText, $template, $id]);
        }
        echo json_encode(['ok' => true, 'id' => $id]);
        break;
    }

    case 'deleteMailing': {
        $id = trim($body['id'] ?? '');
        if ($id) $pdo->prepare("DELETE FROM `tap_mailings` WHERE `id`=?")->execute([$id]);
        echo json_encode(['ok' => true]);
        break;
    }

    default:
        echo json_encode(['error' => 'unknown action']);
}
