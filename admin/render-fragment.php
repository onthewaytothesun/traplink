<?php
require __DIR__ . '/_session.php';
if (!($_SESSION['admin_auth'] ?? false)) { http_response_code(403); echo json_encode(['ok'=>false]); exit; }

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
    echo json_encode(['ok'=>false,'error'=>'DB error']); exit;
}

$pageId = trim($_GET['page_id'] ?? '');
if (!$pageId) { echo json_encode(['ok'=>false]); exit; }

$page = $pdo->prepare("SELECT * FROM tap_pages WHERE id=? LIMIT 1");
$page->execute([$pageId]);
$page = $page->fetch();
if (!$page) { echo json_encode(['ok'=>false]); exit; }

$stmtBlocks = $pdo->prepare("SELECT * FROM tap_blocks WHERE page_id=? ORDER BY sort_order, created_at");
$stmtBlocks->execute([$pageId]);
$blocks = $stmtBlocks->fetchAll();

$stmtSections = $pdo->prepare("SELECT * FROM tap_sections WHERE page_id=? ORDER BY sort_order, created_at");
$stmtSections->execute([$pageId]);
$sections = $stmtSections->fetchAll();

require dirname(__DIR__) . '/page.php';

$theme           = getPageTheme($pdo, $page);
$screenBg        = sanitizeCssColor($theme['screen']       ?? '#ffffff', '#ffffff');
$textColor       = sanitizeCssColor($theme['text_color']   ?? '#343a40', '#343a40');
$linkBg          = sanitizeCssColor($theme['link_bg']      ?? '#ffffff', '#ffffff');
$linkColor       = sanitizeCssColor($theme['link_color']   ?? '#343a40', '#343a40');
$linkRadius      = max(0, min(100, (int)($theme['link_radius']       ?? 7)));
$linkBorderWidth = max(0, min(10,  (int)($theme['link_border_width'] ?? 0)));
$linkBorderColor = preg_replace('/[^#a-zA-Z0-9(),. ]/', '', $theme['link_border_color'] ?? '') ?: $linkBg;
$linkShadow      = in_array($theme['link_shadow'] ?? '', ['none','light','medium','heavy']) ? $theme['link_shadow'] : 'none';
$linkShadowColor = preg_replace('/[^#a-zA-Z0-9(),. ]/', '', $theme['link_shadow_color'] ?? '') ?: 'rgba(0,0,0,.15)';
$pageFont        = preg_replace('/[^a-zA-Z0-9 ]/', '', trim($theme['page_font'] ?? ''));
$shadowParamsMap = ['light'=>'0 2px 8px 0','medium'=>'0 4px 16px 0','heavy'=>'0 8px 28px 0'];
$shadowParams    = $shadowParamsMap[$linkShadow] ?? '0 0 0 0';
$shadowColor     = ($linkShadow === 'none') ? 'transparent' : $linkShadowColor;

$blockLabels = [
    'text'=>'Текст','link'=>'Кнопка','messenger'=>'Мессенджер','video'=>'Видео',
    'break'=>'Разделитель','socialnetworks'=>'Соцсети','html'=>'HTML','avatar'=>'Аватар',
    'pictures'=>'Карусель','form'=>'Форма','page'=>'Страница','map'=>'Карта',
    'timer'=>'Таймер','collapse'=>'FAQ','banner'=>'Баннер','media'=>'Иконка+текст',
    'pricing'=>'Прайс','music'=>'Музыка','digitals-product'=>'Цифр. товар',
    'plans'=>'Тарифы','zero'=>'Свой блок',
];

$sectionMap = [];
foreach ($sections as $sec) $sectionMap[$sec['id']] = $sec;

// Google Fonts
$fontLinks = '';
if ($pageFont) {
    $fontLinks .= '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=' . rawurlencode($pageFont) . ':wght@400;600;700&display=swap">';
}
$googleFonts = [];
foreach ($blocks as $block) {
    if ($block['block_type_name'] !== 'text') continue;
    $o = json_decode($block['options'] ?? '{}', true) ?: [];
    $f = trim($o['font'] ?? '');
    if ($f) $googleFonts[$f] = true;
}
if ($googleFonts) {
    $families = implode('&family=', array_map(fn($f) => rawurlencode($f) . ':wght@400;600;700', array_keys($googleFonts)));
    $fontLinks .= '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=' . $families . '&display=swap">';
}

// Build consecutive runs (same as page-preview.php)
$runs = [];
foreach ($blocks as $block) {
    $sid  = $block['section_id'] ?? '';
    $last = count($runs) - 1;
    if ($sid !== '' && $last >= 0 && $runs[$last]['type'] === 'section' && $runs[$last]['sid'] === $sid) {
        $runs[$last]['blocks'][] = $block;
    } elseif ($sid !== '') {
        $runs[] = ['type' => 'section', 'sid' => $sid, 'blocks' => [$block]];
    } else {
        $runs[] = ['type' => 'block', 'block' => $block];
    }
}

// Render single block wrap with admin controls (data-action, no Alpine directives)
$renderBlockWrap = function($block) use ($blockLabels) {
    $opts = json_decode($block['options'] ?? '{}', true) ?: [];
    if ($block['block_type_name'] === 'form') {
        $opts['_block_id'] = $block['id'];
        $opts['_page_id']  = $block['page_id'] ?? '';
    }
    if ($block['block_type_name'] === 'zero' && is_array($opts['zero'] ?? null)) {
        $html = renderZeroBlock($opts['zero']);
    } elseif ($block['block_type_name'] === 'html' || $block['block_type_name'] === 'zero') {
        $src = trim($opts['html'] ?? '');
        $html = $src
            ? '<pre style="margin:0;padding:8px 10px;background:rgba(0,0,0,.06);border-radius:6px;font-size:.72rem;color:#6b7280;white-space:pre-wrap;word-break:break-all;max-height:120px;overflow:hidden;">' . htmlspecialchars($src) . '</pre>'
            : '<div style="padding:8px 10px;background:rgba(0,0,0,.06);border-radius:6px;font-size:.75rem;color:#9ca3af;text-align:center;">HTML</div>';
    } elseif ($block['block_type_name'] === 'video') {
        $vurl = $opts['url'] ?? '';
        if (preg_match('/(?:youtube\.com\/watch\?.*v=|youtu\.be\/)([a-zA-Z0-9_\-]+)/i', $vurl, $vm)) {
            $vid  = htmlspecialchars($vm[1]);
            $html = '<div style="position:relative;border-radius:8px;overflow:hidden;aspect-ratio:16/9;background:#000;">'
                  . '<img src="https://img.youtube.com/vi/' . $vid . '/hqdefault.jpg" style="width:100%;height:100%;object-fit:cover;opacity:.85;display:block;">'
                  . '<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;">'
                  . '<svg width="44" height="44" viewBox="0 0 48 48" fill="none"><circle cx="24" cy="24" r="24" fill="rgba(0,0,0,.55)"/><polygon points="19,16 35,24 19,32" fill="#fff"/></svg>'
                  . '</div></div>';
        } else {
            $html = '<div style="padding:10px;background:rgba(0,0,0,.08);border-radius:4px;font-size:.8rem;text-align:center;color:#6b7280;">Видео</div>';
        }
    } else {
        $html = renderBlock($block['block_type_name'], $opts);
    }

    $hidden     = !$block['is_visible'];
    $wrapCls    = blockWrapClass($block['block_type_name'], $opts);
    $anchorAttr = $block['anchor'] ? ' id="' . htmlspecialchars($block['anchor']) . '"' : '';
    $blockLabel = $blockLabels[$block['block_type_name']] ?? $block['block_type_name'];
    $id         = htmlspecialchars($block['id']);
    $wrapStyle  = $block['block_type_name'] === 'video' ? ' style="padding-bottom:0;background:transparent;"' : '';

    $eyeOff = '<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>';
    $eyeOn  = '<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';

    $out  = '<div class="' . $wrapCls . ' admin-block-wrap" data-block-id="' . $id . '"' . $anchorAttr . $wrapStyle . '>';
    $out .= '<div class="admin-block-bar">';
    $out .= '<span class="drag-handle" title="Перетащить"><svg width="11" height="11" fill="currentColor" viewBox="0 0 24 24"><circle cx="9" cy="5" r="1.5"/><circle cx="15" cy="5" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/></svg></span>';
    $out .= '<span style="font-size:.65rem;color:#1f2937;">' . htmlspecialchars($blockLabel) . '</span>';
    if ($hidden) $out .= '<span style="font-size:.6rem;color:#d97706;">скрыт</span>';
    $out .= '<div style="flex:1"></div>';
    $out .= '<button class="admin-btn ' . ($hidden ? 'vis-off' : '') . '" title="' . ($hidden ? 'Показать' : 'Скрыть') . '" data-action="toggleVis" data-block-id="' . $id . '">' . ($hidden ? $eyeOff : $eyeOn) . '</button>';
    $out .= '<button class="admin-btn" title="Редактировать" data-action="editBlock" data-block-id="' . $id . '"><svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>';
    $out .= '<button class="admin-btn danger" title="Удалить" data-action="deleteBlock" data-block-id="' . $id . '"><svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>';
    $out .= '</div>'; // admin-block-bar
    $out .= '<div class="admin-block-content' . ($hidden ? ' is-hidden' : '') . '">';
    $out .= $html !== '' ? $html : '<div style="padding:10px;background:rgba(255,255,255,.05);border-radius:6px;color:rgba(0,0,0,.25);font-size:.8rem;text-align:center;">[' . htmlspecialchars($block['block_type_name']) . ']</div>';
    $out .= '</div>'; // admin-block-content
    $out .= '</div>'; // admin-block-wrap
    return $out;
};

ob_start();
?>
<?= $fontLinks ?>
<style>
.taplink-preview-inner{
  background:<?= htmlspecialchars($screenBg) ?>;
  color:<?= htmlspecialchars($textColor) ?>;
  font-family:<?= $pageFont ? "'" . htmlspecialchars($pageFont) . "'," : '' ?>-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
  line-height:1.6;
  --theme-screen-background:<?= htmlspecialchars($screenBg) ?>;
  --theme-text-color:<?= htmlspecialchars($textColor) ?>;
  --theme-heading-color:<?= htmlspecialchars($textColor) ?>;
  --theme-link-background:<?= htmlspecialchars($linkBg) ?>;
  --theme-link-title-color:<?= htmlspecialchars($linkColor) ?>;
  --theme-link-subtitle-color:<?= htmlspecialchars($linkColor) ?>;
  --theme-link-border-color:<?= htmlspecialchars($linkBg) ?>;
  --block-link-background:var(--theme-link-background);
  --block-link-title-color:var(--theme-link-title-color);
  --block-link-subtitle-color:var(--theme-link-subtitle-color);
  --block-link-border-color:var(--theme-link-border-color);
  --block-link-subtitle-fontsize:13px;
  --block-link-icon-background:rgba(52,58,64,.12);
  --block-link-border-color:<?= htmlspecialchars($linkBorderColor) ?>;
<?= themeLinkCssVars($linkRadius, $linkBorderWidth, $shadowParams, $shadowColor) ?>
  --theme-link-backdrop-filter:none;
<?= themeTextSizeCssVars() ?>
  --section-padding-top:0px;
  --section-padding-right:0px;
  --section-padding-bottom:0px;
  --section-padding-left:0px;
}
</style>
<div class="taplink-preview-inner">
  <div id="blocksSortable" style="padding:24px 0 80px">
<?php
$hasTimer = false;
foreach ($runs as $run):
    if ($run['type'] === 'section'):
        $sid      = htmlspecialchars($run['sid']);
        $secTitle = htmlspecialchars($sectionMap[$run['sid']]['title'] ?? 'Секция');
        echo '<div class="section-group" data-section-id="' . $sid . '">';
        echo '<div class="section-group-label">' . $secTitle . '</div>';
        echo '<div class="section-group-sortable" data-section-id="' . $sid . '">';
        foreach ($run['blocks'] as $b) {
            if ($b['block_type_name'] === 'timer') $hasTimer = true;
            echo $renderBlockWrap($b);
        }
        echo '</div></div>';
    else:
        if ($run['block']['block_type_name'] === 'timer') $hasTimer = true;
        echo $renderBlockWrap($run['block']);
    endif;
endforeach;
?>
  </div>
  <button class="admin-add-placeholder" data-action="addBlock">
    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Добавить блок
  </button>
</div>
<?php if ($hasTimer): ?>
<script>
document.querySelectorAll('.timer-widget[data-date]').forEach(function(el){
  var date=new Date(el.dataset.date).getTime();
  function tick(){var now=Date.now(),diff=date-now;if(diff<=0){el.querySelector('.timer-display').innerHTML='<span style="font-size:1.2rem">Время вышло</span>';return;}var d=Math.floor(diff/86400000),h=Math.floor(diff%86400000/3600000),m=Math.floor(diff%3600000/60000),s=Math.floor(diff%60000/1000);el.querySelectorAll('.timer-unit').forEach(function(u,i){u.querySelector('.num').textContent=String([d,h,m,s][i]).padStart(2,'0');});}
  tick();setInterval(tick,1000);
});
</script>
<?php endif; ?>
<?php
$html = ob_get_clean();
echo json_encode(['ok' => true, 'html' => $html]);
