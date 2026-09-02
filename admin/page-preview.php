<?php
session_start();

if (!($_SESSION['admin_auth'] ?? false)) {
    header('Location: /admin/');
    exit;
}

$cfg = require dirname(__DIR__) . '/config.php';

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $cfg['db_host'] ?? 'localhost', $cfg['db_name'] ?? ''),
        $cfg['db_user'] ?? '', $cfg['db_pass'] ?? ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('DB error: ' . htmlspecialchars($e->getMessage()));
}

$pageId = trim($_GET['page_id'] ?? '');
if (!$pageId) { header('Location: /admin/'); exit; }

$stmtPage = $pdo->prepare("SELECT * FROM tap_pages WHERE id=? LIMIT 1");
$stmtPage->execute([$pageId]);
$page = $stmtPage->fetch();
if (!$page) { header('Location: /admin/'); exit; }

$stmtBlocks = $pdo->prepare(
    "SELECT * FROM tap_blocks WHERE page_id=? ORDER BY sort_order, created_at"
);
$stmtBlocks->execute([$pageId]);
$blocks = $stmtBlocks->fetchAll();

$allPages = $pdo->query("SELECT id, title, slug, is_main FROM tap_pages ORDER BY sort_order, id")->fetchAll();

$stmtSections = $pdo->prepare(
    "SELECT * FROM tap_sections WHERE page_id=? ORDER BY sort_order, created_at"
);
$stmtSections->execute([$pageId]);
$sections = $stmtSections->fetchAll();

require dirname(__DIR__) . '/page.php';

$theme = getGlobalTheme($pdo);
$bgColor   = sanitizeCssColor($theme['screen']     ?? '#ffffff', '#ffffff');
$textColor = sanitizeCssColor($theme['text_color'] ?? '#343a40', '#343a40');
$linkBg    = sanitizeCssColor($theme['link_bg']    ?? '#ffffff', '#ffffff');
$linkColor = sanitizeCssColor($theme['link_color'] ?? '#343a40', '#343a40');
$linkRadius      = max(0, min(100, (int)($theme['link_radius']      ?? 7)));
$linkBorderWidth = max(0, min(10,  (int)($theme['link_border_width'] ?? 0)));
$linkBorderColor = preg_replace('/[^#a-zA-Z0-9(),. ]/', '', $theme['link_border_color'] ?? '') ?: $linkBg;
$linkShadow      = in_array($theme['link_shadow'] ?? '', ['none','light','medium','heavy']) ? $theme['link_shadow'] : 'none';
$linkShadowColor = preg_replace('/[^#a-zA-Z0-9(),. ]/', '', $theme['link_shadow_color'] ?? '') ?: 'rgba(0,0,0,.15)';
$pageFont        = preg_replace('/[^a-zA-Z0-9 ]/', '', trim($theme['page_font'] ?? ''));
$shadowParamsMap = ['light'=>'0 2px 8px 0','medium'=>'0 4px 16px 0','heavy'=>'0 8px 28px 0'];
$shadowParams    = $shadowParamsMap[$linkShadow] ?? '0 0 0 0';
$shadowColor     = ($linkShadow === 'none') ? 'transparent' : $linkShadowColor;
$liveUrl   = $page['is_main'] ? '/' : ($page['slug'] ? '/p/' . $page['slug'] : '#');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Редактор — <?= htmlspecialchars($page['title']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<link rel="stylesheet" href="/assets/taplink-frontend.css">
<style>
[x-cloak]{display:none!important}
:root {
  --theme-screen-background: <?= htmlspecialchars($bgColor) ?>;
  --theme-text-color:        <?= htmlspecialchars($textColor) ?>;
  --theme-heading-color:     <?= htmlspecialchars($textColor) ?>;
  --theme-link-background:   <?= htmlspecialchars($linkBg) ?>;
  --theme-link-title-color:  <?= htmlspecialchars($linkColor) ?>;
  --theme-link-subtitle-color: <?= htmlspecialchars($linkColor) ?>;
  --theme-link-border-color: <?= htmlspecialchars($linkBg) ?>;
  --block-link-background:       var(--theme-link-background);
  --block-link-title-color:      var(--theme-link-title-color);
  --block-link-subtitle-color:   var(--theme-link-subtitle-color);
  --block-link-border-color:     var(--theme-link-border-color);
  --block-link-subtitle-fontsize: 13px;
  --block-link-icon-background:  rgba(52,58,64,.12);
  --block-link-border-color: <?= htmlspecialchars($linkBorderColor) ?>;
<?= themeLinkCssVars($linkRadius, $linkBorderWidth, $shadowParams, $shadowColor) ?>
  --theme-link-backdrop-filter: none;
<?= themeTextSizeCssVars() ?>
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:<?= $pageFont ? "'" . $pageFont . "'," : '' ?>-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:var(--theme-text-color);line-height:1.6}
.page-container{max-width:640px;margin:0 auto;padding:0 0 80px}

/* ── admin overlay ── */
.admin-block-wrap{position:relative}
.section-group{display:flex;flex-direction:row;margin-bottom:2px}
.section-group-label{width:18px;flex-shrink:0;display:flex;align-items:flex-start;justify-content:center;padding-top:8px;writing-mode:vertical-lr;transform:rotate(180deg);font-size:.52rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(129,140,248,.85);white-space:nowrap;overflow:hidden;background:rgba(99,102,241,.1);border-left:2px solid rgba(99,102,241,.45)}
.section-group-sortable{flex:1;background:rgba(99,102,241,.04);border-left:1px solid rgba(99,102,241,.2);min-height:8px}
.section-badge{font-size:.58rem;background:rgba(99,102,241,.2);color:rgba(165,180,252,.95);border-radius:3px;padding:1px 5px;font-weight:600;white-space:nowrap}
.admin-block-bar{
  display:flex;align-items:center;gap:4px;padding:0 6px;
  background:rgba(13,17,23,.3);
  border-radius:5px 5px 0 0;
  height:26px;
}
.admin-block-content.is-hidden{opacity:.4}
.admin-btn{background:none;border:none;cursor:pointer;padding:3px 5px;border-radius:4px;color:#1f2937;line-height:1;transition:color .12s}
.admin-btn:hover{color:#000}
.admin-btn.danger:hover{color:#dc2626}
.admin-btn.vis-off{color:#d97706}
.drag-handle{cursor:grab;color:#374151;padding:3px 4px;line-height:1}
.drag-handle:hover{color:#111827}
.drag-handle:active{cursor:grabbing}
.admin-add-placeholder{
  display:flex;align-items:center;justify-content:center;gap:8px;
  width:100%;padding:12px;margin-top:4px;
  border:2px dashed rgba(255,255,255,.12);border-radius:10px;
  color:rgba(255,255,255,.35);background:transparent;cursor:pointer;
  font-size:.875rem;transition:all .15s;
}
.admin-add-placeholder:hover{border-color:rgba(255,255,255,.28);color:rgba(255,255,255,.6);background:rgba(255,255,255,.04)}
.sb::-webkit-scrollbar{width:5px}.sb::-webkit-scrollbar-track{background:transparent}.sb::-webkit-scrollbar-thumb{background:#374151;border-radius:3px}
</style>
<link rel="stylesheet" href="/assets/blocks.css">
<?php
if ($pageFont) {
    echo "<link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=" . rawurlencode($pageFont) . ":wght@400;600;700&display=swap\">\n";
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
    echo "<link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family={$families}&display=swap\">\n";
}
?>
</head>
<body style="background:<?= htmlspecialchars($bgColor) ?>;color:<?= htmlspecialchars($textColor) ?>;" x-data="previewApp()" x-cloak>

<?php $navMode = 'preview'; $navPage = $page; $navLiveUrl = $liveUrl; require __DIR__ . '/_nav.php'; ?>

<!-- ── Layout ── -->
<div class="flex" style="padding-top:56px;height:100vh">

<?php $sidebarPages = $allPages; $sidebarCurrentId = $pageId; require __DIR__ . '/_sidebar.php'; ?>

<main class="flex-1 overflow-y-auto sb">
<!-- ── Page preview ── -->
<?php $blockLabels = [
  'text'=>'Текст','link'=>'Кнопка','messenger'=>'Мессенджер','video'=>'Видео',
  'break'=>'Разделитель','socialnetworks'=>'Соцсети','html'=>'HTML','avatar'=>'Аватар',
  'pictures'=>'Карусель','form'=>'Форма','page'=>'Страница','map'=>'Карта',
  'timer'=>'Таймер','collapse'=>'FAQ','banner'=>'Баннер','media'=>'Иконка+текст',
  'pricing'=>'Прайс','music'=>'Музыка','digitals-product'=>'Цифр. товар',
  'plans'=>'Тарифы','zero'=>'Свой блок',
]; ?>
<div class="page-container" style="padding-top:24px;">
  <div id="blocksSortable">
  <?php
  $sectionMap = [];
  foreach ($sections as $sec) $sectionMap[$sec['id']] = $sec;

  // Build consecutive runs: group blocks by section_id
  $runs = [];
  foreach ($blocks as $block) {
    $sid = $block['section_id'] ?? '';
    $last = count($runs) - 1;
    if ($sid !== '' && $last >= 0 && $runs[$last]['type'] === 'section' && $runs[$last]['sid'] === $sid) {
      $runs[$last]['blocks'][] = $block;
    } elseif ($sid !== '') {
      $runs[] = ['type' => 'section', 'sid' => $sid, 'blocks' => [$block]];
    } else {
      $runs[] = ['type' => 'block', 'block' => $block];
    }
  }

  // Helper closure to render a single block wrap
  $renderBlockWrap = function($block) use ($blockLabels) {
    $opts       = json_decode($block['options'] ?? '{}', true) ?: [];
    // Inject context required by form renderer (same as page.php does in renderPage)
    if ($block['block_type_name'] === 'form') {
      $opts['_block_id'] = $block['id'];
      $opts['_page_id']  = $block['page_id'] ?? '';
    }
    // For video blocks render a thumbnail instead of a live iframe (iframe stacking covers the bar)
    if ($block['block_type_name'] === 'video') {
      $vurl = $opts['url'] ?? '';
      if (preg_match('/(?:youtube\.com\/watch\?.*v=|youtu\.be\/)([a-zA-Z0-9_\-]+)/i', $vurl, $vm)) {
        $vid = htmlspecialchars($vm[1]);
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
    // Override block-video CSS (padding-bottom:56.25%;background:#000) — not needed in admin
    $wrapStyle  = $block['block_type_name'] === 'video' ? ' style="padding-bottom:0;background:transparent;"' : '';
    echo '<div class="' . $wrapCls . ' admin-block-wrap" data-block-id="' . $id . '"' . $anchorAttr . $wrapStyle . '>';
    echo '<div class="admin-block-bar">';
    echo '<span class="drag-handle" title="Перетащить"><svg width="11" height="11" fill="currentColor" viewBox="0 0 24 24"><circle cx="9" cy="5" r="1.5"/><circle cx="15" cy="5" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/></svg></span>';
    echo '<span style="font-size:.65rem;color:#1f2937;">' . htmlspecialchars($blockLabel) . '</span>';
    if ($hidden) echo '<span class="text-yellow-500 font-mono" style="font-size:.6rem;">скрыт</span>';
    echo '<div class="flex-1"></div>';
    // toggle visibility
    $eyeOff = '<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>';
    $eyeOn  = '<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>';
    echo '<button class="admin-btn ' . ($hidden ? 'vis-off' : '') . '" title="' . ($hidden ? 'Показать' : 'Скрыть') . '" @click.stop="toggleVis(\'' . $id . '\')">' . ($hidden ? $eyeOff : $eyeOn) . '</button>';
    echo '<button class="admin-btn" title="Редактировать" @click.stop="editBlock(\'' . $id . '\')"><svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>';
    echo '<button class="admin-btn danger" title="Удалить" @click.stop="deleteBlock(\'' . $id . '\')"><svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>';
    echo '</div>'; // admin-block-bar
    echo '<div class="admin-block-content' . ($hidden ? ' is-hidden' : '') . '">';
    if ($html !== '') { echo $html; }
    else { echo '<div style="padding:10px;background:rgba(255,255,255,.05);border-radius:6px;color:rgba(255,255,255,.35);font-size:.8rem;text-align:center;">[' . htmlspecialchars($block['block_type_name']) . ']</div>'; }
    echo '</div>'; // admin-block-content
    echo '</div>'; // admin-block-wrap
  };

  foreach ($runs as $run):
    if ($run['type'] === 'section'):
      $sid      = htmlspecialchars($run['sid']);
      $secTitle = htmlspecialchars($sectionMap[$run['sid']]['title'] ?? 'Секция');
  ?>
  <div class="section-group" data-section-id="<?= $sid ?>">
    <div class="section-group-label"><?= $secTitle ?></div>
    <div class="section-group-sortable" data-section-id="<?= $sid ?>">
      <?php foreach ($run['blocks'] as $block) { $renderBlockWrap($block); } ?>
    </div>
  </div>
  <?php else: $renderBlockWrap($run['block']); endif; ?>
  <?php endforeach; ?>
  </div>

  <button class="admin-add-placeholder" @click="openAdd()">
    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Добавить блок
  </button>
</div>
</main>
</div>

<!-- ── Timer JS (same as page.php) ── -->
<?php if (array_filter($blocks, fn($b) => $b['block_type_name'] === 'timer')): ?>
<script>
document.querySelectorAll('.timer-widget[data-date]').forEach(function(el){
  var date=new Date(el.dataset.date).getTime();
  function tick(){var now=Date.now(),diff=date-now;if(diff<=0){el.querySelector('.timer-display').innerHTML='<span style="font-size:1.2rem">Время вышло</span>';return;}var d=Math.floor(diff/86400000),h=Math.floor(diff%86400000/3600000),m=Math.floor(diff%3600000/60000),s=Math.floor(diff%60000/1000);el.querySelectorAll('.timer-unit').forEach(function(u,i){u.querySelector('.num').textContent=String([d,h,m,s][i]).padStart(2,'0');});}
  tick();setInterval(tick,1000);
});
</script>
<?php endif; ?>

<!-- ── Design panel ── -->
<div x-show="designOpen" x-cloak
  class="fixed top-0 right-0 bottom-0 z-40 flex flex-col sb overflow-y-auto"
  style="width:300px;background:#161b22;border-left:1px solid #21262d;padding-top:52px;">
  <div class="px-4 py-3 flex items-center justify-between shrink-0" style="border-bottom:1px solid #21262d">
    <span class="text-sm font-semibold" style="color:#e6edf3">Дизайн страницы</span>
    <button @click="designOpen=false" class="text-gray-500 hover:text-white text-lg leading-none">×</button>
  </div>
  <div class="flex-1 overflow-y-auto sb p-4 space-y-5">

    <!-- Page section -->
    <div>
      <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Страница</div>
      <div class="space-y-2.5">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-300">Фон страницы</span>
          <div class="flex items-center gap-1.5 bg-gray-800 border border-gray-700 rounded-lg px-2 py-1">
            <input type="color" :value="colorPickerHex(design.screen)" @input="design.screen=colorPickerMerge($event.target.value, design.screen);applyDesign()" class="w-7 h-7 rounded cursor-pointer border-0 bg-transparent p-0 shrink-0">
            <input type="text" x-model="design.screen" @input="applyDesign()" class="w-24 bg-transparent text-white text-xs font-mono focus:outline-none" placeholder="#edf2ff00">
          </div>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-300">Цвет текста</span>
          <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="color" x-model="design.text_color" @input="applyDesign()" class="w-7 h-7 rounded cursor-pointer border-0 bg-transparent p-0">
            <span class="text-xs text-gray-600 font-mono" x-text="design.text_color"></span>
          </label>
        </div>
        <div>
          <div class="text-sm text-gray-300 mb-1.5">Шрифт страницы</div>
          <select x-model="design.page_font" @change="applyDesign();loadGoogleFont(design.page_font)"
            class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
            <option value="">По умолчанию</option>
            <option>Inter</option><option>Roboto</option><option>Montserrat</option>
            <option>Oswald</option><option>Raleway</option><option>Poppins</option>
            <option>Nunito</option><option>Lato</option><option>Open Sans</option>
            <option>Playfair Display</option><option>Merriweather</option>
            <option>PT Sans</option><option>PT Serif</option><option>Ubuntu</option><option>Rubik</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Divider -->
    <div style="border-top:1px solid #21262d"></div>

    <!-- Buttons section -->
    <div>
      <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Кнопки</div>
      <div class="space-y-3">

        <!-- Colors -->
        <div class="grid grid-cols-2 gap-2">
          <div>
            <div class="text-xs text-gray-500 mb-1">Фон кнопок</div>
            <label class="flex items-center gap-1.5 cursor-pointer bg-gray-800 border border-gray-700 rounded-lg px-2 py-1.5">
              <input type="color" x-model="design.link_bg" @input="applyDesign()" class="w-5 h-5 rounded cursor-pointer border-0 bg-transparent p-0 shrink-0">
              <span class="text-xs text-gray-500 font-mono truncate" x-text="design.link_bg"></span>
            </label>
          </div>
          <div>
            <div class="text-xs text-gray-500 mb-1">Текст кнопок</div>
            <label class="flex items-center gap-1.5 cursor-pointer bg-gray-800 border border-gray-700 rounded-lg px-2 py-1.5">
              <input type="color" x-model="design.link_color" @input="applyDesign()" class="w-5 h-5 rounded cursor-pointer border-0 bg-transparent p-0 shrink-0">
              <span class="text-xs text-gray-500 font-mono truncate" x-text="design.link_color"></span>
            </label>
          </div>
        </div>

        <!-- Border radius -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <span class="text-sm text-gray-300">Скругление</span>
            <span class="text-xs text-gray-500 font-mono" x-text="design.link_radius+'px'"></span>
          </div>
          <div class="grid grid-cols-4 gap-1 mb-2">
            <button type="button" @click="design.link_radius=0;applyDesign()"
              :class="design.link_radius==0?'border-blue-500 text-blue-400':'border-gray-700 text-gray-500 hover:border-gray-500'"
              class="border rounded-lg py-1.5 flex flex-col items-center gap-1 transition-colors text-xs">
              <span class="w-5 h-3 border-[1.5px] border-current inline-block"></span>Квадрат</button>
            <button type="button" @click="design.link_radius=8;applyDesign()"
              :class="design.link_radius==8?'border-blue-500 text-blue-400':'border-gray-700 text-gray-500 hover:border-gray-500'"
              class="border rounded-lg py-1.5 flex flex-col items-center gap-1 transition-colors text-xs">
              <span class="w-5 h-3 border-[1.5px] border-current inline-block" style="border-radius:3px"></span>Слегка</button>
            <button type="button" @click="design.link_radius=16;applyDesign()"
              :class="design.link_radius==16?'border-blue-500 text-blue-400':'border-gray-700 text-gray-500 hover:border-gray-500'"
              class="border rounded-lg py-1.5 flex flex-col items-center gap-1 transition-colors text-xs">
              <span class="w-5 h-3 border-[1.5px] border-current inline-block" style="border-radius:6px"></span>Круглые</button>
            <button type="button" @click="design.link_radius=50;applyDesign()"
              :class="design.link_radius==50?'border-blue-500 text-blue-400':'border-gray-700 text-gray-500 hover:border-gray-500'"
              class="border rounded-lg py-1.5 flex flex-col items-center gap-1 transition-colors text-xs">
              <span class="w-5 h-3 border-[1.5px] border-current inline-block" style="border-radius:99px"></span>Пилюля</button>
          </div>
          <input type="range" x-model.number="design.link_radius" @input="applyDesign()" min="0" max="50" class="w-full accent-blue-500">
        </div>

        <!-- Border -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <span class="text-sm text-gray-300">Обводка</span>
            <span class="text-xs text-gray-500 font-mono" x-text="design.link_border_width+'px'"></span>
          </div>
          <div class="flex items-center gap-2">
            <input type="range" x-model.number="design.link_border_width" @input="applyDesign()" min="0" max="4" step="1" class="flex-1 accent-blue-500">
            <label class="flex items-center gap-1 cursor-pointer" title="Цвет обводки">
              <input type="color" x-model="design.link_border_color" @input="applyDesign()" class="w-6 h-6 rounded cursor-pointer border border-gray-600 bg-transparent p-0">
            </label>
          </div>
        </div>

        <!-- Shadow -->
        <div>
          <div class="text-sm text-gray-300 mb-1.5">Тень</div>
          <div class="grid grid-cols-4 gap-1">
            <button type="button" @click="design.link_shadow='none';applyDesign()"
              :class="design.link_shadow=='none'?'border-blue-500 text-blue-400':'border-gray-700 text-gray-500 hover:border-gray-500'"
              class="border rounded-lg py-1.5 text-xs transition-colors">Нет</button>
            <button type="button" @click="design.link_shadow='light';applyDesign()"
              :class="design.link_shadow=='light'?'border-blue-500 text-blue-400':'border-gray-700 text-gray-500 hover:border-gray-500'"
              class="border rounded-lg py-1.5 text-xs transition-colors">Лёгкая</button>
            <button type="button" @click="design.link_shadow='medium';applyDesign()"
              :class="design.link_shadow=='medium'?'border-blue-500 text-blue-400':'border-gray-700 text-gray-500 hover:border-gray-500'"
              class="border rounded-lg py-1.5 text-xs transition-colors">Средняя</button>
            <button type="button" @click="design.link_shadow='heavy';applyDesign()"
              :class="design.link_shadow=='heavy'?'border-blue-500 text-blue-400':'border-gray-700 text-gray-500 hover:border-gray-500'"
              class="border rounded-lg py-1.5 text-xs transition-colors">Сильная</button>
          </div>
          <template x-if="design.link_shadow!=='none'">
            <label class="flex items-center gap-2 mt-1.5 cursor-pointer">
              <span class="text-xs text-gray-500">Цвет тени</span>
              <input type="color" x-model="design.link_shadow_color" @input="applyDesign()" class="w-6 h-6 rounded cursor-pointer border border-gray-600 bg-transparent p-0">
            </label>
          </template>
        </div>

      </div>
    </div>
  </div>

  <!-- Save footer -->
  <div class="shrink-0 px-4 py-3" style="border-top:1px solid #21262d;background:#161b22">
    <button @click="saveDesign()" :disabled="designSaving"
      class="w-full text-white py-2.5 rounded-lg text-sm font-medium transition-colors disabled:opacity-50"
      style="background:#1f6feb"
      x-text="designSaving?'Сохраняю…':'Сохранить дизайн'"></button>
  </div>
</div>

<!-- ── Block modal ── -->
<div x-show="blockModal" x-cloak @click.self="blockModal=false"
  class="fixed inset-0 z-50 flex items-center justify-center p-4"
  style="background:rgba(0,0,0,.75);backdrop-filter:blur(4px)">
  <div class="flex flex-col w-full max-w-lg shadow-2xl"
       style="background:#161b22;border:1px solid #30363d;border-radius:12px;max-height:92vh;">
    <!-- Header -->
    <div class="flex items-center justify-between px-5 py-4 shrink-0" style="border-bottom:1px solid #21262d">
      <h3 class="font-semibold text-sm" style="color:#e6edf3" x-text="editId ? 'Редактировать блок' : 'Новый блок'"></h3>
      <button @click="blockModal=false" class="text-gray-500 hover:text-white transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Body -->
    <div class="sb overflow-y-auto flex-1 p-5 space-y-4">

      <!-- Type grid (add mode) -->
      <div x-show="!editId">
        <label class="block text-sm text-gray-400 mb-2">Тип блока</label>
        <div class="grid grid-cols-4 gap-1.5 max-h-52 overflow-y-auto sb pr-1">
          <template x-for="bt in blockTypes" :key="bt.id">
            <button type="button" @click="selectType(bt.id)"
              :class="form.block_type_id == bt.id
                ? 'bg-blue-600 border-blue-500 text-white'
                : 'bg-gray-800 border-gray-700 text-gray-400 hover:border-gray-500 hover:text-gray-200'"
              class="border rounded-lg p-2 flex flex-col items-center gap-1 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" x-html="bt.icon"></svg>
              <span class="text-xs leading-tight text-center" x-text="bt.label"></span>
            </button>
          </template>
        </div>
      </div>

      <!-- Type badge (edit mode) -->
      <div x-show="editId" class="flex items-center gap-2.5 p-3 bg-gray-800 rounded-lg">
        <div class="w-8 h-8 rounded-lg bg-gray-700 flex items-center justify-center text-gray-300 shrink-0">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"
            x-html="getIcon(typeName(form.block_type_id))"></svg>
        </div>
        <div>
          <div class="text-sm font-medium text-white" x-text="getLabel(form.block_type_id)"></div>
          <div class="text-xs text-gray-500 font-mono" x-text="typeName(form.block_type_id)"></div>
        </div>
      </div>

      <!-- Section -->
      <div x-data="{open:false}" class="relative">
        <label class="block text-sm text-gray-400 mb-1.5">Секция</label>
        <button type="button" @click="open=!open" @keydown.escape="open=false"
          class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm text-left flex items-center justify-between focus:outline-none focus:border-blue-500 transition-colors">
          <span x-text="form.section_id ? (sections.find(s=>s.id===form.section_id)?.title||'Секция') : 'Без секции'"></span>
          <svg class="w-4 h-4 text-gray-500 shrink-0 transition-transform" :class="open?'rotate-180':''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" @click.outside="open=false" x-cloak
          class="absolute z-50 mt-1 w-full bg-gray-800 border border-gray-700 rounded-lg shadow-xl overflow-hidden">
          <div class="max-h-48 overflow-y-auto">
            <button type="button" @click="form.section_id='';open=false"
              :class="form.section_id===''?'bg-blue-600 text-white':'text-gray-300 hover:bg-gray-700'"
              class="w-full text-left px-3 py-2 text-sm transition-colors">Без секции</button>
            <template x-for="s in sections" :key="s.id">
              <button type="button" @click="form.section_id=s.id;open=false"
                :class="form.section_id===s.id?'bg-blue-600 text-white':'text-gray-300 hover:bg-gray-700'"
                class="w-full text-left px-3 py-2 text-sm transition-colors" x-text="s.title||s.id"></button>
            </template>
          </div>
          <div class="border-t border-gray-700">
            <button type="button" @click="addSection().then(s=>{if(s){form.section_id=s.id;open=false;}})"
              class="w-full text-left px-3 py-2 text-sm text-blue-400 hover:bg-gray-700 transition-colors flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              Добавить секцию
            </button>
          </div>
        </div>
      </div>

      <!-- text -->
      <template x-if="typeName(form.block_type_id) === 'text'">
        <div>
          <div class="flex items-center gap-1 flex-wrap bg-gray-800 border border-gray-700 rounded-t-lg px-2 py-1.5">
            <select x-model="opts.text_size" class="bg-gray-900 text-white text-xs rounded px-2 py-1 focus:outline-none cursor-pointer border-0">
              <option value="h1">Большой заголовок</option>
              <option value="h2">Средний заголовок</option>
              <option value="h3">Маленький заголовок</option>
              <option value="lg">Большой текст</option>
              <option value="md">Средний текст</option>
              <option value="sm">Маленький текст</option>
            </select>
            <div class="w-px h-5 bg-gray-600 mx-0.5 shrink-0"></div>
            <select x-model="opts.font" @change="loadGoogleFont(opts.font)" class="bg-gray-900 text-white text-xs rounded px-2 py-1 focus:outline-none cursor-pointer border-0 max-w-[120px]">
              <option value="">По умолчанию</option>
              <option value="Inter">Inter</option><option value="Roboto">Roboto</option>
              <option value="Montserrat">Montserrat</option><option value="Oswald">Oswald</option>
              <option value="Raleway">Raleway</option><option value="Poppins">Poppins</option>
              <option value="Nunito">Nunito</option><option value="Lato">Lato</option>
              <option value="Open Sans">Open Sans</option><option value="Playfair Display">Playfair Display</option>
              <option value="Merriweather">Merriweather</option><option value="PT Sans">PT Sans</option>
              <option value="PT Serif">PT Serif</option><option value="Ubuntu">Ubuntu</option>
              <option value="Rubik">Rubik</option>
              <option value="Russo One">Russo One</option>
            </select>
            <div class="w-px h-5 bg-gray-600 mx-0.5 shrink-0"></div>
            <button type="button" @click="opts.text_align='left'" :class="(opts.text_align||'left')==='left'?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'" class="p-1.5 rounded transition-colors">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/></svg>
            </button>
            <button type="button" @click="opts.text_align='center'" :class="opts.text_align==='center'?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'" class="p-1.5 rounded transition-colors">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
            </button>
            <button type="button" @click="opts.text_align='right'" :class="opts.text_align==='right'?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'" class="p-1.5 rounded transition-colors">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="6" y1="18" x2="21" y2="18"/></svg>
            </button>
            <button type="button" @click="opts.text_align='justify'" :class="opts.text_align==='justify'?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'" class="p-1.5 rounded transition-colors">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="w-px h-5 bg-gray-600 mx-0.5 shrink-0"></div>
            <button type="button" @click="opts.bold=!opts.bold" :class="opts.bold?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'" class="w-7 h-7 rounded font-bold text-sm flex items-center justify-center transition-colors">B</button>
            <button type="button" @click="opts.italic=!opts.italic" :class="opts.italic?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'" class="w-7 h-7 rounded italic text-sm flex items-center justify-center transition-colors">I</button>
            <button type="button" @click="opts.strikethrough=!opts.strikethrough" :class="opts.strikethrough?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'" class="w-7 h-7 rounded line-through text-sm flex items-center justify-center transition-colors">S</button>
            <button type="button" @click="opts.underline=!opts.underline" :class="opts.underline?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'" class="w-7 h-7 rounded underline text-sm flex items-center justify-center transition-colors">U</button>
            <div class="w-px h-5 bg-gray-600 mx-0.5 shrink-0"></div>
            <div class="flex items-center gap-1.5 bg-gray-900 rounded px-2 py-1">
              <input type="color" x-model="opts.color" class="w-5 h-5 rounded-full cursor-pointer border-0 bg-transparent p-0 shrink-0">
              <span class="text-xs text-gray-400 font-mono" x-text="opts.color||'авто'"></span>
            </div>
          </div>
          <textarea x-model="opts.text" rows="5"
            class="w-full bg-gray-900 border border-t-0 border-gray-700 text-white rounded-b-lg px-4 py-3 focus:outline-none focus:border-blue-500 resize-none"
            placeholder="Введите текст…"
            :style="`font-family:${opts.font?opts.font+',sans-serif':'inherit'};font-size:${txtSize(opts.text_size)};font-weight:${txtWeight(opts.text_size,opts.bold)};font-style:${opts.italic?'italic':'normal'};text-decoration:${[opts.underline?'underline':'',opts.strikethrough?'line-through':''].filter(Boolean).join(' ')||'none'};text-align:${opts.text_align||'left'};color:${opts.color||'inherit'};line-height:${txtLineHeight(opts.text_size)}`"></textarea>
        </div>
      </template>

      <!-- link -->
      <template x-if="typeName(form.block_type_id) === 'link'">
        <div class="space-y-4">
          <div>
            <label class="block text-xs text-gray-500 mb-2">Текст ссылки</label>
            <div class="flex gap-3 items-start">
              <div class="relative shrink-0" x-data="{open:false}">
                <button type="button" @click="open=!open"
                  class="w-20 h-[72px] border-2 border-dashed rounded-xl flex flex-col items-center justify-center gap-1 transition-colors"
                  :class="opts.icon&&opts.icon!=='none'?'border-gray-500 bg-gray-800/60':'border-gray-700 hover:border-gray-500'">
                  <template x-if="opts.icon&&opts.icon!=='none'">
                    <svg class="w-8 h-8 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path :d="LINK_ICONS[opts.icon]||''"/></svg>
                  </template>
                  <template x-if="!opts.icon||opts.icon==='none'">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  </template>
                  <span class="text-xs leading-none" :class="opts.icon&&opts.icon!=='none'?'text-gray-500':'text-gray-600'" x-text="opts.icon&&opts.icon!=='none'?opts.icon:'иконка'"></span>
                </button>
                <div x-show="open" x-cloak @click.outside="open=false"
                  class="absolute left-0 top-full mt-1 z-30 p-2 shadow-2xl" style="background:#161b22;border:1px solid #30363d;border-radius:10px;min-width:232px">
                  <div class="grid grid-cols-5 gap-1">
                    <button type="button" @click="opts.icon='none';open=false"
                      :class="!opts.icon||opts.icon==='none'?'bg-blue-600 text-white':'text-gray-500 hover:bg-gray-800 hover:text-white'"
                      class="h-9 rounded-lg text-xs transition-colors">нет</button>
                    <template x-for="ico in LINK_ICONS_LIST" :key="ico.id">
                      <button type="button" @click="opts.icon=ico.id;open=false"
                        :class="opts.icon===ico.id?'bg-blue-600 text-white':'text-gray-400 hover:bg-gray-800 hover:text-white'"
                        :title="ico.label" class="h-9 w-full rounded-lg flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path :d="ico.path"/></svg>
                      </button>
                    </template>
                  </div>
                </div>
              </div>
              <div class="flex-1 space-y-2">
                <input type="text" x-model="opts.title" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" placeholder="Заголовок ссылки">
                <input type="text" x-model="opts.subtitle" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" placeholder="Подзаголовок (необязательно)">
              </div>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs text-gray-500 mb-1">Действие</label>
              <select x-model="opts.action" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm">
                <option value="website">Веб-сайт</option><option value="telegram">Telegram</option><option value="phone">Телефон</option><option value="email">Email</option>
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1" x-text="({website:'URL',telegram:'t.me/username',phone:'Телефон',email:'Email'})[opts.action||'website']"></label>
              <input type="text" x-model="opts.value" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                :placeholder="({website:'https://example.com',telegram:'@username',phone:'+7 999 123-45-67',email:'hello@example.com'})[opts.action||'website']">
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs text-gray-500 mb-1">Стиль</label>
              <select x-model="opts.style" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm">
                <option value="one">Заливка</option><option value="two">Контур</option><option value="three">Прозрачный</option>
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">Выравнивание</label>
              <div class="flex gap-1">
                <button type="button" @click="opts.text_align=''"
                  :class="!opts.text_align?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'"
                  class="p-1.5 rounded transition-colors flex-1" title="На всю ширину">
                  <svg class="w-4 h-4 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 12h16M8 8l-4 4 4 4M16 8l4 4-4 4"/></svg>
                </button>
                <button type="button" @click="opts.text_align='left'"
                  :class="opts.text_align==='left'?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'"
                  class="p-1.5 rounded transition-colors flex-1" title="По левому краю">
                  <svg class="w-4 h-4 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="13" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <button type="button" @click="opts.text_align='center'"
                  :class="opts.text_align==='center'?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'"
                  class="p-1.5 rounded transition-colors flex-1" title="По центру">
                  <svg class="w-4 h-4 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="7" y1="12" x2="17" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <button type="button" @click="opts.text_align='right'"
                  :class="opts.text_align==='right'?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'"
                  class="p-1.5 rounded transition-colors flex-1" title="По правому краю">
                  <svg class="w-4 h-4 mx-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="11" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
              </div>
            </div>
          </div>
          <!-- Per-block design overrides -->
          <div style="border-top:1px solid #21262d;padding-top:12px">
            <div class="text-xs text-gray-500 mb-2">Переопределить дизайн кнопки</div>
            <div class="grid grid-cols-2 gap-2 mb-2">
              <div>
                <div class="text-xs text-gray-600 mb-1">Фон</div>
                <label class="flex items-center gap-1.5 cursor-pointer bg-gray-800 border border-gray-700 rounded-lg px-2 py-1.5">
                  <input type="color" x-model="opts.custom_bg" class="w-5 h-5 rounded cursor-pointer border-0 bg-transparent p-0 shrink-0">
                  <span class="text-xs text-gray-500 font-mono" x-text="opts.custom_bg||'авто'"></span>
                  <button x-show="opts.custom_bg" type="button" @click.prevent="opts.custom_bg=''" class="ml-auto text-gray-600 hover:text-red-400 leading-none">×</button>
                </label>
              </div>
              <div>
                <div class="text-xs text-gray-600 mb-1">Текст</div>
                <label class="flex items-center gap-1.5 cursor-pointer bg-gray-800 border border-gray-700 rounded-lg px-2 py-1.5">
                  <input type="color" x-model="opts.custom_color" class="w-5 h-5 rounded cursor-pointer border-0 bg-transparent p-0 shrink-0">
                  <span class="text-xs text-gray-500 font-mono" x-text="opts.custom_color||'авто'"></span>
                  <button x-show="opts.custom_color" type="button" @click.prevent="opts.custom_color=''" class="ml-auto text-gray-600 hover:text-red-400 leading-none">×</button>
                </label>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-xs text-gray-600 shrink-0">Скругление</span>
              <input type="range" x-model.number="opts.custom_radius" min="0" max="50" class="flex-1 accent-blue-500"
                :style="opts.custom_radius===''||opts.custom_radius==null?'opacity:.4':''">
              <span class="text-xs text-gray-500 font-mono w-10 text-right"
                x-text="(opts.custom_radius!==''&&opts.custom_radius!=null)?opts.custom_radius+'px':'авто'"></span>
              <button x-show="opts.custom_radius!==''&&opts.custom_radius!=null" type="button"
                @click.prevent="opts.custom_radius=''" class="text-gray-600 hover:text-red-400 text-sm leading-none">×</button>
            </div>
          </div>
        </div>
      </template>

      <!-- break -->
      <template x-if="typeName(form.block_type_id) === 'break'">
        <div class="space-y-3">
          <div><label class="block text-xs text-gray-500 mb-1">Высота (px)</label>
            <input type="number" x-model.number="opts.height" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm" placeholder="20" min="0" max="300"></div>
          <div><label class="block text-xs text-gray-500 mb-1">Стиль</label>
            <select x-model="opts.style" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm">
              <option value="none">Без линии</option><option value="line">Линия</option><option value="dashed">Пунктир</option>
            </select></div>
        </div>
      </template>

      <!-- video -->
      <template x-if="typeName(form.block_type_id) === 'video'">
        <div class="space-y-3">
          <div><label class="block text-xs text-gray-500 mb-1">YouTube / Vimeo URL</label>
            <input type="text" x-model="opts.url" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" placeholder="https://youtube.com/watch?v=…"></div>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" x-model="opts.is_autoplay" class="rounded accent-blue-500">
            <span class="text-sm text-gray-400">Автовоспроизведение</span>
          </label>
        </div>
      </template>

      <!-- html / zero -->
      <template x-if="['html','zero'].includes(typeName(form.block_type_id))">
        <div><label class="block text-xs text-gray-500 mb-1">HTML</label>
          <textarea x-model="opts.html" rows="7" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-xs font-mono focus:outline-none focus:border-blue-500" placeholder="<div>...</div>"></textarea></div>
      </template>

      <!-- banner -->
      <template x-if="typeName(form.block_type_id) === 'banner'">
        <div class="space-y-3">
          <div>
            <label class="block text-xs text-gray-500 mb-1">Картинка</label>
            <div x-show="opts.picture" class="mb-2 rounded-lg overflow-hidden">
              <img :src="opts.picture" class="w-full h-auto block" style="max-height:160px;object-fit:cover;">
            </div>
            <label class="flex items-center justify-center gap-2 w-full cursor-pointer bg-gray-800 border border-dashed border-gray-600 hover:border-blue-500 text-gray-400 hover:text-blue-400 rounded-lg px-3 py-2.5 text-sm transition-colors"
              @click.prevent="$refs.bannerFile.click()">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
              <span x-text="opts.picture ? 'Заменить' : 'Загрузить картинку'"></span>
            </label>
            <input type="file" x-ref="bannerFile" class="hidden" accept="image/*"
              @change="async function(e){
                const f=e.target.files[0]; if(!f) return;
                const fd=new FormData(); fd.append('file',f);
                const r=await fetch('/admin/upload.php',{method:'POST',body:fd});
                const d=await r.json();
                if(d.url) opts.picture=d.url;
                e.target.value='';
              }($event)">
          </div>
          <div><label class="block text-xs text-gray-500 mb-1">Ссылка при клике</label>
            <input type="text" x-model="opts.link" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" placeholder="https://example.com"></div>
        </div>
      </template>

      <!-- map -->
      <template x-if="typeName(form.block_type_id) === 'map'">
        <div class="space-y-3">
          <div><label class="block text-xs text-gray-500 mb-1">Адрес</label>
            <input type="text" x-model="opts.address" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" placeholder="Москва, Красная площадь"></div>
          <div class="grid grid-cols-3 gap-3">
            <div><label class="block text-xs text-gray-500 mb-1">Широта</label>
              <input type="text" x-model="opts.lat" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm" placeholder="55.7558"></div>
            <div><label class="block text-xs text-gray-500 mb-1">Долгота</label>
              <input type="text" x-model="opts.lng" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm" placeholder="37.6173"></div>
            <div><label class="block text-xs text-gray-500 mb-1">Зум</label>
              <input type="number" x-model.number="opts.zoom" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm" placeholder="14"></div>
          </div>
        </div>
      </template>

      <!-- timer -->
      <template x-if="typeName(form.block_type_id) === 'timer'">
        <div><label class="block text-xs text-gray-500 mb-1">Дата и время</label>
          <input type="datetime-local" x-model="opts.date" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm"></div>
      </template>

      <!-- page -->
      <template x-if="typeName(form.block_type_id) === 'page'">
        <div class="space-y-3">
          <div><label class="block text-xs text-gray-500 mb-1">Заголовок</label>
            <input type="text" x-model="opts.title" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" placeholder="Название страницы"></div>
          <div><label class="block text-xs text-gray-500 mb-1">Slug страницы</label>
            <input type="text" x-model="opts.value" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm" placeholder="my-page"></div>
        </div>
      </template>

      <!-- JSON fallback -->
      <template x-if="JSON_TYPES.includes(typeName(form.block_type_id))">
        <div>
          <label class="block text-xs text-gray-500 mb-1">Options (JSON)</label>
          <textarea x-model="optsJson" rows="9"
            class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-xs font-mono focus:outline-none focus:border-blue-500"
            :placeholder="PLACEHOLDERS[typeName(form.block_type_id)] || '{}'"></textarea>
        </div>
      </template>

      <!-- Anchor -->
      <div>
        <label class="block text-xs text-gray-500 mb-1">Якорь (anchor)</label>
        <input type="text" x-model="form.anchor" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500" placeholder="my-section">
      </div>

      <!-- Visibility -->
      <div class="flex items-center gap-3">
        <span class="text-sm text-gray-400">Видимость:</span>
        <button @click="form.is_visible=!form.is_visible" type="button"
          :class="form.is_visible?'bg-green-600':'bg-gray-700'"
          class="relative w-11 h-6 rounded-full transition-colors">
          <span :class="form.is_visible?'translate-x-6':'translate-x-1'"
            class="absolute top-1 w-4 h-4 bg-white rounded-full transition-transform block"></span>
        </button>
        <span class="text-sm" :class="form.is_visible?'text-green-400':'text-gray-600'"
          x-text="form.is_visible?'Видимый':'Скрытый'"></span>
      </div>

      <p x-show="formError" class="text-red-400 text-sm" x-text="formError"></p>
    </div>

    <!-- Footer -->
    <div class="flex justify-end gap-3 px-5 py-4 shrink-0" style="border-top:1px solid #21262d">
      <button @click="blockModal=false" class="text-gray-400 hover:text-white px-4 py-2 text-sm transition-colors">Отмена</button>
      <button @click="saveBlock()" :disabled="saving"
        class="text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50"
        style="background:#1f6feb"
        x-text="saving?'Сохраняю…':'Сохранить'"></button>
    </div>
  </div>
</div>

<script src="/assets/editor.js"></script>
<script>
const PAGE_ID = <?= json_encode($pageId) ?>;
const BLOCKS  = <?= json_encode($blocks, JSON_UNESCAPED_UNICODE) ?>;
const SECTIONS = <?= json_encode($sections, JSON_UNESCAPED_UNICODE) ?>;

function previewApp(){return{
  blocks:BLOCKS,
  sections:SECTIONS,
  blockModal:false,
  editId:null,
  saving:false,
  formError:'',
  form:{block_type_id:1,section_id:'',is_visible:true,anchor:''},
  opts:{},optsJson:'{}',
  designOpen: false,
  designSaving: false,
  design: {
    screen:            <?= json_encode($theme['screen']           ?? '#ffffff') ?>,
    text_color:        <?= json_encode($theme['text_color']       ?? '#343a40') ?>,
    link_bg:           <?= json_encode($theme['link_bg']          ?? '#ffffff') ?>,
    link_color:        <?= json_encode($theme['link_color']       ?? '#343a40') ?>,
    link_radius:       <?= (int)($theme['link_radius']            ?? 7) ?>,
    link_border_width: <?= (int)($theme['link_border_width']      ?? 0) ?>,
    link_border_color: <?= json_encode($theme['link_border_color'] ?? '#ffffff') ?>,
    link_shadow:       <?= json_encode($theme['link_shadow']       ?? 'none') ?>,
    link_shadow_color: <?= json_encode($theme['link_shadow_color'] ?? 'rgba(0,0,0,.15)') ?>,
    page_font:         <?= json_encode($theme['page_font']         ?? '') ?>,
  },

  blockTypes:BT_LIST.map(id=>({id,label:LABELS[id]||TYPE_MAP[id],icon:ICONS[TYPE_MAP[id]]||''})),

  typeName(id){return TYPE_MAP[id]||'text'},
  getLabel(id){return LABELS[id]||'?'},
  getIcon(name){return ICONS[name]||''},
  loadGoogleFont(f){loadGoogleFont(f);},
  async addSection(){
    const title=prompt('Название секции:','');
    if(!title||!title.trim())return null;
    const d=await(await fetch('/admin/api.php?action=addSection',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({title:title.trim(),page_id:<?= json_encode($pageId) ?>})})).json();
    if(d.ok){this.sections.push(d.section);return d.section;}
    return null;
  },
  txtSize(s){return txtSize(s);},

  editBlock(id){
    const b=this.blocks.find(x=>x.id===id);
    if(!b)return;
    this.editId=id;
    this.form={block_type_id:b.block_type_id,section_id:b.section_id||'',is_visible:!!b.is_visible,anchor:b.anchor||''};
    const o=typeof b.options==='string'?JSON.parse(b.options||'{}'):(b.options||{});
    if(JSON_TYPES.includes(this.typeName(b.block_type_id))){
      this.opts={};this.optsJson=JSON.stringify(o,null,2);
    }else{
      this.opts=o;this.optsJson='{}';
    }
    this.formError='';
    this.blockModal=true;
  },

  openAdd(){
    this.editId=null;
    this.form={block_type_id:1,section_id:'',is_visible:true,anchor:''};
    this.opts={};this.optsJson='{}';
    this.formError='';
    this.blockModal=true;
  },

  selectType(id){
    this.form.block_type_id=id;
    this.opts={};
    this.optsJson=PLACEHOLDERS[this.typeName(id)]||'{}';
  },

  async saveBlock(){
    this.saving=true;this.formError='';
    const name=this.typeName(this.form.block_type_id);
    let options;
    if(JSON_TYPES.includes(name)){
      try{options=JSON.parse(this.optsJson);}
      catch(e){this.formError='Неверный JSON';this.saving=false;return;}
    }else{
      options=this.opts;
    }
    const payload={...this.form,page_id:PAGE_ID,id:this.editId,block_type_name:name,options};
    try{
      const r=await fetch('/admin/api.php?action=save',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
      const d=await r.json();
      if(d.error){this.formError=d.error;}
      else{this.blockModal=false;sessionStorage.setItem('pv_scroll',window.scrollY);location.reload();}
    }catch(e){this.formError='Ошибка сети';}
    this.saving=false;
  },

  async toggleVis(id){
    await fetch('/admin/api.php?action=toggle',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
    sessionStorage.setItem('pv_scroll',window.scrollY);location.reload();
  },

  async deleteBlock(id){
    if(!confirm('Удалить блок?'))return;
    await fetch('/admin/api.php?action=delete',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
    sessionStorage.setItem('pv_scroll',window.scrollY);location.reload();
  },

  applyDesign(){
    const r=document.documentElement;
    r.style.setProperty('--theme-screen-background', this.design.screen);
    r.style.setProperty('--theme-text-color', this.design.text_color);
    r.style.setProperty('--theme-heading-color', this.design.text_color);
    r.style.setProperty('--theme-link-background', this.design.link_bg);
    r.style.setProperty('--block-link-background', this.design.link_bg);
    r.style.setProperty('--theme-link-title-color', this.design.link_color);
    r.style.setProperty('--block-link-title-color', this.design.link_color);
    r.style.setProperty('--theme-link-subtitle-color', this.design.link_color);
    r.style.setProperty('--block-link-subtitle-color', this.design.link_color);
    r.style.setProperty('--theme-link-border-color', this.design.link_border_color);
    const radius=this.design.link_radius+'px';
    const borderW=this.design.link_border_width+'px';
    const sm={light:'0 2px 8px 0',medium:'0 4px 16px 0',heavy:'0 8px 28px 0'};
    const shadow=sm[this.design.link_shadow]||'0 0 0 0';
    const shadowColor=this.design.link_shadow==='none'?'transparent':this.design.link_shadow_color;
    ['--theme-link-border-radius','--block-link-border-radius'].forEach(v=>r.style.setProperty(v,radius));
    ['--theme-link-border-width','--block-link-border-width'].forEach(v=>r.style.setProperty(v,borderW));
    ['--theme-link-border-width-offset','--block-link-border-width-offset'].forEach(v=>r.style.setProperty(v,borderW));
    ['--theme-link-shadow-params','--block-link-shadow-params'].forEach(v=>r.style.setProperty(v,shadow));
    ['--theme-link-shadow-color','--block-link-shadow-color'].forEach(v=>r.style.setProperty(v,shadowColor));
    document.body.style.background=this.design.screen;
    document.body.style.color=this.design.text_color;
    document.body.style.fontFamily=this.design.page_font?(this.design.page_font+',sans-serif'):'';
  },
  async saveDesign(){
    this.designSaving=true;
    try{
      await fetch('/admin/api.php?action=saveSettings',{
        method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify(this.design)
      });
    }finally{this.designSaving=false;}
  },

  init(){
    const s=sessionStorage.getItem('pv_scroll');
    if(s){window.scrollTo(0,parseInt(s));sessionStorage.removeItem('pv_scroll');}

    if(!window.Sortable) return;

    async function reorderFull(){
      const result=[];
      document.querySelectorAll('#blocksSortable > *').forEach(el=>{
        if(el.dataset.blockId){
          result.push({id:el.dataset.blockId,section_id:null});
        } else if(el.classList.contains('section-group')){
          const sid=el.dataset.sectionId;
          el.querySelectorAll('[data-block-id]').forEach(b=>{
            result.push({id:b.dataset.blockId,section_id:sid});
          });
        }
      });
      await fetch('/admin/api.php?action=reorderFull',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({blocks:result})});
      sessionStorage.setItem('pv_scroll',window.scrollY);
      location.reload();
    }

    const mainEl=document.getElementById('blocksSortable');
    if(mainEl){
      Sortable.create(mainEl,{
        handle:'.drag-handle',
        draggable:'.admin-block-wrap',
        group:'blocks',
        animation:150,
        onEnd:reorderFull
      });
    }
    document.querySelectorAll('.section-group-sortable').forEach(el=>{
      Sortable.create(el,{
        handle:'.drag-handle',
        draggable:'.admin-block-wrap',
        group:'blocks',
        animation:150,
        onEnd:reorderFull
      });
    });
  }
};}
</script>
</body>
</html>
