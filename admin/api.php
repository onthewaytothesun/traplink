<?php
session_start();

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

$action = $_GET['action'] ?? '';
$body   = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (json_decode(file_get_contents('php://input'), true) ?? [])
    : [];

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
        $id       = $body['id'] ?? '';
        $title    = substr(trim($body['title'] ?? ''), 0, 255);
        $slug     = preg_replace('/[^a-z0-9\-]/', '', strtolower($body['slug'] ?? ''));
        $theme    = json_encode($body['theme'] ?? []);
        $folderId = array_key_exists('folder_id', $body) ? (($body['folder_id'] ?? '') ?: null) : false;
        if ($id && $title) {
            if ($folderId !== false) {
                $pdo->prepare("UPDATE `tap_pages` SET `title`=?,`slug`=?,`theme`=?,`folder_id`=? WHERE `id`=?")
                    ->execute([$title, $slug, $theme, $folderId, $id]);
            } else {
                $pdo->prepare("UPDATE `tap_pages` SET `title`=?,`slug`=?,`theme`=? WHERE `id`=?")
                    ->execute([$title, $slug, $theme, $id]);
            }
        }
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
        echo json_encode(['sections' => $stmt->fetchAll()]);
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
            'id' => $id, 'title' => $title, 'sort_order' => $ord + 10, 'page_id' => $pageId,
        ]]);
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

    default:
        echo json_encode(['error' => 'unknown action']);
}
