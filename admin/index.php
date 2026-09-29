<?php
require __DIR__ . '/_session.php';
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$cfg = require dirname(__DIR__) . '/config.php';
$ADMIN_LOGIN    = $cfg['admin_login']    ?? 'admin';
$ADMIN_PASSWORD = $cfg['admin_password'] ?? 'admin';

if (isset($_GET['logout'])) { session_destroy(); header('Location: /admin/'); exit; }

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_login'])) {
    if ($_POST['username'] === $ADMIN_LOGIN && $_POST['password'] === $ADMIN_PASSWORD) {
        $_SESSION['admin_auth'] = true; header('Location: /admin/'); exit;
    }
    $loginError = 'Неверный логин или пароль';
}

$isAuth = $_SESSION['admin_auth'] ?? false;

if (!$isAuth):
?><!DOCTYPE html>
<html lang="ru"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Вход — Блоки</title><script src="https://cdn.tailwindcss.com"></script>
<style>
:root{--adm-bg:#f1f5f9;--adm-surface:#ffffff;--adm-raised:#f8fafc;--adm-border:#e2e8f0;--adm-border-s:#d1d5db;--adm-text:#111827;--adm-muted:#6b7280;--adm-subtle:#9ca3af;--adm-nav:rgba(255,255,255,.95)}
[data-theme="dark"]{--adm-bg:#030712;--adm-surface:#111827;--adm-raised:#1f2937;--adm-border:#1f2937;--adm-border-s:#374151;--adm-text:#ffffff;--adm-muted:#9ca3af;--adm-subtle:#6b7280;--adm-nav:rgba(17,24,39,.95)}
.bg-gray-950{background-color:var(--adm-bg)!important}.bg-gray-900{background-color:var(--adm-surface)!important}.bg-gray-800{background-color:var(--adm-raised)!important}.border-gray-800{border-color:var(--adm-border)!important}.border-gray-700{border-color:var(--adm-border-s)!important}.text-white{color:var(--adm-text)!important}.text-gray-400{color:var(--adm-muted)!important}.text-gray-500{color:var(--adm-subtle)!important}.bg-blue-600,.bg-blue-600.text-white{color:#fff!important}
</style>
<script>!function(){var t=localStorage.getItem('adm-theme');if(!t)t=matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';document.documentElement.setAttribute('data-theme',t)}()</script>
</head>
<body class="bg-gray-950 flex items-center justify-center min-h-screen">
<div class="w-full max-w-sm">
  <div class="text-center mb-8">
    <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-600 rounded-xl mb-3">
      <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h6"/>
      </svg>
    </div>
    <h1 class="text-white text-xl font-semibold">Блоки</h1>
    <p class="text-gray-500 text-sm mt-1">timeweb.one-way.dev</p>
  </div>
  <?php if ($loginError): ?>
  <div class="bg-red-500/10 border border-red-500/30 text-red-400 rounded-lg px-4 py-3 mb-4 text-sm">
    <?= htmlspecialchars($loginError) ?>
  </div>
  <?php endif; ?>
  <form method="POST" class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-4">
    <input type="hidden" name="_login" value="1">
    <div>
      <label class="block text-sm text-gray-400 mb-1.5">Логин</label>
      <input type="text" name="username" autocomplete="username" required
        class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2.5 focus:outline-none focus:border-blue-500 transition-colors" placeholder="admin">
    </div>
    <div>
      <label class="block text-sm text-gray-400 mb-1.5">Пароль</label>
      <input type="password" name="password" autocomplete="current-password" required
        class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2.5 focus:outline-none focus:border-blue-500 transition-colors" placeholder="••••••••">
    </div>
    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white rounded-lg py-2.5 font-medium transition-colors">Войти</button>
  </form>
</div>
</body></html>
<?php exit; endif; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Блоки — Админка</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='7' fill='%231f6feb'/><path fill='none' stroke='white' stroke-width='2.5' stroke-linecap='round' d='M7 10h18M7 14h18M7 18h12M7 22h8'/></svg>">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1/Sortable.min.js"></script>
<script src="/admin/js/sidebar.js"></script>
<script src="/admin/js/templates.js?v=1"></script>
<link rel="stylesheet" href="/assets/templates.css?v=1">
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/theme/material-darker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/htmlmixed/htmlmixed.min.js"></script>
<link rel="stylesheet" href="/assets/taplink-frontend.css">
<link rel="stylesheet" href="/assets/blocks.css">
<link rel="stylesheet" href="/assets/zero-public.css?v=1">
<link rel="stylesheet" href="/assets/zero-editor.css?v=1">
<style>
  [x-cloak]{display:none!important}
  /* ─── Admin theme: light default, dark via system preference ─── */
  :root{--adm-bg:#f1f5f9;--adm-surface:#ffffff;--adm-raised:#f8fafc;--adm-border:#e2e8f0;--adm-border-s:#d1d5db;--adm-text:#111827;--adm-muted:#6b7280;--adm-subtle:#9ca3af;--adm-scroll:#d1d5db;--adm-nav:rgba(255,255,255,.95);--adm-row-hover:rgba(0,0,0,.04);--adm-row-border:rgba(226,232,240,.5);--adm-overlay-hover:rgba(0,0,0,.04)}
  [data-theme="dark"]{--adm-bg:#030712;--adm-surface:#111827;--adm-raised:#1f2937;--adm-border:#1f2937;--adm-border-s:#374151;--adm-text:#ffffff;--adm-muted:#9ca3af;--adm-subtle:#6b7280;--adm-scroll:#374151;--adm-nav:rgba(17,24,39,.95);--adm-row-hover:rgba(31,41,55,.3);--adm-row-border:rgba(31,41,55,.4);--adm-overlay-hover:rgba(255,255,255,.05)}
  .bg-gray-950{background-color:var(--adm-bg)!important}.bg-gray-900{background-color:var(--adm-surface)!important}.bg-gray-900\/95{background-color:var(--adm-nav)!important}.bg-gray-800{background-color:var(--adm-raised)!important}.bg-gray-700{background-color:var(--adm-border)!important}
  .hover\:bg-gray-800:hover{background-color:var(--adm-raised)!important}.hover\:bg-gray-700:hover{background-color:var(--adm-border)!important}.hover\:bg-gray-800\/30:hover{background-color:var(--adm-row-hover)!important}.hover\:bg-white\/5:hover{background-color:var(--adm-overlay-hover)!important}
  .border-gray-800{border-color:var(--adm-border)!important}.border-gray-700{border-color:var(--adm-border-s)!important}.border-gray-600{border-color:var(--adm-border-s)!important}.border-gray-800\/40{border-color:var(--adm-row-border)!important}
  .text-white{color:var(--adm-text)!important}.text-gray-200{color:var(--adm-text)!important}.text-gray-300{color:var(--adm-muted)!important}.text-gray-400{color:var(--adm-muted)!important}.text-gray-500{color:var(--adm-subtle)!important}.text-gray-600{color:var(--adm-subtle)!important}
  .hover\:text-white:hover{color:var(--adm-text)!important}.hover\:text-gray-200:hover{color:var(--adm-text)!important}
  .placeholder-gray-600::placeholder{color:var(--adm-subtle)!important}
  .bg-blue-600,.bg-blue-600.text-white{color:#fff!important}
  /* ─────────────────────────────────────────────────────────────── */
  .sb::-webkit-scrollbar{width:3px}.sb::-webkit-scrollbar-track{background:transparent}.sb::-webkit-scrollbar-thumb{background:var(--adm-scroll);border-radius:2px}
  .CodeMirror{height:280px;font-size:13px;font-family:'JetBrains Mono','Fira Mono',monospace;border-radius:0.5rem;}
  /* admin block overlay */
  .admin-block-wrap{position:relative}
  .section-group{display:flex;flex-direction:row;margin-bottom:2px}
  .section-group-label{width:18px;flex-shrink:0;display:flex;align-items:flex-start;justify-content:center;padding-top:8px;writing-mode:vertical-lr;transform:rotate(180deg);font-size:.52rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(129,140,248,.85);white-space:nowrap;overflow:hidden;background:rgba(99,102,241,.1);border-left:2px solid rgba(99,102,241,.45)}
  .section-group-sortable{flex:1;background:rgba(99,102,241,.04);border-left:1px solid rgba(99,102,241,.2);min-height:8px}
  .admin-block-bar{display:flex;align-items:center;gap:4px;padding:0 6px;background:rgba(13,17,23,.3);border-radius:5px 5px 0 0;height:26px;}
  .admin-block-content.is-hidden{opacity:.4}
  .admin-btn{background:none;border:none;cursor:pointer;padding:3px 5px;border-radius:4px;color:#1f2937;line-height:1;transition:color .12s}
  .admin-btn:hover{color:#000}
  .admin-btn.danger:hover{color:#dc2626}
  .admin-btn.vis-off{color:#d97706}
  .drag-handle{cursor:grab;color:#374151;padding:3px 4px;line-height:1}
  .drag-handle:hover{color:#111827}
  .drag-handle:active{cursor:grabbing}
  .admin-add-placeholder{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:12px;margin-top:4px;border:2px dashed var(--adm-border-s);border-radius:10px;color:var(--adm-muted);background:transparent;cursor:pointer;font-size:.875rem;transition:all .15s;}
  .admin-add-placeholder:hover{border-color:var(--adm-subtle);color:var(--adm-text);background:var(--adm-row-hover)}
  #pagePreviewArea *,#pagePreviewArea *::before,#pagePreviewArea *::after{box-sizing:border-box}
</style>
<script>!function(){var t=localStorage.getItem('adm-theme');if(!t)t=matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';document.documentElement.setAttribute('data-theme',t)}()</script>
</head>
<body class="bg-gray-950 text-white" x-data="app()" x-init="init()"
  @sidebar-page-selected.window="selectPage($event.detail.page)"
  @sidebar-page-deleted.window="if(currentPage?.id===$event.detail.pageId){currentPage=null;history.replaceState(null,'','/admin/');}"
  @sidebar-add-page.window="openAddPage()"
  @sidebar-add-folder.window="addFolder()">

<!-- Header -->
<?php $navMode = 'index'; require __DIR__ . '/_nav.php'; ?>

<div class="flex pt-14" style="height:100vh">

  <!-- Sidebar: Pages -->
  <div x-show="activeTab==='pages'">
    <?php $sidebarCurrentId = ''; $sidebarMode = 'select'; require __DIR__ . '/_sidebar.php'; ?>
  </div>

  <!-- Main -->
  <main x-show="activeTab==='pages'" class="flex-1 flex flex-col overflow-hidden">

    <!-- No page selected -->
    <div x-show="!currentPage" class="flex-1 flex items-center justify-center text-gray-600">
      <div class="text-center">
        <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-sm">Выберите страницу слева<br>или <button @click="openAddPage()" class="text-blue-400 hover:underline">создайте новую</button></p>
      </div>
    </div>

    <!-- Visual editor -->
    <div x-show="currentPage" class="flex-1 overflow-y-auto sb flex justify-center" style="background:#e5e7eb">
      <div class="relative w-full max-w-2xl px-4 py-6">
        <!-- View button -->
        <div class="flex justify-end gap-2 mb-2">
          <a x-show="currentPage?.theme?._template_design" :href="'/admin/page-preview.php?page_id='+currentPage?.id+'&design=1'" class="tpl-save-button">Дизайн страницы</a>
          <button type="button" class="tpl-save-button" @click="$dispatch('save-page-template', {page:currentPage})">Сохранить как шаблон</button>
          <a :href="currentPage?.is_main ? '/' : (currentPage?.slug ? '/p/'+currentPage.slug : '#')"
            target="_blank"
            class="flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-800 bg-white hover:bg-gray-50 border border-gray-300 px-3 py-1.5 rounded-lg transition-colors no-underline shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            Просмотр
          </a>
        </div>
        <!-- Spinner -->
        <div x-show="previewLoading" class="absolute inset-0 z-10 flex items-center justify-center" style="background:rgba(229,231,235,.7)">
          <svg class="animate-spin w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
        </div>
        <!-- Rendered page -->
        <div id="pagePreviewArea" style="border-radius:12px;overflow:hidden;box-shadow:0 4px 32px rgba(0,0,0,.12)"></div>
      </div>
    </div>

  </main>

  <?php require __DIR__ . '/_templates.php'; ?>

  <!-- Design tab -->
  <div x-show="activeTab==='design'" x-cloak class="flex-1 overflow-y-auto sb p-8 flex justify-center">
    <div class="w-full max-w-lg space-y-6">
      <h2 class="text-lg font-semibold text-white">Глобальный дизайн</h2>

      <!-- Page section -->
      <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 space-y-4">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Страница</div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs text-gray-500 mb-1.5">Фон страницы</label>
            <div class="flex items-center gap-2 bg-gray-800 border border-gray-700 rounded-lg px-2 py-1.5">
              <input type="color" :value="colorPickerHex(design.screen)" @input="design.screen=colorPickerMerge($event.target.value, design.screen)" class="w-6 h-6 rounded cursor-pointer border-0 bg-transparent p-0 shrink-0">
              <input type="text" x-model="design.screen" class="flex-1 bg-transparent text-white text-xs font-mono focus:outline-none min-w-0" placeholder="#ffffff / #edf2ff00">
            </div>
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1.5">Цвет текста</label>
            <label class="flex items-center gap-2 bg-gray-800 border border-gray-700 rounded-lg px-2 py-1.5 cursor-pointer">
              <input type="color" x-model="design.text_color" class="w-6 h-6 rounded cursor-pointer border-0 bg-transparent p-0 shrink-0">
              <span class="text-xs text-gray-400 font-mono" x-text="design.text_color"></span>
            </label>
          </div>
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1.5">Шрифт страницы</label>
          <select x-model="design.page_font"
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

      <!-- Buttons section -->
      <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 space-y-4">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Кнопки</div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs text-gray-500 mb-1.5">Фон кнопок</label>
            <label class="flex items-center gap-2 bg-gray-800 border border-gray-700 rounded-lg px-2 py-1.5 cursor-pointer">
              <input type="color" x-model="design.link_bg" class="w-6 h-6 rounded cursor-pointer border-0 bg-transparent p-0 shrink-0">
              <span class="text-xs text-gray-400 font-mono" x-text="design.link_bg"></span>
            </label>
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1.5">Текст кнопок</label>
            <label class="flex items-center gap-2 bg-gray-800 border border-gray-700 rounded-lg px-2 py-1.5 cursor-pointer">
              <input type="color" x-model="design.link_color" class="w-6 h-6 rounded cursor-pointer border-0 bg-transparent p-0 shrink-0">
              <span class="text-xs text-gray-400 font-mono" x-text="design.link_color"></span>
            </label>
          </div>
        </div>

        <!-- Preview -->
        <div class="rounded-lg p-3 flex gap-2 items-center" :style="`background:${design.screen}`">
          <div class="flex-1 rounded-md px-3 py-2 text-sm font-medium text-center"
            :style="`background:${design.link_bg};color:${design.link_color};border-radius:${design.link_radius}px`">Кнопка</div>
          <div class="flex-1 rounded-md px-3 py-2 text-sm font-medium text-center border-2"
            :style="`border-color:${design.link_bg};color:${design.link_bg};border-radius:${design.link_radius}px`">Контур</div>
        </div>

        <!-- Radius -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <span class="text-sm text-gray-300">Скругление</span>
            <span class="text-xs text-gray-500 font-mono" x-text="design.link_radius+'px'"></span>
          </div>
          <div class="grid grid-cols-4 gap-1 mb-2">
            <button type="button" @click="design.link_radius=0"
              :class="design.link_radius==0?'border-blue-500 text-blue-400':'border-gray-700 text-gray-500 hover:border-gray-500'"
              class="border rounded-lg py-1.5 flex flex-col items-center gap-1 transition-colors text-xs">
              <span class="w-5 h-3 border-[1.5px] border-current inline-block"></span>Квадрат</button>
            <button type="button" @click="design.link_radius=8"
              :class="design.link_radius==8?'border-blue-500 text-blue-400':'border-gray-700 text-gray-500 hover:border-gray-500'"
              class="border rounded-lg py-1.5 flex flex-col items-center gap-1 transition-colors text-xs">
              <span class="w-5 h-3 border-[1.5px] border-current inline-block" style="border-radius:3px"></span>Слегка</button>
            <button type="button" @click="design.link_radius=16"
              :class="design.link_radius==16?'border-blue-500 text-blue-400':'border-gray-700 text-gray-500 hover:border-gray-500'"
              class="border rounded-lg py-1.5 flex flex-col items-center gap-1 transition-colors text-xs">
              <span class="w-5 h-3 border-[1.5px] border-current inline-block" style="border-radius:6px"></span>Круглые</button>
            <button type="button" @click="design.link_radius=50"
              :class="design.link_radius==50?'border-blue-500 text-blue-400':'border-gray-700 text-gray-500 hover:border-gray-500'"
              class="border rounded-lg py-1.5 flex flex-col items-center gap-1 transition-colors text-xs">
              <span class="w-5 h-3 border-[1.5px] border-current inline-block" style="border-radius:99px"></span>Пилюля</button>
          </div>
          <input type="range" x-model.number="design.link_radius" min="0" max="50" class="w-full accent-blue-500">
        </div>

        <!-- Border -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <span class="text-sm text-gray-300">Обводка</span>
            <span class="text-xs text-gray-500 font-mono" x-text="design.link_border_width+'px'"></span>
          </div>
          <div class="flex items-center gap-2">
            <input type="range" x-model.number="design.link_border_width" min="0" max="4" step="1" class="flex-1 accent-blue-500">
            <label class="flex items-center gap-1 cursor-pointer" title="Цвет обводки">
              <input type="color" x-model="design.link_border_color" class="w-6 h-6 rounded cursor-pointer border border-gray-600 bg-transparent p-0">
            </label>
          </div>
        </div>

        <!-- Shadow -->
        <div>
          <div class="text-sm text-gray-300 mb-1.5">Тень</div>
          <div class="grid grid-cols-4 gap-1">
            <button type="button" @click="design.link_shadow='none'"
              :class="design.link_shadow=='none'?'border-blue-500 text-blue-400':'border-gray-700 text-gray-500 hover:border-gray-500'"
              class="border rounded-lg py-1.5 text-xs transition-colors">Нет</button>
            <button type="button" @click="design.link_shadow='light'"
              :class="design.link_shadow=='light'?'border-blue-500 text-blue-400':'border-gray-700 text-gray-500 hover:border-gray-500'"
              class="border rounded-lg py-1.5 text-xs transition-colors">Лёгкая</button>
            <button type="button" @click="design.link_shadow='medium'"
              :class="design.link_shadow=='medium'?'border-blue-500 text-blue-400':'border-gray-700 text-gray-500 hover:border-gray-500'"
              class="border rounded-lg py-1.5 text-xs transition-colors">Средняя</button>
            <button type="button" @click="design.link_shadow='heavy'"
              :class="design.link_shadow=='heavy'?'border-blue-500 text-blue-400':'border-gray-700 text-gray-500 hover:border-gray-500'"
              class="border rounded-lg py-1.5 text-xs transition-colors">Сильная</button>
          </div>
          <template x-if="design.link_shadow!=='none'">
            <label class="flex items-center gap-2 mt-1.5 cursor-pointer">
              <span class="text-xs text-gray-500">Цвет тени</span>
              <input type="color" x-model="design.link_shadow_color" class="w-6 h-6 rounded cursor-pointer border border-gray-600 bg-transparent p-0">
            </label>
          </template>
        </div>
      </div>

      <button @click="saveDesign()" :disabled="designSaving"
        class="w-full bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white py-2.5 rounded-lg text-sm font-medium transition-colors"
        x-text="designSaving?'Сохраняю…':'Сохранить дизайн'"></button>
    </div>
  </div>

  <!-- Submissions tab -->
  <div x-show="activeTab==='submissions'" x-cloak class="flex-1 overflow-y-auto sb p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold text-white">Последние заявки <span class="text-sm text-gray-500 font-normal">до 25</span></h2>
      <button @click="loadAllSubmissions()" class="text-gray-500 hover:text-white text-sm transition-colors flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        Обновить
      </button>
    </div>

    <div x-show="allSubsLoading" class="flex justify-center py-16 text-gray-500">
      <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
    </div>

    <div x-show="!allSubsLoading && allSubmissions.length===0" class="text-center py-16 text-gray-500 text-sm">Заявок пока нет</div>

    <div x-show="!allSubsLoading && allSubmissions.length>0" class="bg-gray-900 border border-gray-800 rounded-xl overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-800 text-xs text-gray-500 uppercase tracking-wider">
            <th class="text-left px-4 py-3 whitespace-nowrap">Дата</th>
            <th class="text-left px-4 py-3 whitespace-nowrap">Страница</th>
            <th class="text-left px-4 py-3 whitespace-nowrap">Block ID</th>
            <th class="text-left px-4 py-3">Данные</th>
            <th class="px-4 py-3 w-10"></th>
          </tr>
        </thead>
        <tbody>
          <template x-for="s in allSubmissions" :key="s.id">
            <tr class="border-b border-gray-800/40 hover:bg-gray-800/30 transition-colors">
              <td class="px-4 py-3 text-gray-400 whitespace-nowrap text-xs" x-text="new Date(s.created_at).toLocaleString('ru')"></td>
              <td class="px-4 py-3 text-gray-300 whitespace-nowrap text-xs" x-text="s.page_title||'—'"></td>
              <td class="px-4 py-3 font-mono text-gray-600 text-xs whitespace-nowrap" x-text="s.block_id"></td>
              <td class="px-4 py-3">
                <div class="space-y-0.5">
                  <template x-for="[k,v] in Object.entries(s.data)" :key="k">
                    <div class="flex gap-1.5 text-xs">
                      <span class="text-gray-500 shrink-0" x-text="k+':'"></span>
                      <span class="text-gray-200" x-text="v||'—'"></span>
                    </div>
                  </template>
                </div>
              </td>
              <td class="px-4 py-3">
                <button @click="deleteAllSub(s)" class="text-gray-600 hover:text-red-400 p-1 rounded hover:bg-gray-700 transition-colors">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Settings tab -->
  <div x-show="activeTab==='settings'" x-cloak class="flex-1 overflow-y-auto sb p-8 flex justify-center">
    <div class="w-full max-w-lg space-y-6">
      <h2 class="text-lg font-semibold text-white">Настройки</h2>

      <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 space-y-4">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">SEO</div>
        <div>
          <label class="block text-xs text-gray-500 mb-1.5">Заголовок сайта <span class="text-gray-600">(тег &lt;title&gt;)</span></label>
          <input type="text" x-model="siteSettings.seo_title" maxlength="120"
            class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 transition-colors"
            placeholder="Название сайта">
          <p class="text-xs text-gray-600 mt-1">Если не задан — используется название страницы.</p>
        </div>
        <div>
          <label class="block text-xs text-gray-500 mb-1.5">Описание <span class="text-gray-600">(meta description)</span></label>
          <textarea x-model="siteSettings.seo_description" rows="3" maxlength="300"
            class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 transition-colors resize-none"
            placeholder="Краткое описание сайта для поисковиков"></textarea>
        </div>
      </div>

      <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 space-y-4">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Вставка HTML-кода в &lt;head&gt;</div>
        <p class="text-xs text-gray-500 leading-relaxed">Код вставляется на всех опубликованных страницах перед закрывающим тегом <code class="text-gray-400">&lt;/head&gt;</code>. Используйте для подключения метрик, пикселей, шрифтов и других внешних скриптов.</p>
        <div id="headCodeEditor" class="rounded-lg overflow-hidden border border-gray-700"></div>
      </div>

      <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 space-y-3">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Фавиконка</div>
        <div class="flex items-center gap-4">
          <div x-show="siteSettings.favicon_url" class="shrink-0 w-10 h-10 rounded-lg overflow-hidden border border-gray-700 flex items-center justify-center bg-gray-800">
            <img :src="siteSettings.favicon_url" class="w-8 h-8 object-contain">
          </div>
          <div class="flex-1">
            <label class="flex items-center justify-center gap-2 w-full cursor-pointer bg-gray-800 border border-dashed border-gray-600 hover:border-blue-500 text-gray-400 hover:text-blue-400 rounded-lg px-3 py-2.5 text-sm transition-colors"
              :class="faviconUploading && 'opacity-60 pointer-events-none'"
              @click.prevent="!faviconUploading && $refs.faviconFile.click()">
              <template x-if="faviconUploading">
                <svg class="animate-spin" width="14" height="14" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg>
              </template>
              <template x-if="!faviconUploading">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
              </template>
              <span x-text="faviconUploading ? 'Загружаю…' : (siteSettings.favicon_url ? 'Заменить' : 'Загрузить фавиконку')"></span>
            </label>
            <input type="file" x-ref="faviconFile" class="hidden" accept="image/*,.ico"
              @change="async function(e){
                const f=e.target.files[0]; if(!f) return;
                faviconUploading=true;
                try {
                  const fd=new FormData(); fd.append('file',f);
                  const r=await fetch('/admin/upload.php',{method:'POST',body:fd});
                  const d=await r.json();
                  if(d.url) siteSettings.favicon_url=d.url;
                  else alert(d.error||'Ошибка загрузки');
                } catch(ex){ alert('Ошибка загрузки'); }
                faviconUploading=false;
                e.target.value='';
              }($event)">
          </div>
          <button x-show="siteSettings.favicon_url" @click="siteSettings.favicon_url=''"
            class="shrink-0 text-gray-600 hover:text-red-400 transition-colors text-lg leading-none">×</button>
        </div>
        <p class="text-xs text-gray-600">PNG, ICO или SVG. Рекомендуется 32×32 или 64×64 px.</p>
      </div>

      <button @click="saveSiteSettings()" :disabled="siteSettingsSaving"
        class="w-full bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white py-2.5 rounded-lg text-sm font-medium transition-colors"
        x-text="siteSettingsSaving?'Сохраняю…':'Сохранить настройки'"></button>
    </div>
  </div>

  <!-- Payments tab -->
  <div x-show="activeTab==='payments'" x-cloak class="flex-1 overflow-y-auto sb p-8 flex justify-center">
    <div class="w-full max-w-lg space-y-6">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-white">Платёжные системы</h2>
        <button x-show="payments.length===0" @click="openAddPayment('getplatinum')"
          class="flex items-center gap-1.5 text-sm bg-purple-600 hover:bg-purple-500 text-white px-3.5 py-1.5 rounded-lg transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Подключить
        </button>
      </div>

      <!-- Loading -->
      <div x-show="paymentsLoading" class="flex justify-center py-12">
        <svg class="animate-spin w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
      </div>

      <!-- Available providers grid -->
      <div x-show="!paymentsLoading">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Доступные системы</div>
        <div class="grid grid-cols-2 gap-2">
          <template x-for="[key, p] in Object.entries(PAYMENT_PROVIDERS)" :key="key">
            <button @click="openAddPayment(key)"
              :class="payments.some(pm=>pm.provider===key) ? 'border-purple-600/50 bg-purple-900/10' : 'border-gray-800 hover:border-gray-600'"
              class="flex items-center gap-3 p-3 bg-gray-900 border rounded-xl transition-colors text-left relative">
              <span class="text-xl w-8 shrink-0 text-center" x-text="p.icon"></span>
              <div class="min-w-0">
                <div class="text-sm text-white font-medium truncate" x-text="p.name"></div>
                <div class="text-xs text-gray-500 truncate" x-text="p.desc"></div>
              </div>
              <div x-show="payments.some(pm=>pm.provider===key && pm.is_active)" class="absolute top-2 right-2 w-2 h-2 bg-green-500 rounded-full"></div>
            </button>
          </template>
        </div>
      </div>

      <!-- Connected -->
      <template x-for="pm in payments" :key="pm.id">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 space-y-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 text-lg"
              style="background:#7c3aed22;color:#7c3aed">💎</div>
            <div class="flex-1 min-w-0">
              <div class="font-medium text-sm text-white" x-text="pm.label || 'GetPlatinum'"></div>
              <div class="text-xs text-gray-500 mt-0.5">Приём платежей</div>
            </div>
            <div :class="pm.is_active ? 'bg-green-900/50 text-green-400 border-green-800' : 'bg-gray-800 text-gray-500 border-gray-700'"
              class="text-xs px-2 py-0.5 rounded-full border shrink-0"
              x-text="pm.is_active ? 'Активна' : 'Отключена'"></div>
          </div>
          <div class="flex items-center gap-2">
            <button @click="editPayment(pm)"
              class="flex-1 text-sm text-gray-400 hover:text-white hover:bg-gray-800 px-3 py-2 rounded-lg transition-colors text-center">Настройки</button>
            <button @click="togglePayment(pm)"
              class="flex-1 text-sm px-3 py-2 rounded-lg transition-colors text-center"
              :class="pm.is_active ? 'text-yellow-400 hover:bg-yellow-900/30' : 'text-green-400 hover:bg-green-900/30'"
              x-text="pm.is_active ? 'Отключить' : 'Включить'"></button>
            <button @click="deletePayment(pm)"
              class="text-sm text-red-400 hover:bg-red-900/30 px-3 py-2 rounded-lg transition-colors">Удалить</button>
          </div>
          <div class="text-xs text-gray-600 border-t border-gray-800 pt-3">
            <span class="text-gray-500">Callback URL:</span>
            <code class="ml-1 text-gray-400 select-all"><?= htmlspecialchars((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '')) ?>/payment-callback.php</code>
          </div>
        </div>
      </template>

      <!-- Orders list -->
      <div x-show="!paymentsLoading && payments.length>0">
        <div class="flex items-center justify-between mb-3">
          <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Последние заказы</div>
          <button @click="loadOrders()" class="text-xs text-gray-500 hover:text-white transition-colors">Обновить</button>
        </div>
        <div x-show="ordersLoading" class="flex justify-center py-6">
          <svg class="animate-spin w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
        </div>
        <div x-show="!ordersLoading && orders.length===0" class="text-center py-6 text-gray-600 text-sm">Заказов пока нет</div>
        <div x-show="!ordersLoading && orders.length>0" class="space-y-2">
          <template x-for="o in orders" :key="o.id">
            <div class="bg-gray-900 border border-gray-800 rounded-lg p-3 flex items-center gap-3">
              <div class="flex-1 min-w-0">
                <div class="text-sm text-white truncate" x-text="o.deal_id"></div>
                <div class="text-xs text-gray-500 mt-0.5" x-text="(o.amount/100).toFixed(2)+' '+o.currency+' — '+o.created_at"></div>
              </div>
              <div class="text-xs px-2 py-0.5 rounded-full border shrink-0"
                :class="o.status==='paid' ? 'bg-green-900/50 text-green-400 border-green-800' : o.status==='failed' ? 'bg-red-900/50 text-red-400 border-red-800' : 'bg-gray-800 text-gray-500 border-gray-700'"
                x-text="o.status==='paid'?'Оплачен':o.status==='failed'?'Ошибка':'Ожидание'"></div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>

  <!-- Products tab -->
  <div x-show="activeTab==='products'" x-cloak class="flex-1 overflow-y-auto sb p-8 flex justify-center">
    <div class="w-full max-w-2xl space-y-6">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-white">Цифровые товары</h2>
        <button @click="openProductModal()"
          class="flex items-center gap-1.5 text-sm bg-blue-600 hover:bg-blue-500 text-white px-3.5 py-1.5 rounded-lg transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Добавить товар
        </button>
      </div>

      <!-- Loading -->
      <div x-show="productsLoading" class="flex justify-center py-12">
        <svg class="animate-spin w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
      </div>

      <!-- Empty -->
      <div x-show="!productsLoading && products.length===0" class="text-center py-12 text-gray-600 text-sm">
        Товаров пока нет
      </div>

      <!-- Product cards -->
      <div x-show="!productsLoading" class="grid gap-4">
        <template x-for="prod in products" :key="prod.id">
          <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden flex">
            <!-- Image -->
            <div class="w-28 h-28 shrink-0 bg-gray-800 flex items-center justify-center">
              <template x-if="prod.image_url">
                <img :src="prod.image_url" class="w-full h-full object-cover" alt="">
              </template>
              <template x-if="!prod.image_url">
                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              </template>
            </div>
            <!-- Info -->
            <div class="flex-1 p-4 flex flex-col justify-between min-w-0">
              <div>
                <div class="flex items-center gap-2">
                  <span class="font-medium text-sm text-white truncate" x-text="prod.title"></span>
                  <span x-show="!prod.is_active" class="text-xs px-1.5 py-0.5 rounded bg-gray-800 text-gray-500 border border-gray-700 shrink-0">Скрыт</span>
                </div>
                <div class="text-sm text-blue-400 mt-1 font-mono" x-text="(prod.price/100).toFixed(2)+' '+prod.currency"></div>
                <div x-show="prod.success_page_id" class="text-xs text-gray-500 mt-1">
                  Страница после оплаты: <span class="text-gray-400" x-text="productPageTitle(prod.success_page_id)"></span>
                </div>
              </div>
              <div class="flex items-center gap-2 mt-2">
                <button @click="editProduct(prod)" class="text-xs text-gray-400 hover:text-white hover:bg-gray-800 px-2.5 py-1.5 rounded-lg transition-colors">Редактировать</button>
                <button @click="deleteProduct(prod)" class="text-xs text-red-400 hover:bg-red-900/30 px-2.5 py-1.5 rounded-lg transition-colors">Удалить</button>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>

  <!-- Modules tab -->
  <div x-show="activeTab==='modules'" x-cloak class="flex-1 overflow-y-auto sb p-8 flex justify-center">
    <div class="w-full max-w-2xl space-y-6">
      <h2 class="text-lg font-semibold text-white">Модули</h2>

      <!-- Category: Email -->
      <div class="space-y-3">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          Email
        </div>

        <!-- Module: Personal email mailing -->
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
          <button @click="modulesUI.emailOpen=!modulesUI.emailOpen;if(modulesUI.emailOpen)loadModuleEmail()"
            class="w-full flex items-center justify-between px-5 py-4 text-left transition-colors" style="opacity:.85" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='.85'">
            <div>
              <div class="text-sm font-medium text-white">Рассылка с личного аккаунта</div>
              <div class="text-xs text-gray-500 mt-0.5">Отправка писем через вашу личную почту (Mail.ru / Яндекс)</div>
            </div>
            <svg class="w-4 h-4 text-gray-500 transition-transform shrink-0 ml-3" :class="modulesUI.emailOpen?'rotate-180':''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div x-show="modulesUI.emailOpen" x-transition class="border-t border-gray-800 px-5 py-5 space-y-4">
            <!-- Loading -->
            <div x-show="modulesUI.emailLoading" class="flex justify-center py-4">
              <svg class="animate-spin w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            </div>
            <div x-show="!modulesUI.emailLoading" class="space-y-4">
              <!-- Mail provider -->
              <div>
                <label class="block text-xs text-gray-500 mb-1.5">Тип почты</label>
                <div class="flex gap-2">
                  <button @click="moduleEmail.provider='mail'"
                    :class="moduleEmail.provider==='mail' ? 'bg-blue-600 border-blue-500 text-white' : 'bg-gray-800 border-gray-700 text-gray-400 hover:text-white hover:border-gray-600'"
                    class="flex-1 flex items-center justify-center gap-2 border rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>
                    Mail.ru
                  </button>
                  <button @click="moduleEmail.provider='yandex'"
                    :class="moduleEmail.provider==='yandex' ? 'bg-blue-600 border-blue-500 text-white' : 'bg-gray-800 border-gray-700 text-gray-400 hover:text-white hover:border-gray-600'"
                    class="flex-1 flex items-center justify-center gap-2 border rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/></svg>
                    Яндекс
                  </button>
                </div>
              </div>

              <!-- SMTP Username -->
              <div>
                <label class="block text-xs text-gray-500 mb-1.5">SMTP Username (доменная почта)</label>
                <input type="email" x-model="moduleEmail.domain" placeholder="info@example.com" required
                  class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 placeholder-gray-600 transition-colors">
              </div>

              <!-- Sender -->
              <div>
                <label class="block text-xs text-gray-500 mb-1.5">Адрес отправителя</label>
                <div class="flex gap-2">
                  <input type="email" x-model="moduleEmail.sender" placeholder="info@example.com" required
                    class="flex-1 bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 placeholder-gray-600 transition-colors">
                  <button x-show="moduleEmail.domain && moduleEmail.sender!==moduleEmail.domain"
                    @click="moduleEmail.sender=moduleEmail.domain"
                    class="shrink-0 text-xs text-blue-400 hover:text-blue-300 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/20 px-2.5 py-2 rounded-lg transition-colors whitespace-nowrap">
                    <span x-text="moduleEmail.domain"></span>
                  </button>
                </div>
              </div>

              <!-- Password -->
              <div>
                <label class="block text-xs text-gray-500 mb-1.5">Пароль приложения</label>
                <div class="relative">
                  <input :type="modulesUI.emailShowPass?'text':'password'" x-model="moduleEmail.password" placeholder="Пароль для SMTP" required
                    class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 pr-10 text-sm focus:outline-none focus:border-blue-500 placeholder-gray-600 transition-colors">
                  <button type="button" @click="modulesUI.emailShowPass=!modulesUI.emailShowPass"
                    class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-white transition-colors p-1">
                    <svg x-show="!modulesUI.emailShowPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="modulesUI.emailShowPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                  </button>
                </div>
                <p class="text-xs text-gray-600 mt-1.5">Для Mail.ru и Яндекс используйте <a href="https://id.mail.ru/security/passwords" target="_blank" class="text-blue-400 hover:underline">пароль приложения</a>, а не основной пароль.</p>
              </div>

              <!-- Save -->
              <div class="flex items-center justify-between pt-2">
                <p class="text-xs text-red-400" x-show="modulesUI.emailError" x-text="modulesUI.emailError"></p>
                <p class="text-xs text-green-400" x-show="modulesUI.emailSaved" x-transition>Сохранено</p>
                <div></div>
                <button @click="saveModuleEmail()" :disabled="modulesUI.emailSaving||!moduleEmail.domain||!moduleEmail.sender||!moduleEmail.password"
                  class="text-sm font-medium text-white px-4 py-2 rounded-lg transition-colors disabled:opacity-50"
                  style="background:#1f6feb" onmouseover="this.style.background='#388bfd'" onmouseout="this.style.background='#1f6feb'"
                  x-text="modulesUI.emailSaving?'Сохраняю…':'Сохранить'"></button>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Mailings tab -->
  <div x-show="activeTab==='mailings'" x-cloak class="flex-1 overflow-y-auto sb p-8 flex justify-center">
    <div class="w-full max-w-2xl space-y-6">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-white">Рассылки</h2>
        <button @click="openMailingEditor()"
          class="flex items-center gap-1.5 text-sm bg-blue-600 hover:bg-blue-500 text-white px-3.5 py-1.5 rounded-lg transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Новая рассылка
        </button>
      </div>

      <!-- Loading -->
      <div x-show="mailingsLoading" class="flex justify-center py-12">
        <svg class="animate-spin w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
      </div>

      <!-- Empty -->
      <div x-show="!mailingsLoading && mailings.length===0" class="text-center py-12 text-gray-600 text-sm">
        Рассылок пока нет
      </div>

      <!-- Mailing cards -->
      <div x-show="!mailingsLoading" class="space-y-3">
        <template x-for="ml in mailings" :key="ml.id">
          <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 flex items-center justify-between gap-4">
            <div class="min-w-0 flex-1">
              <div class="text-sm font-medium text-white truncate" x-text="ml.subject"></div>
              <div class="text-xs text-gray-500 mt-1" x-text="'Шаблон: '+mailingTemplateLabel(ml.template)"></div>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
              <button @click="editMailing(ml)" class="text-xs text-gray-400 hover:text-white hover:bg-gray-800 px-2.5 py-1.5 rounded-lg transition-colors">Редактировать</button>
              <button @click="deleteMailing(ml)" class="text-xs text-red-400 hover:bg-red-900/30 px-2.5 py-1.5 rounded-lg transition-colors">Удалить</button>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</div>

<!-- ══ Mailing editor modal ══ -->
<div x-show="mailingModal" x-cloak @click.self="mailingModal=false"
  class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.7)">
  <div class="bg-gray-900 border border-gray-800 rounded-2xl w-full max-w-2xl flex flex-col shadow-2xl" style="max-height:90vh">
    <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between shrink-0">
      <h3 class="font-semibold text-white" x-text="mailingForm.id ? 'Редактировать рассылку' : 'Новая рассылка'"></h3>
      <button @click="mailingModal=false" class="text-gray-500 hover:text-white p-1 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="overflow-y-auto sb p-5 space-y-5">

      <!-- Template picker -->
      <div>
        <label class="block text-xs text-gray-500 mb-2">Шаблон оформления</label>
        <div class="grid grid-cols-3 gap-3">
          <template x-for="tpl in mailingTemplates" :key="tpl.id">
            <button @click="mailingForm.template=tpl.id;applyMailingTemplate(tpl.id)"
              :class="mailingForm.template===tpl.id ? 'border-blue-500 ring-1 ring-blue-500/40' : 'border-gray-700 hover:border-gray-500'"
              class="border rounded-xl overflow-hidden text-left transition-all group">
              <div class="bg-white p-3 pointer-events-none" style="transform:scale(.55);transform-origin:top left;height:150px;width:182%;overflow:hidden">
                <div style="font-family:Arial,sans-serif;font-size:13px;line-height:1.5;color:#333" x-html="mailingTplPreview(tpl.id)"></div>
              </div>
              <div class="px-3 py-2 border-t" :class="mailingForm.template===tpl.id ? 'border-blue-500/30 bg-blue-500/10' : 'border-gray-700 bg-gray-800'">
                <div class="text-xs font-medium text-white" x-text="tpl.label"></div>
              </div>
            </button>
          </template>
        </div>
      </div>

      <!-- Subject -->
      <div>
        <label class="block text-xs text-gray-500 mb-1.5">Тема письма</label>
        <input type="text" x-model="mailingForm.subject" placeholder="Тема рассылки" required
          class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 placeholder-gray-600 transition-colors">
      </div>

      <!-- Variables -->
      <div>
        <label class="block text-xs text-gray-500 mb-1.5">Переменные <span class="text-gray-600">— нажмите, чтобы вставить</span></label>
        <div class="flex flex-wrap gap-1.5">
          <button type="button" @click="insertMailingVar('{{name}}')"
            class="text-xs bg-gray-800 border border-gray-700 text-gray-300 hover:text-white hover:border-gray-500 px-2.5 py-1.5 rounded-lg transition-colors font-mono">{{name}} <span class="text-gray-500 font-sans ml-1">Имя</span></button>
          <button type="button" @click="insertMailingVar('{{product}}')"
            class="text-xs bg-gray-800 border border-gray-700 text-gray-300 hover:text-white hover:border-gray-500 px-2.5 py-1.5 rounded-lg transition-colors font-mono">{{product}} <span class="text-gray-500 font-sans ml-1">Товар</span></button>
          <button type="button" @click="insertMailingVar('{{price}}')"
            class="text-xs bg-gray-800 border border-gray-700 text-gray-300 hover:text-white hover:border-gray-500 px-2.5 py-1.5 rounded-lg transition-colors font-mono">{{price}} <span class="text-gray-500 font-sans ml-1">Цена</span></button>
        </div>
      </div>

      <!-- Body -->
      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label class="text-xs text-gray-500" x-text="mailingForm.isHtml?'HTML-код письма':'Текст письма'"></label>
          <button type="button" @click="toggleMailingHtml()"
            :class="mailingForm.isHtml ? 'bg-blue-600 border-blue-500 text-white' : 'bg-gray-800 border-gray-700 text-gray-400 hover:text-white hover:border-gray-500'"
            class="flex items-center gap-1.5 text-xs border px-2.5 py-1 rounded-lg transition-colors font-mono">
            &lt;/&gt; <span class="font-sans" x-text="mailingForm.isHtml?'HTML':'Текст'"></span>
          </button>
        </div>
        <textarea x-ref="mailingBody" x-model="mailingForm.body" rows="14" required
          :placeholder="mailingForm.isHtml?'<table>\\n  <tr>\\n    <td>Контент письма</td>\\n  </tr>\\n</table>':'Текст рассылки…'"
          class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 placeholder-gray-600 transition-colors font-mono leading-relaxed resize-y"></textarea>
      </div>

      <!-- Preview -->
      <div>
        <label class="block text-xs text-gray-500 mb-1.5">Предпросмотр</label>
        <div class="border border-gray-700 rounded-xl overflow-hidden">
          <div x-show="!mailingForm.isHtml" class="bg-white p-5 text-sm text-gray-800 leading-relaxed" style="min-height:100px" x-html="mailingPreviewHtml"></div>
          <iframe x-show="mailingForm.isHtml" style="width:100%;min-height:360px;border:0;background:#fff"
            :srcdoc="mailingPreviewHtml"></iframe>
        </div>
      </div>

      <!-- Error -->
      <p class="text-xs text-red-400" x-show="mailingError" x-text="mailingError"></p>
    </div>
    <div class="px-5 py-4 border-t border-gray-800 flex justify-end gap-2 shrink-0">
      <button @click="mailingModal=false" class="text-gray-400 hover:text-white px-4 py-2 text-sm transition-colors">Отмена</button>
      <button @click="saveMailing()" :disabled="mailingSaving||!mailingForm.subject||!mailingForm.body"
        class="text-sm font-medium text-white px-4 py-2 rounded-lg transition-colors disabled:opacity-50"
        style="background:#1f6feb" onmouseover="this.style.background='#388bfd'" onmouseout="this.style.background='#1f6feb'"
        x-text="mailingSaving?'Сохраняю…':'Сохранить'"></button>
    </div>
  </div>
</div>

<!-- ══ Product modal ══ -->
<div x-show="productModal" x-cloak @click.self="productModal=false"
  class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.7)">
  <div class="bg-gray-900 border border-gray-800 rounded-2xl w-full max-w-md flex flex-col shadow-2xl" style="max-height:90vh">
    <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between shrink-0">
      <h3 class="font-semibold text-white" x-text="productForm.id ? 'Редактировать товар' : 'Новый товар'"></h3>
      <button @click="productModal=false" class="text-gray-500 hover:text-white p-1 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="overflow-y-auto sb p-5 space-y-4">
      <!-- Image upload -->
      <div>
        <label class="block text-xs text-gray-500 mb-1.5">Изображение</label>
        <div class="flex items-center gap-3">
          <div class="w-20 h-20 rounded-lg bg-gray-800 border border-gray-700 overflow-hidden flex items-center justify-center shrink-0">
            <template x-if="productForm.image_url">
              <img :src="productForm.image_url" class="w-full h-full object-cover" alt="">
            </template>
            <template x-if="!productForm.image_url">
              <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </template>
          </div>
          <div class="flex-1">
            <label class="inline-flex items-center gap-1.5 text-sm text-blue-400 hover:text-blue-300 cursor-pointer transition-colors">
              <span x-text="productImageUploading ? 'Загрузка…' : 'Загрузить'"></span>
              <input type="file" accept="image/*" class="hidden" @change="uploadProductImage($event)" :disabled="productImageUploading">
            </label>
            <button x-show="productForm.image_url" @click="productForm.image_url=''"
              class="block text-xs text-red-400 hover:text-red-300 mt-1 transition-colors">Удалить</button>
          </div>
        </div>
      </div>
      <!-- Title -->
      <div>
        <label class="block text-xs text-gray-500 mb-1.5">Название</label>
        <input type="text" x-model="productForm.title" placeholder="Курс, шаблон, файл…"
          class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
      </div>
      <!-- Price -->
      <div class="flex gap-3">
        <div class="flex-1">
          <label class="block text-xs text-gray-500 mb-1.5">Цена (руб.)</label>
          <input type="number" step="0.01" min="0" x-model.number="productForm.priceRub" placeholder="0.00"
            class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 font-mono">
        </div>
      </div>
      <!-- Success page -->
      <div>
        <label class="block text-xs text-gray-500 mb-1.5">Страница после оплаты (необязательно)</label>
        <select x-model="productForm.success_page_id"
          class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
          <option value="">— Не выбрана —</option>
          <template x-for="pg in productPages" :key="pg.id">
            <option :value="pg.id" x-text="pg.title"></option>
          </template>
        </select>
        <p class="text-xs text-gray-600 mt-1">Покупатель будет перенаправлен на эту страницу после успешной оплаты</p>
      </div>
      <!-- Active -->
      <div class="flex items-center gap-2">
        <input type="checkbox" x-model="productForm.is_active" id="prodActive"
          class="w-4 h-4 rounded border-gray-600 bg-gray-800 text-blue-600 focus:ring-blue-500 focus:ring-offset-0">
        <label for="prodActive" class="text-sm text-gray-400">Активен</label>
      </div>
      <!-- Error -->
      <div x-show="productError" class="text-sm text-red-400" x-text="productError"></div>
    </div>
    <div class="px-5 py-4 border-t border-gray-800 flex justify-end gap-2 shrink-0">
      <button @click="productModal=false" class="text-gray-400 hover:text-white px-3 py-1.5 text-sm transition-colors">Отмена</button>
      <button @click="saveProduct()" :disabled="productSaving"
        class="bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white px-4 py-1.5 rounded-lg text-sm font-medium transition-colors"
        x-text="productSaving ? 'Сохраняю…' : 'Сохранить'"></button>
    </div>
  </div>
</div>

<!-- ══ Payment modal (GetPlatinum) ══ -->
<div x-show="paymentModal" x-cloak @click.self="paymentModal=false"
  class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.7)">
  <div class="bg-gray-900 border border-gray-800 rounded-2xl w-full max-w-md flex flex-col shadow-2xl" style="max-height:90vh">
    <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between shrink-0">
      <div>
        <h3 class="font-semibold text-white" x-text="paymentForm.id ? 'Настройки GetPlatinum' : 'Подключить GetPlatinum'"></h3>
      </div>
      <button @click="paymentModal=false" class="text-gray-500 hover:text-white p-1 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="overflow-y-auto sb p-5 space-y-4">
      <!-- Label -->
      <div>
        <label class="block text-xs text-gray-500 mb-1.5">Название (необязательно)</label>
        <input type="text" x-model="paymentForm.label" placeholder="Например: Основная касса"
          class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
      </div>

      <!-- Credential fields -->
      <div class="space-y-3">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Реквизиты</div>
        <template x-for="field in (PAYMENT_PROVIDERS['getplatinum']?.fields||[])" :key="field.key">
          <div>
            <label class="block text-xs text-gray-500 mb-1.5" x-text="field.label"></label>
            <template x-if="field.options">
              <select
                :value="paymentForm.credentials[field.key]||''"
                @change="paymentForm.credentials[field.key]=$event.target.value"
                class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                <option value="" disabled>Выберите…</option>
                <template x-for="o in field.options" :key="o.v">
                  <option :value="o.v" x-text="o.t" :selected="paymentForm.credentials[field.key]==o.v"></option>
                </template>
              </select>
            </template>
            <template x-if="!field.options">
              <input :type="field.secret ? 'password' : 'text'"
                :placeholder="field.placeholder||''"
                :value="paymentForm.credentials[field.key]||''"
                @input="paymentForm.credentials[field.key]=$event.target.value"
                class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 font-mono">
            </template>
            <p x-show="field.hint" class="text-xs text-gray-600 mt-1" x-text="field.hint"></p>
          </div>
        </template>
      </div>

      <!-- Active toggle -->
      <label class="flex items-center gap-3 cursor-pointer">
        <div class="relative">
          <input type="checkbox" x-model="paymentForm.is_active" class="sr-only peer">
          <div class="w-10 h-6 bg-gray-700 peer-checked:bg-purple-600 rounded-full transition-colors"></div>
          <div class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4"></div>
        </div>
        <span class="text-sm text-gray-300">Активна</span>
      </label>

      <p x-show="paymentError" class="text-red-400 text-sm" x-text="paymentError"></p>
    </div>

    <div class="px-5 py-4 border-t border-gray-800 flex justify-end gap-3 shrink-0">
      <button @click="paymentModal=false" class="text-gray-400 hover:text-white px-4 py-2 text-sm transition-colors">Отмена</button>
      <button @click="savePayment()" :disabled="paymentSaving"
        class="bg-purple-600 hover:bg-purple-500 disabled:opacity-50 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors"
        x-text="paymentSaving ? 'Сохраняю…' : 'Сохранить'"></button>
    </div>
  </div>
</div>

<!-- ══ Page modal ══ -->
<div x-show="pageModal" x-cloak @click.self="pageModal=false"
  class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
  <div class="bg-gray-900 border border-gray-700 rounded-xl w-full max-w-sm shadow-2xl">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-800">
      <h3 class="font-semibold" x-text="editPageId ? 'Настройки страницы' : 'Новая страница'"></h3>
      <button @click="pageModal=false" class="text-gray-500 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>
    <div class="p-5 space-y-4">
      <div>
        <label class="block text-sm text-gray-400 mb-1.5">Название</label>
        <input type="text" x-model="pageForm.title" @input="autoSlug()"
          class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500 text-sm"
          placeholder="Главная страница">
      </div>
      <div>
        <label class="block text-sm text-gray-400 mb-1">Slug <span class="text-gray-600 font-mono text-xs">/p/{slug}</span></label>
        <div class="flex items-center bg-gray-800 border border-gray-700 rounded-lg overflow-hidden focus-within:border-blue-500 transition-colors">
          <span class="text-gray-600 text-sm px-3 border-r border-gray-700 py-2 shrink-0">/p/</span>
          <input type="text" x-model="pageForm.slug" @input="pageForm.slugEdited=true"
            class="flex-1 bg-transparent text-white px-3 py-2 focus:outline-none text-sm font-mono"
            placeholder="my-page">
        </div>
        <p class="text-xs text-gray-600 mt-1">Только латиница, цифры, дефис. Для главной страницы slug не нужен.</p>
      </div>

      <!-- Is main toggle -->
      <div class="flex items-center justify-between p-3 bg-gray-800 rounded-lg">
        <div>
          <div class="text-sm text-white font-medium">Главная страница</div>
          <div class="text-xs text-gray-500">Открывается по корневому URL сайта</div>
        </div>
        <button @click="pageForm.is_main = !pageForm.is_main" type="button"
          :class="pageForm.is_main ? 'bg-yellow-500' : 'bg-gray-700'"
          class="relative w-11 h-6 rounded-full transition-colors shrink-0 ml-3">
          <span :class="pageForm.is_main ? 'translate-x-6' : 'translate-x-1'"
            class="absolute top-1 w-4 h-4 bg-white rounded-full transition-transform block"></span>
        </button>
      </div>

      <!-- Folder selector -->
      <div x-show="folders.length>0">
        <label class="block text-sm text-gray-400 mb-1.5">Папка</label>
        <select x-model="pageForm.folder_id"
          class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500 text-sm">
          <option :value="null">Без папки</option>
          <template x-for="f in folders" :key="f.id">
            <option :value="f.id" x-text="f.title"></option>
          </template>
        </select>
      </div>

      <p x-show="pageFormError" class="text-red-400 text-sm" x-text="pageFormError"></p>
    </div>
    <div class="px-5 py-4 border-t border-gray-800 flex justify-end gap-3">
      <button @click="pageModal=false" class="text-gray-400 hover:text-white px-4 py-2 text-sm">Отмена</button>
      <button @click="savePage()" class="bg-blue-600 hover:bg-blue-500 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors">Сохранить</button>
    </div>
  </div>
</div>

<!-- ══ Block modal ══ -->
<div x-show="blockModal" x-cloak @click.self="blockModal=false"
  class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
  <div class="bg-gray-900 border border-gray-700 rounded-xl w-full max-w-lg shadow-2xl flex flex-col max-h-[92vh]">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-800 shrink-0">
      <h3 class="font-semibold" x-text="editId ? 'Редактировать блок' : 'Новый блок'"></h3>
      <button @click="blockModal=false" class="text-gray-500 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Tabs for form blocks -->
    <div x-show="typeName(form.block_type_id)==='form'" class="px-5 pt-3 border-b border-gray-800 flex gap-0 shrink-0">
      <button type="button" @click="blockModalTab='content'"
        :class="blockModalTab==='content'?'border-blue-500 text-white':'border-transparent text-gray-500 hover:text-gray-300'"
        class="px-3 pb-2.5 text-sm border-b-2 transition-colors">Контент</button>
      <button type="button" @click="blockModalTab='modules';loadMailingsIfNeeded()"
        :class="blockModalTab==='modules'?'border-blue-500 text-white':'border-transparent text-gray-500 hover:text-gray-300'"
        class="px-3 pb-2.5 text-sm border-b-2 transition-colors">Модули</button>
    </div>

    <div x-show="blockModalTab==='content'" class="overflow-y-auto sb p-5 space-y-4 flex-1">

      <!-- Type grid (add) -->
      <div x-show="!editId">
        <label class="block text-sm text-gray-400 mb-2">Тип блока</label>
        <div class="grid grid-cols-4 gap-1.5 max-h-52 overflow-y-auto sb pr-1">
          <template x-for="bt in blockTypes" :key="bt.id">
            <button type="button" @click="selectType(bt.id)"
              :class="form.block_type_id == bt.id
                ? 'bg-blue-600 border-blue-500 text-white'
                : 'bg-gray-800 border-gray-700 text-gray-200 hover:border-gray-500'"
              class="border rounded-lg p-2 flex flex-col items-center gap-1 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"
                x-html="bt.icon"></svg>
              <span class="text-xs leading-tight text-center" x-text="bt.label"></span>
            </button>
          </template>
        </div>
      </div>

      <!-- Type badge (edit) -->
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
      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label class="text-sm text-gray-400">Секция</label>
          <button @click="openAddSection()" class="text-xs text-gray-600 hover:text-blue-400 flex items-center gap-1 transition-colors">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Новая секция
          </button>
        </div>
        <select x-model="form.section_id"
          class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500 text-sm">
          <option value="">Без секции</option>
          <template x-for="s in pageSections" :key="s.id">
            <option :value="s.id" x-text="s.title || s.id"></option>
          </template>
        </select>
      </div>

      <!-- text -->
      <template x-if="typeName(form.block_type_id) === 'text'">
        <div>
          <!-- Toolbar -->
          <div class="flex items-center gap-1 flex-wrap bg-gray-800 border border-gray-700 rounded-t-lg px-2 py-1.5">
            <!-- Size -->
            <select x-model="opts.text_size"
              class="bg-gray-900 text-white text-xs rounded px-2 py-1 focus:outline-none cursor-pointer border-0">
              <option value="h1">Большой заголовок</option>
              <option value="h2">Средний заголовок</option>
              <option value="h3">Маленький заголовок</option>
              <option value="lg">Большой текст</option>
              <option value="md">Средний текст</option>
              <option value="sm">Маленький текст</option>
            </select>
            <div class="w-px h-5 bg-gray-600 mx-0.5 shrink-0"></div>
            <!-- Font -->
            <select x-model="opts.font" @change="loadGoogleFont(opts.font)"
              class="bg-gray-900 text-white text-xs rounded px-2 py-1 focus:outline-none cursor-pointer border-0 max-w-[120px]">
              <option value="">По умолчанию</option>
              <option value="Inter">Inter</option>
              <option value="Roboto">Roboto</option>
              <option value="Montserrat">Montserrat</option>
              <option value="Oswald">Oswald</option>
              <option value="Raleway">Raleway</option>
              <option value="Poppins">Poppins</option>
              <option value="Nunito">Nunito</option>
              <option value="Lato">Lato</option>
              <option value="Open Sans">Open Sans</option>
              <option value="Playfair Display">Playfair Display</option>
              <option value="Merriweather">Merriweather</option>
              <option value="PT Sans">PT Sans</option>
              <option value="PT Serif">PT Serif</option>
              <option value="Ubuntu">Ubuntu</option>
              <option value="Rubik">Rubik</option>
              <option value="Russo One">Russo One</option>
            </select>
            <div class="w-px h-5 bg-gray-600 mx-0.5 shrink-0"></div>
            <!-- Alignment -->
            <button type="button" @click="opts.text_align='left'"
              :class="(opts.text_align||'left')==='left'?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'"
              class="p-1.5 rounded transition-colors" title="По левому краю">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/></svg>
            </button>
            <button type="button" @click="opts.text_align='center'"
              :class="opts.text_align==='center'?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'"
              class="p-1.5 rounded transition-colors" title="По центру">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
            </button>
            <button type="button" @click="opts.text_align='right'"
              :class="opts.text_align==='right'?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'"
              class="p-1.5 rounded transition-colors" title="По правому краю">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="6" y1="18" x2="21" y2="18"/></svg>
            </button>
            <button type="button" @click="opts.text_align='justify'"
              :class="opts.text_align==='justify'?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'"
              class="p-1.5 rounded transition-colors" title="По ширине">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="w-px h-5 bg-gray-600 mx-0.5 shrink-0"></div>
            <!-- Bold -->
            <button type="button" @click="opts.bold = txtWeight(opts.text_size, opts.bold)==='700' ? false : true"
              :class="txtWeight(opts.text_size,opts.bold)==='700'?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'"
              class="w-7 h-7 rounded font-bold text-sm flex items-center justify-center transition-colors" title="Жирный">B</button>
            <!-- Italic -->
            <button type="button" @click="opts.italic=!opts.italic"
              :class="opts.italic?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'"
              class="w-7 h-7 rounded italic text-sm flex items-center justify-center transition-colors" title="Курсив">I</button>
            <!-- Strikethrough -->
            <button type="button" @click="opts.strikethrough=!opts.strikethrough"
              :class="opts.strikethrough?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'"
              class="w-7 h-7 rounded line-through text-sm flex items-center justify-center transition-colors" title="Зачёркнутый">S</button>
            <!-- Underline -->
            <button type="button" @click="opts.underline=!opts.underline"
              :class="opts.underline?'bg-blue-600 text-white':'text-gray-400 hover:text-white hover:bg-gray-700'"
              class="w-7 h-7 rounded underline text-sm flex items-center justify-center transition-colors" title="Подчёркнутый">U</button>
            <div class="w-px h-5 bg-gray-600 mx-0.5 shrink-0"></div>
            <!-- Color -->
            <div class="flex items-center gap-1.5 bg-gray-900 rounded px-2 py-1 cursor-pointer" title="Цвет текста">
              <input type="color" x-model="opts.color"
                class="w-5 h-5 rounded-full cursor-pointer border-0 bg-transparent p-0 shrink-0">
              <span class="text-xs text-gray-400 font-mono" x-text="opts.color||'авто'"></span>
            </div>
          </div>
          <!-- Textarea with live-preview styles -->
          <textarea x-model="opts.text" rows="5"
            class="w-full bg-gray-900 border border-t-0 border-gray-700 text-white rounded-b-lg px-4 py-3 focus:outline-none focus:border-blue-500 resize-none"
            placeholder="Введите текст…"
            :style="`font-family:${opts.font?opts.font+',sans-serif':'inherit'};font-size:${txtSize(opts.text_size)};font-weight:${txtWeight(opts.text_size,opts.bold)};font-style:${opts.italic?'italic':'normal'};text-decoration:${[opts.underline?'underline':'',opts.strikethrough?'line-through':''].filter(Boolean).join(' ')||'none'};text-align:${opts.text_align||'left'};color:${opts.color||'inherit'};line-height:${txtLineHeight(opts.text_size)}`"></textarea>
        </div>
      </template>

      <!-- link -->
      <template x-if="typeName(form.block_type_id) === 'link'">
        <div class="space-y-4">
          <!-- Icon picker + text fields -->
          <div>
            <label class="block text-xs text-gray-500 mb-2">Текст ссылки</label>
            <div class="flex gap-3 items-start">
              <!-- Icon picker -->
              <div class="relative shrink-0" x-data="{open:false}">
                <button type="button" @click="open=!open"
                  class="w-20 h-[72px] border-2 border-dashed rounded-xl flex flex-col items-center justify-center gap-1 transition-colors"
                  :class="opts.icon && opts.icon!=='none' ? 'border-gray-500 bg-gray-800/60' : 'border-gray-700 hover:border-gray-500'">
                  <template x-if="opts.icon && opts.icon!=='none'">
                    <svg class="w-8 h-8 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                      <path :d="LINK_ICONS[opts.icon]||''"/>
                    </svg>
                  </template>
                  <template x-if="!opts.icon || opts.icon==='none'">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  </template>
                  <span class="text-xs leading-none" :class="opts.icon&&opts.icon!=='none'?'text-gray-500':'text-gray-600'"
                    x-text="opts.icon&&opts.icon!=='none'?opts.icon:'иконка'"></span>
                </button>
                <!-- Icon grid dropdown -->
                <div x-show="open" x-cloak @click.outside="open=false"
                  class="absolute left-0 top-full mt-1 z-30 bg-gray-900 border border-gray-700 rounded-xl p-2 shadow-2xl" style="min-width:232px">
                  <div class="grid grid-cols-5 gap-1">
                    <button type="button" @click="opts.icon='none';open=false"
                      :class="!opts.icon||opts.icon==='none'?'bg-blue-600 text-white':'text-gray-500 hover:bg-gray-800 hover:text-white'"
                      class="h-9 rounded-lg text-xs transition-colors">нет</button>
                    <template x-for="ico in LINK_ICONS_LIST" :key="ico.id">
                      <button type="button" @click="opts.icon=ico.id;open=false"
                        :class="opts.icon===ico.id?'bg-blue-600 text-white':'text-gray-400 hover:bg-gray-800 hover:text-white'"
                        :title="ico.label"
                        class="h-9 w-full rounded-lg flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                          <path :d="ico.path"/>
                        </svg>
                      </button>
                    </template>
                  </div>
                </div>
              </div>
              <!-- Title + subtitle -->
              <div class="flex-1 space-y-2">
                <input type="text" x-model="opts.title"
                  class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                  placeholder="Заголовок ссылки">
                <input type="text" x-model="opts.subtitle"
                  class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                  placeholder="Подзаголовок (необязательно)">
              </div>
            </div>
          </div>
          <!-- Action + URL -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs text-gray-500 mb-1">Действие</label>
              <select x-model="opts.action" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm">
                <option value="website">Веб-сайт</option>
                <option value="telegram">Telegram</option>
                <option value="phone">Телефон</option>
                <option value="email">Email</option>
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1"
                x-text="({website:'URL',telegram:'t.me/username',phone:'Телефон',email:'Email'})[opts.action||'website']"></label>
              <input type="text" x-model="opts.value"
                class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                :placeholder="({website:'https://example.com',telegram:'@username или t.me/...',phone:'+7 999 123-45-67',email:'hello@example.com'})[opts.action||'website']">
            </div>
          </div>
          <!-- Style -->
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
          <div style="border-top:1px solid #374151;padding-top:12px">
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
        <div class="space-y-4">
          <div>
            <label class="block text-xs text-gray-500 mb-2">Стиль</label>
            <div class="grid grid-cols-3 gap-2">
              <button type="button" @click="opts.style='none'"
                :class="(!opts.style||opts.style==='none')?'border-blue-500 bg-blue-600/10':'border-gray-700 hover:border-gray-500'"
                class="border rounded-lg p-3 flex flex-col items-center gap-2 transition-colors cursor-pointer">
                <div class="w-full h-8 flex items-center justify-center"></div>
                <span class="text-xs" :class="(!opts.style||opts.style==='none')?'text-blue-400':'text-gray-500'">Отступ</span>
              </button>
              <button type="button" @click="opts.style='line'"
                :class="opts.style==='line'?'border-blue-500 bg-blue-600/10':'border-gray-700 hover:border-gray-500'"
                class="border rounded-lg p-3 flex flex-col items-center gap-2 transition-colors cursor-pointer">
                <div class="w-full h-8 flex items-center"><div class="w-full border-t border-gray-400"></div></div>
                <span class="text-xs" :class="opts.style==='line'?'text-blue-400':'text-gray-500'">Линия</span>
              </button>
              <button type="button" @click="opts.style='dashed'"
                :class="opts.style==='dashed'?'border-blue-500 bg-blue-600/10':'border-gray-700 hover:border-gray-500'"
                class="border rounded-lg p-3 flex flex-col items-center gap-2 transition-colors cursor-pointer">
                <div class="w-full h-8 flex items-center"><div class="w-full border-t border-dashed border-gray-400"></div></div>
                <span class="text-xs" :class="opts.style==='dashed'?'text-blue-400':'text-gray-500'">Пунктир</span>
              </button>
            </div>
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1.5">Высота</label>
            <div class="flex items-center gap-3">
              <input type="range" x-model.number="opts.height" min="0" max="200" class="flex-1 accent-blue-500">
              <span class="text-sm text-gray-400 font-mono w-12 text-right" x-text="(opts.height||20)+'px'"></span>
            </div>
          </div>
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

      <template x-if="typeName(form.block_type_id) === 'zero'">
        <div class="zero-editor-launch">
          <p>Свободный холст с текстом, изображениями, кнопками и фигурами. Каждый элемент — отдельный слой.</p>
          <button type="button" @click="ZeroEditor.open(opts, data => { opts = { ...opts, zero: data }; })">Открыть редактор слоёв</button>
          <p x-show="opts.zero" style="margin-top:10px" x-text="'Слоёв: ' + (opts.zero?.layers?.length || 0) + '. После редактирования сохраните блок.'"></p>
          <details x-show="!opts.zero"><summary>HTML существующего блока</summary><p>HTML сохранится, но после применения слоёв страница будет показывать макет из редактора. Автоматического разбора HTML на слои нет.</p><textarea x-model="opts.html" rows="5"></textarea></details>
        </div>
      </template>

      <!-- Legacy HTML -->
      <template x-if="typeName(form.block_type_id) === 'html'">
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
              x-data="{uploading:false}"
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

      <!-- Pictures block -->
      <template x-if="typeName(form.block_type_id) === 'pictures'">
        <div class="space-y-3">
          <label class="block text-xs text-gray-500 mb-1">Фотографии карусели</label>
          <div class="grid grid-cols-3 gap-2">
            <template x-for="(item, i) in (opts.list || [])" :key="i">
              <div class="relative group" style="aspect-ratio:1/1">
                <img :src="item.p?.filename?.startsWith('/') ? item.p.filename : 'https://p.taplink.st/p/'+item.p?.filename"
                  class="w-full h-full object-cover rounded-lg bg-gray-800">
                <button type="button" @click="opts.list.splice(i,1)"
                  class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs leading-none opacity-0 group-hover:opacity-100 transition-opacity">×</button>
              </div>
            </template>
            <label class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-700 cursor-pointer hover:border-blue-500 transition-colors text-gray-600 hover:text-blue-400" style="aspect-ratio:1/1">
              <template x-if="!picturesUploading">
                <span>
                  <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
                  <span class="text-xs">Фото</span>
                </span>
              </template>
              <template x-if="picturesUploading">
                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
              </template>
              <input type="file" accept="image/*" multiple class="hidden" @change="uploadPicturesImages($event)">
            </label>
          </div>
          <!-- Dots settings -->
          <div class="border-t border-gray-700 pt-3 space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs text-gray-400">Точки навигации</span>
              <label class="flex items-center gap-1.5 cursor-pointer select-none">
                <input type="checkbox" :checked="opts.dots?.show ?? true"
                  @change="if(!opts.dots)opts.dots={show:true,shape:'circle'};opts.dots.show=$event.target.checked"
                  class="accent-blue-500 cursor-pointer">
                <span class="text-xs text-gray-400">Показывать</span>
              </label>
            </div>
            <template x-if="opts.dots?.show ?? true">
              <div class="space-y-2">
                <div class="flex items-center gap-2">
                  <span class="text-xs text-gray-500 w-12 shrink-0">Цвет</span>
                  <input type="color" :value="opts.dots?.color || '#333333'"
                    @input="if(!opts.dots)opts.dots={show:true,shape:'circle'};opts.dots.color=$event.target.value"
                    class="w-8 h-7 rounded cursor-pointer border-0 bg-transparent p-0">
                  <button type="button" @click="if(opts.dots)delete opts.dots.color"
                    class="text-xs text-gray-500 hover:text-gray-300">авто</button>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-xs text-gray-500 w-12 shrink-0">Форма</span>
                  <div class="flex gap-1">
                    <template x-for="sh in [{v:'circle',l:'●'},{v:'square',l:'■'},{v:'line',l:'▬'}]" :key="sh.v">
                      <button type="button"
                        @click="if(!opts.dots)opts.dots={show:true};opts.dots.shape=sh.v"
                        :class="(opts.dots?.shape||'circle')===sh.v ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300'"
                        class="px-2 py-0.5 text-sm rounded leading-none" x-text="sh.l"></button>
                    </template>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </template>

      <!-- Form editor -->
      <template x-if="typeName(form.block_type_id) === 'form'">
        <div class="space-y-3">
          <div>
            <label class="text-sm text-gray-400 mb-2 block">Поля формы</label>
            <div class="flex flex-wrap gap-1">
              <button type="button" @click="opts.fields = [...(opts.fields||[]), {type_id:1,title:'Имя',text:'',required:false,idx:(opts.fields||[]).length+1}]"
                class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 px-2 py-1 rounded">+ Имя</button>
              <button type="button" @click="opts.fields = [...(opts.fields||[]), {type_id:2,title:'ФИО',text:'',required:false,idx:(opts.fields||[]).length+1}]"
                class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 px-2 py-1 rounded">+ ФИО</button>
              <button type="button" @click="opts.fields = [...(opts.fields||[]), {type_id:3,title:'',text:'',required:false,idx:(opts.fields||[]).length+1}]"
                class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 px-2 py-1 rounded">+ Текст</button>
              <button type="button" @click="opts.fields = [...(opts.fields||[]), {type_id:5,title:'Телефон',text:'',required:false,idx:(opts.fields||[]).length+1}]"
                class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 px-2 py-1 rounded">+ Телефон</button>
              <button type="button" @click="opts.fields = [...(opts.fields||[]), {type_id:6,title:'Email',text:'',required:true,idx:(opts.fields||[]).length+1}]"
                class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 px-2 py-1 rounded">+ Email</button>
              <button type="button" @click="opts.fields = [...(opts.fields||[]), {type_id:12,title:'Количество',text:'',required:false,idx:(opts.fields||[]).length+1}]"
                class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 px-2 py-1 rounded">+ Число</button>
              <button type="button" @click="opts.fields = [...(opts.fields||[]), {type_id:9,title:'Дата',text:'',required:false,idx:(opts.fields||[]).length+1}]"
                class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 px-2 py-1 rounded">+ Дата</button>
              <button type="button" @click="opts.fields = [...(opts.fields||[]), {type_id:13,title:'Время',text:'',required:false,idx:(opts.fields||[]).length+1}]"
                class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 px-2 py-1 rounded">+ Время</button>
              <button type="button" @click="opts.fields = [...(opts.fields||[]), {type_id:7,title:'Выбор',text:'',required:false,idx:(opts.fields||[]).length+1,options:['Вариант 1','Вариант 2']}]"
                class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 px-2 py-1 rounded">+ Выбор</button>
              <button type="button" @click="opts.fields = [...(opts.fields||[]), {type_id:10,title:'Список',text:'',required:false,idx:(opts.fields||[]).length+1,options:['Пункт 1','Пункт 2']}]"
                class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 px-2 py-1 rounded">+ Список</button>
              <button type="button" @click="opts.fields = [...(opts.fields||[]), {type_id:11,title:'Согласие',text:'',required:false,idx:(opts.fields||[]).length+1}]"
                class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 px-2 py-1 rounded">+ Галочка</button>
              <button type="button" @click="opts.fields = [...(opts.fields||[]), {type_id:8,title:'Страна',text:'',required:false,idx:(opts.fields||[]).length+1}]"
                class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 px-2 py-1 rounded">+ Страна</button>
            </div>
          </div>
          <div x-ref="formFieldsList" class="space-y-2">
          <template x-for="(field, fi) in (opts.fields||[])" :key="fi">
            <div class="bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 space-y-2">
              <div class="flex gap-2 items-center">
                <span class="form-field-handle cursor-grab active:cursor-grabbing text-gray-600 hover:text-gray-400 shrink-0" title="Перетащить">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="5" r="1.5"/><circle cx="15" cy="5" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/></svg>
                </span>
                <span class="text-xs text-gray-500 w-14 shrink-0" x-text="({1:'Имя',2:'ФИО',3:'Текст',5:'Тел.',6:'Email',7:'Выбор',8:'Страна',9:'Дата',10:'Список',11:'Галочка',12:'Число',13:'Время'})[field.type_id]||'Текст'"></span>
                <input type="text" x-model="field.title" placeholder="Подпись"
                  class="flex-1 bg-transparent text-white text-sm focus:outline-none min-w-0">
                <label class="flex items-center gap-1 text-xs text-gray-500 shrink-0 cursor-pointer">
                  <input type="checkbox" x-model="field.required" class="accent-blue-500">
                  обяз.
                </label>
                <button type="button" @click="opts.fields.splice(fi,1)"
                  class="text-gray-600 hover:text-red-400 text-lg leading-none shrink-0">×</button>
              </div>
              <!-- Options editor for radio / select -->
              <template x-if="field.type_id==7 || field.type_id==10">
                <div class="space-y-1 pl-14">
                  <template x-for="(opt, oi) in (field.options||[])" :key="oi">
                    <div class="flex gap-1 items-center">
                      <span class="text-gray-600 text-xs w-4 shrink-0" x-text="field.type_id==7?'○':'•'"></span>
                      <input type="text" :value="opt" @input="field.options[oi]=$event.target.value"
                        class="flex-1 bg-gray-900 border border-gray-700 text-white text-xs rounded px-2 py-1 focus:outline-none focus:border-blue-500 min-w-0">
                      <button type="button" @click="field.options.splice(oi,1)"
                        class="text-gray-600 hover:text-red-400 text-sm leading-none shrink-0">×</button>
                    </div>
                  </template>
                  <button type="button" @click="if(!field.options)field.options=[];field.options.push('Вариант '+(field.options.length+1))"
                    class="text-xs text-blue-400 hover:text-blue-300">+ вариант</button>
                </div>
              </template>
            </div>
          </template>
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Текст кнопки</label>
            <input type="text" x-model="opts.form_btn" placeholder="Отправить"
              class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
          </div>
          <!-- Product -->
          <div>
            <label class="block text-xs text-gray-500 mb-1">Товар (оплата при отправке)</label>
            <select x-model="opts.product_id"
              @focus="if(!formProducts.length)loadFormProducts()"
              class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
              <option value="">— Без оплаты —</option>
              <template x-for="pr in formProducts" :key="pr.id">
                <option :value="pr.id" x-text="pr.title + ' — ' + (pr.price/100).toFixed(2) + ' ' + pr.currency"></option>
              </template>
            </select>
            <p class="text-xs text-gray-600 mt-1">При заполнении формы создастся заказ и покупатель перейдёт к оплате</p>
          </div>
        </div>
      </template>

      <!-- Media editor -->
      <template x-if="typeName(form.block_type_id) === 'media'">
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <label class="text-sm text-gray-400">Элементы</label>
            <button type="button" @click="opts.fields = [...(opts.fields||[]), {title:'',text:'',thumb:{t:'none'}}]"
              class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 px-2 py-1 rounded">+ Добавить</button>
          </div>
          <template x-for="(field, fi) in (opts.fields||[])" :key="fi">
            <div class="bg-gray-800 border border-gray-700 rounded-lg p-3 space-y-2">
              <div class="flex items-center gap-2">
                <!-- Icon picker -->
                <div x-data="{open:false}" class="relative shrink-0">
                  <button type="button" @click="open=!open"
                    class="w-9 h-9 rounded-lg bg-gray-700 border border-gray-600 flex items-center justify-center hover:bg-gray-600">
                    <template x-if="(field.thumb||{}).t==='p' && (field.thumb.p||{}).filename">
                      <img :src="field.thumb.p.filename" class="w-6 h-6 object-contain">
                    </template>
                    <template x-if="(field.thumb||{}).t==='i' && field.thumb.i">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-200">
                        <path :d="MEDIA_ICONS[field.thumb.i]||''"/>
                      </svg>
                    </template>
                    <template x-if="!(field.thumb||{}).t || (field.thumb||{}).t==='none' || ((field.thumb||{}).t==='i' && !field.thumb.i) || ((field.thumb||{}).t==='p' && !(field.thumb.p||{}).filename)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gray-600">
                        <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/>
                      </svg>
                    </template>
                  </button>
                  <div x-show="open" @click.outside="open=false"
                    class="absolute left-0 top-10 z-50 bg-gray-900 border border-gray-700 rounded-xl shadow-2xl p-3 w-64">
                    <div class="text-xs text-gray-500 mb-2">Иконка</div>
                    <div class="grid grid-cols-6 gap-1 mb-3">
                      <button type="button" @click="field.thumb={t:'none'};open=false"
                        class="w-8 h-8 rounded-lg bg-gray-800 border border-gray-700 flex items-center justify-center text-gray-500 hover:bg-gray-700 text-xs">✕</button>
                      <template x-for="ico in MEDIA_ICONS_LIST" :key="ico.id">
                        <button type="button" @click="field.thumb={t:'i',i:ico.id};open=false"
                          :class="(field.thumb||{}).t==='i' && field.thumb.i===ico.id ? 'bg-blue-600 border-blue-500' : 'bg-gray-800 border-gray-700 hover:bg-gray-700'"
                          class="w-8 h-8 rounded-lg border flex items-center justify-center" :title="ico.label">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-gray-200">
                            <path :d="ico.path"/>
                          </svg>
                        </button>
                      </template>
                    </div>
                    <div class="text-xs text-gray-500 mb-1">Своя картинка (URL)</div>
                    <input type="text" placeholder="/uploads/icons/my.svg"
                      :value="(field.thumb||{}).t==='p' ? ((field.thumb.p||{}).filename||'') : ''"
                      @input="field.thumb={t:'p',p:{filename:$event.target.value}}"
                      class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-2 py-1 text-xs focus:outline-none focus:border-blue-500">
                  </div>
                </div>
                <div class="flex-1 min-w-0 space-y-1">
                  <input type="text" x-model="field.title" placeholder="Заголовок"
                    class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-blue-500">
                  <input type="text" x-model="field.text" placeholder="Подпись"
                    class="w-full bg-gray-700 border border-gray-600 text-white rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-blue-500">
                </div>
                <button type="button" @click="opts.fields.splice(fi,1)"
                  class="text-gray-600 hover:text-red-400 text-xl leading-none shrink-0">×</button>
              </div>
            </div>
          </template>
        </div>
      </template>

      <!-- messenger -->
      <template x-if="typeName(form.block_type_id) === 'messenger'">
        <div class="space-y-3">
          <template x-for="(item, idx) in (opts.items||[])" :key="idx">
            <div class="flex items-start gap-2 bg-gray-800 border border-gray-700 rounded-lg p-3">
              <div class="flex-1 space-y-2">
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="block text-xs text-gray-500 mb-1">Мессенджер</label>
                    <select x-model="item.messenger" class="w-full bg-gray-900 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm">
                      <option value="telegram">Telegram</option>
                      <option value="whatsapp">WhatsApp</option>
                      <option value="viber">Viber</option>
                      <option value="instagram">Instagram</option>
                      <option value="vk">ВКонтакте</option>
                      <option value="max">MAX</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs text-gray-500 mb-1"
                      x-text="({telegram:'Юзернейм',whatsapp:'Номер',viber:'Номер',instagram:'Юзернейм',vk:'ID/username',max:'Юзернейм'})[item.messenger]||'Значение'"></label>
                    <input type="text" x-model="item.v"
                      class="w-full bg-gray-900 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                      :placeholder="({telegram:'username',whatsapp:'79991234567',viber:'79991234567',instagram:'username',vk:'id123',max:'username'})[item.messenger]||''">
                  </div>
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Подпись (необязательно)</label>
                  <input type="text" x-model="item.t"
                    class="w-full bg-gray-900 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                    placeholder="Написать в Telegram">
                </div>
              </div>
              <button type="button" @click="opts.items.splice(idx,1)" class="text-gray-600 hover:text-red-400 p-1 transition-colors shrink-0 mt-5" title="Удалить">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </template>
          <button type="button" @click="if(!opts.items)opts.items=[];opts.items.push({messenger:'telegram',v:'',t:'',i:null})"
            class="w-full border border-dashed border-gray-600 hover:border-blue-500 text-gray-400 hover:text-blue-400 rounded-lg px-3 py-2 text-sm transition-colors flex items-center justify-center gap-2">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Добавить мессенджер
          </button>
        </div>
      </template>

      <!-- socialnetworks -->
      <template x-if="typeName(form.block_type_id) === 'socialnetworks'">
        <div class="space-y-3">
          <template x-for="(item, idx) in (opts.items||[])" :key="idx">
            <div class="flex items-center gap-2 bg-gray-800 border border-gray-700 rounded-lg p-3">
              <div class="flex-1 grid grid-cols-[120px_1fr] gap-2">
                <select x-model="item.type" class="bg-gray-900 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm">
                  <option value="instagram">Instagram</option>
                  <option value="vk">ВКонтакте</option>
                  <option value="telegram">Telegram</option>
                  <option value="youtube">YouTube</option>
                  <option value="facebook">Facebook</option>
                  <option value="twitter">Twitter / X</option>
                  <option value="tiktok">TikTok</option>
                </select>
                <input type="text" x-model="item.link"
                  class="bg-gray-900 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                  :placeholder="({instagram:'https://instagram.com/…',vk:'https://vk.com/…',telegram:'https://t.me/…',youtube:'https://youtube.com/…',facebook:'https://facebook.com/…',twitter:'https://x.com/…',tiktok:'https://tiktok.com/@…'})[item.type]||'https://…'">
              </div>
              <button type="button" @click="opts.items.splice(idx,1)" class="text-gray-600 hover:text-red-400 p-1 transition-colors shrink-0" title="Удалить">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </template>
          <button type="button" @click="if(!opts.items)opts.items=[];opts.items.push({type:'instagram',link:''})"
            class="w-full border border-dashed border-gray-600 hover:border-blue-500 text-gray-400 hover:text-blue-400 rounded-lg px-3 py-2 text-sm transition-colors flex items-center justify-center gap-2">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Добавить соцсеть
          </button>
        </div>
      </template>

      <!-- music -->
      <template x-if="typeName(form.block_type_id) === 'music'">
        <div class="space-y-3">
          <template x-for="(item, idx) in (opts.items||[])" :key="idx">
            <div class="flex items-start gap-2 bg-gray-800 border border-gray-700 rounded-lg p-3">
              <div class="flex-1 space-y-2">
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Источник</label>
                  <select x-model="item.type" class="w-full bg-gray-900 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm">
                    <option value="file">Загрузить файл</option>
                    <option value="spotify">Spotify</option>
                    <option value="yandex">Яндекс Музыка</option>
                    <option value="apple">Apple Music</option>
                  </select>
                </div>
                <template x-if="item.type==='file'">
                  <div class="space-y-2">
                    <div>
                      <label class="block text-xs text-gray-500 mb-1">Название</label>
                      <input type="text" x-model="item.title"
                        class="w-full bg-gray-900 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                        placeholder="Название трека">
                    </div>
                    <div x-show="item.value" class="flex items-center gap-2 text-xs text-gray-400 bg-gray-900 rounded-lg px-3 py-2">
                      <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"/></svg>
                      <span class="truncate" x-text="item.value"></span>
                    </div>
                    <label class="flex items-center justify-center gap-2 w-full cursor-pointer bg-gray-800 border border-dashed border-gray-600 hover:border-blue-500 text-gray-400 hover:text-blue-400 rounded-lg px-3 py-2.5 text-sm transition-colors">
                      <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                      <span x-text="item.value ? 'Заменить файл' : 'Загрузить аудио'"></span>
                      <input type="file" class="hidden" accept="audio/*"
                        @change="async function(e){
                          const f=e.target.files[0]; if(!f) return;
                          const fd=new FormData(); fd.append('file',f);
                          const r=await fetch('/admin/upload.php',{method:'POST',body:fd});
                          const d=await r.json();
                          if(d.url){item.value=d.url; if(!item.title)item.title=f.name.replace(/\.[^.]+$/,'');}
                          e.target.value='';
                        }($event)">
                    </label>
                  </div>
                </template>
                <template x-if="item.type!=='file'">
                  <div>
                    <label class="block text-xs text-gray-500 mb-1">Ссылка</label>
                    <input type="text" x-model="item.value"
                      class="w-full bg-gray-900 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                      :placeholder="({spotify:'https://open.spotify.com/track/…',yandex:'https://music.yandex.ru/album/…/track/…',apple:'https://music.apple.com/…'})[item.type]||'https://…'">
                  </div>
                </template>
              </div>
              <button type="button" @click="opts.items.splice(idx,1)" class="text-gray-600 hover:text-red-400 p-1 transition-colors shrink-0 mt-5" title="Удалить">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </template>
          <button type="button" @click="if(!opts.items)opts.items=[];opts.items.push({type:'file',value:'',title:''})"
            class="w-full border border-dashed border-gray-600 hover:border-blue-500 text-gray-400 hover:text-blue-400 rounded-lg px-3 py-2 text-sm transition-colors flex items-center justify-center gap-2">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Добавить трек
          </button>
        </div>
      </template>

      <!-- pricing -->
      <template x-if="typeName(form.block_type_id) === 'pricing'">
        <div class="space-y-3">
          <template x-for="(item, idx) in (opts.fields||[])" :key="idx">
            <div class="flex items-center gap-2 bg-gray-800 border border-gray-700 rounded-lg p-3">
              <div class="flex-1 grid grid-cols-[1fr_100px] gap-2">
                <input type="text" x-model="item.title"
                  class="bg-gray-900 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                  placeholder="Название">
                <input type="number" x-model.number="item.price"
                  class="bg-gray-900 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                  placeholder="Цена">
              </div>
              <button type="button" @click="opts.fields.splice(idx,1)" class="text-gray-600 hover:text-red-400 p-1 transition-colors shrink-0" title="Удалить">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </template>
          <button type="button" @click="if(!opts.fields)opts.fields=[];opts.fields.push({title:'',price:0})"
            class="w-full border border-dashed border-gray-600 hover:border-blue-500 text-gray-400 hover:text-blue-400 rounded-lg px-3 py-2 text-sm transition-colors flex items-center justify-center gap-2">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Добавить позицию
          </button>
        </div>
      </template>

      <!-- collapse (FAQ) -->
      <template x-if="typeName(form.block_type_id) === 'collapse'">
        <div class="space-y-3">
          <template x-for="(item, idx) in (opts.fields||[])" :key="idx">
            <div class="flex items-start gap-2 bg-gray-800 border border-gray-700 rounded-lg p-3">
              <div class="flex-1 space-y-2">
                <input type="text" x-model="item.title"
                  class="w-full bg-gray-900 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                  placeholder="Вопрос">
                <textarea x-model="item.text" rows="2"
                  class="w-full bg-gray-900 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 resize-none"
                  placeholder="Ответ"></textarea>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" x-model="item.opened" class="rounded accent-blue-500">
                  <span class="text-xs text-gray-500">Раскрыт по умолчанию</span>
                </label>
              </div>
              <button type="button" @click="opts.fields.splice(idx,1)" class="text-gray-600 hover:text-red-400 p-1 transition-colors shrink-0" title="Удалить">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </template>
          <button type="button" @click="if(!opts.fields)opts.fields=[];opts.fields.push({title:'',text:'',opened:false})"
            class="w-full border border-dashed border-gray-600 hover:border-blue-500 text-gray-400 hover:text-blue-400 rounded-lg px-3 py-2 text-sm transition-colors flex items-center justify-center gap-2">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Добавить вопрос
          </button>
        </div>
      </template>

      <!-- plans -->
      <template x-if="typeName(form.block_type_id) === 'plans'">
        <div class="space-y-3">
          <template x-for="(item, idx) in (opts.fields||[])" :key="idx">
            <div class="flex items-start gap-2 bg-gray-800 border border-gray-700 rounded-lg p-3">
              <div class="flex-1 space-y-2">
                <div class="grid grid-cols-[1fr_100px] gap-2">
                  <input type="text" x-model="item.title"
                    class="bg-gray-900 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                    placeholder="Название тарифа">
                  <input type="number" x-model.number="item.price"
                    class="bg-gray-900 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                    placeholder="Цена">
                </div>
                <textarea x-model="item.description" rows="2"
                  class="w-full bg-gray-900 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 resize-none"
                  placeholder="Описание (необязательно)"></textarea>
              </div>
              <button type="button" @click="opts.fields.splice(idx,1)" class="text-gray-600 hover:text-red-400 p-1 transition-colors shrink-0" title="Удалить">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </template>
          <button type="button" @click="if(!opts.fields)opts.fields=[];opts.fields.push({title:'',price:0,description:''})"
            class="w-full border border-dashed border-gray-600 hover:border-blue-500 text-gray-400 hover:text-blue-400 rounded-lg px-3 py-2 text-sm transition-colors flex items-center justify-center gap-2">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Добавить тариф
          </button>
        </div>
      </template>

      <!-- JSON fallback for complex types -->
      <template x-if="['avatar','digitals-product'].includes(typeName(form.block_type_id))">
        <div>
          <label class="block text-xs text-gray-500 mb-1">Options (JSON)</label>
          <textarea x-model="optsJson" rows="9"
            class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-xs font-mono focus:outline-none focus:border-blue-500"
            :placeholder="jsonPlaceholder(typeName(form.block_type_id))"></textarea>
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

    <!-- Modules tab (form blocks only) -->
    <div x-show="blockModalTab==='modules'" class="overflow-y-auto sb p-5 space-y-4 flex-1">
      <template x-if="!modulesUI.emailLoaded || !moduleEmail.domain">
        <div class="text-center py-8 text-gray-600 text-sm">
          <p>Email-модуль не настроен.</p>
          <a href="/admin/?tab=modules" class="text-blue-400 hover:underline text-xs mt-1 inline-block">Настроить в Модулях</a>
        </div>
      </template>
      <template x-if="modulesUI.emailLoaded && moduleEmail.domain">
        <div class="space-y-4">
          <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Рассылки
          </div>
          <div x-show="mailingsLoading" class="flex justify-center py-4">
            <svg class="animate-spin w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          </div>
          <div x-show="!mailingsLoading && mailings.length===0" class="text-sm text-gray-600">
            Рассылок нет. <a href="/admin/?tab=mailings" class="text-blue-400 hover:underline">Создать рассылку</a>
          </div>
          <template x-for="ml in mailings" :key="ml.id">
            <div class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 space-y-2">
              <div class="text-sm font-medium text-white" x-text="ml.subject"></div>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" class="accent-blue-500"
                  :checked="(opts.mailings||[]).some(m=>m.id===ml.id&&m.on_submit)"
                  @change="toggleFormMailing(ml.id,'on_submit',$event.target.checked)">
                <span class="text-sm text-gray-400">Отправить после заполнения формы</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer" x-show="opts.product_id">
                <input type="checkbox" class="accent-blue-500"
                  :checked="(opts.mailings||[]).some(m=>m.id===ml.id&&m.on_payment)"
                  @change="toggleFormMailing(ml.id,'on_payment',$event.target.checked)">
                <span class="text-sm text-gray-400">Отправить после оплаты</span>
              </label>
            </div>
          </template>
        </div>
      </template>
    </div>

    <div class="px-5 py-4 border-t border-gray-800 flex justify-end gap-3 shrink-0">
      <button @click="blockModal=false" class="text-gray-400 hover:text-white px-4 py-2 text-sm transition-colors">Отмена</button>
      <button @click="saveBlock()" :disabled="saving"
        class="bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors"
        x-text="saving?'Сохраняю…':'Сохранить'"></button>
    </div>
  </div>
</div>

<!-- ══ Section modal ══ -->
<div x-show="sectionModal" x-cloak @click.self="sectionModal=false"
  class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
  <div class="bg-gray-900 border border-gray-700 rounded-xl w-full max-w-sm shadow-2xl p-5">
    <h3 class="font-semibold mb-4">Новая секция</h3>
    <input type="text" x-model="newSectionTitle" @keydown.enter="saveSection()"
      class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500 mb-4"
      placeholder="Название секции">
    <div class="flex justify-end gap-3">
      <button @click="sectionModal=false" class="text-gray-400 hover:text-white px-4 py-2 text-sm">Отмена</button>
      <button @click="saveSection()" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg text-sm">Создать</button>
    </div>
  </div>
</div>

<!-- ══ Section settings modal ══ -->
<div x-show="sectionSettingsModal" x-cloak @click.self="sectionSettingsModal=false"
  class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
  <div class="bg-gray-900 border border-gray-700 rounded-xl w-full max-w-sm shadow-2xl p-5 space-y-4">
    <h3 class="font-semibold" x-text="'Секция: ' + (sectionSettingsTarget?.title||'')"></h3>

    <div class="space-y-3">
      <div>
        <label class="text-xs text-gray-500 block mb-1">Фон</label>
        <div class="flex gap-2 items-center">
          <input type="color" x-model="sectionSettingsForm.bg_color"
            class="w-10 h-8 rounded cursor-pointer bg-transparent border border-gray-700 p-0.5">
          <input type="text" x-model="sectionSettingsForm.bg_color" placeholder="transparent / #rrggbb / rgba()"
            class="flex-1 bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-blue-500">
          <button @click="sectionSettingsForm.bg_color=''" class="text-gray-600 hover:text-red-400 text-lg leading-none">×</button>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs text-gray-500 block mb-1">Отступ сверху (px)</label>
          <input type="number" x-model.number="sectionSettingsForm.padding_top" min="0" max="200"
            class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-blue-500">
        </div>
        <div>
          <label class="text-xs text-gray-500 block mb-1">Отступ снизу (px)</label>
          <input type="number" x-model.number="sectionSettingsForm.padding_bottom" min="0" max="200"
            class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-blue-500">
        </div>
      </div>
    </div>

    <div class="flex justify-end gap-3 pt-1">
      <button @click="sectionSettingsModal=false" class="text-gray-400 hover:text-white px-4 py-2 text-sm">Отмена</button>
      <button @click="saveSectionSettings()"
        class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg text-sm">Сохранить</button>
    </div>
  </div>
</div>

<!-- ══ Submissions modal ══ -->
<div x-show="subsModal" x-cloak @click.self="subsModal=false"
  class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
  <div class="bg-gray-900 border border-gray-700 rounded-xl w-full max-w-2xl shadow-2xl flex flex-col max-h-[85vh]">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-800 shrink-0">
      <div>
        <h3 class="font-semibold">Заявки</h3>
        <p class="text-xs text-gray-500 mt-0.5" x-text="submissions.length + ' шт.'"></p>
      </div>
      <button @click="subsModal=false" class="text-gray-500 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>
    <div class="overflow-y-auto sb p-5 flex-1">
      <div x-show="subsLoading" class="flex justify-center py-10 text-gray-500">
        <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
      </div>
      <div x-show="!subsLoading && submissions.length===0" class="text-center py-10 text-gray-500 text-sm">Заявок пока нет</div>
      <div x-show="!subsLoading && submissions.length>0" class="space-y-3">
        <template x-for="s in submissions" :key="s.id">
          <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 flex items-start justify-between gap-4">
            <div class="space-y-1.5 flex-1 min-w-0">
              <div class="text-xs text-gray-500" x-text="new Date(s.created_at).toLocaleString('ru')"></div>
              <template x-for="[k,v] in Object.entries(s.data)" :key="k">
                <div class="flex gap-2 text-sm">
                  <span class="text-gray-500 shrink-0" x-text="k + ':'"></span>
                  <span class="text-gray-200 truncate" x-text="v||'—'"></span>
                </div>
              </template>
            </div>
            <button @click="deleteSubmission(s)" class="text-gray-600 hover:text-red-400 p-1 rounded hover:bg-gray-700 transition-colors shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
        </template>
      </div>
    </div>
  </div>
</div>

<script src="/assets/editor.js"></script>
<script src="/assets/zero-model.js?v=1"></script>
<script src="/assets/zero-editor.js?v=1"></script>
<script>
function txtSize(s){const m={h1:'50px',h2:'30px',h3:'24px',sm:'14px',md:'17px',lg:'20px'};return m[s]||'17px';}
function txtLineHeight(s){const m={h1:'1.15',h2:'1.25',h3:'1.4',sm:'1.45',md:'1.45',lg:'1.45'};return m[s]||'1.45';}
function txtWeight(s,bold){if(bold!==undefined&&bold!==null)return bold?'700':'400';return['h1','h2'].includes(s)?'700':'400';}
function loadGoogleFont(f){if(!f)return;const id='gfont-'+f.replace(/\s+/g,'-');if(!document.getElementById(id)){const l=document.createElement('link');l.id=id;l.rel='stylesheet';l.href='https://fonts.googleapis.com/css2?family='+encodeURIComponent(f)+':wght@400;600;700&display=swap';document.head.appendChild(l);}}
function app(){return{
  blocks:[],sections:[],folders:[],
  loading:true,
  currentPage:null,currentSection:null,
  blockModal:false,blockModalTab:'content',sectionModal:false,pageModal:false,
  editId:null,editPageId:null,
  saving:false,formError:'',pageFormError:'',
  newSectionTitle:'',
  form:{block_type_id:1,section_id:'',is_visible:true,anchor:''},
  opts:{},optsJson:'{}',
  activeTab:'pages',
  design:{screen:'#ffffff',text_color:'#343a40',link_bg:'#ffffff',link_color:'#343a40',link_radius:7,link_border_width:0,link_border_color:'#ffffff',link_shadow:'none',link_shadow_color:'rgba(0,0,0,.15)',page_font:''},
  designSaving:false,
  settingsLoaded:false,
  siteSettings:{head_code:'',seo_title:'',seo_description:'',favicon_url:''},
  faviconUploading:false,
  siteSettingsSaving:false,
  sectionSettingsModal:false,
  sectionSettingsTarget:null,
  sectionSettingsForm:{bg_color:'',padding_top:0,padding_bottom:0},
  siteSettingsLoaded:false,
  _headCm:null,
  subsModal:false,subsLoading:false,submissions:[],_subsBlockId:null,
  allSubmissions:[],allSubsLoading:false,
  payments:[],paymentsLoading:false,
  paymentModal:false,paymentSaving:false,paymentError:'',
  paymentForm:{id:'',provider:'getplatinum',label:'',credentials:{},is_active:false},
  orders:[],ordersLoading:false,
  products:[],productsLoading:false,productPages:[],
  productModal:false,productSaving:false,productError:'',productImageUploading:false,
  productForm:{id:'',title:'',priceRub:0,image_url:'',success_page_id:'',is_active:true},
  formProducts:[],
  moduleEmail:{provider:'mail',domain:'',sender:'',password:''},
  modulesUI:{emailOpen:false,emailLoading:false,emailSaving:false,emailSaved:false,emailShowPass:false,emailLoaded:false,emailError:''},
  mailings:[],mailingsLoading:false,
  mailingModal:false,mailingSaving:false,mailingError:'',
  mailingForm:{id:'',subject:'',body:'',template:'plain',isHtml:false},_mailingTextBackup:'',
  mailingTemplates:[
    {id:'plain',label:'Простой',desc:'Только текст'},
    {id:'minimal',label:'Минимальный',desc:'С заголовком'},
    {id:'card',label:'Карточка',desc:'В рамке'},
  ],
  pageForm:{title:'',slug:'',is_main:false,slugEdited:false,folder_id:null},

  _sortable:null,
  previewLoading:false,
  picturesUploading:false,
  _previewSortable:null,
  _previewListenerAttached:false,

  blockTypes:BT_LIST.map(id=>({id,label:LABELS[id]||TYPE_MAP[id],icon:ICONS[TYPE_MAP[id]]||''})),

  get pageBlocks(){return this.blocks.filter(b=>b.page_id===this.currentPage?.id);},
  get pageSections(){return this.sections.filter(s=>s.page_id===this.currentPage?.id);},
  get visibleBlocks(){
    const bs=this.pageBlocks;
    return this.currentSection===null?bs:bs.filter(b=>b.section_id===this.currentSection);
  },

  async init(){
    await this.loadSettings();
    this.loading=false;
    this.$watch('activeTab',tab=>{
      if(tab==='settings'){
        this.loadSiteSettings().then(()=>this.$nextTick(()=>this._initHeadCm()));
      }
      const u=new URL(location.href);
      if(tab==='pages'){u.searchParams.delete('tab');if(this.currentPage)u.searchParams.set('page_id',this.currentPage.id);else u.searchParams.delete('page_id');}
      else{u.searchParams.set('tab',tab);u.searchParams.delete('page_id');}
      history.replaceState(null,'',u.pathname+u.search);
    });
    const params=new URLSearchParams(location.search);
    const initTab=params.get('tab');
    this.loadModuleEmail();
    if(initTab&&['templates','design','settings','submissions','payments','products','modules','mailings'].includes(initTab)){
      this.activeTab=initTab;
      if(initTab==='submissions')this.loadAllSubmissions();
      if(initTab==='payments')this.loadPayments();
      if(initTab==='products')this.loadProducts();
      if(initTab==='mailings')this.loadMailings();
    }
    const initPageId=params.get('page_id');
    if(initPageId){
      const d=await fetch('/admin/api.php?action=pages').then(r=>r.json());
      const pg=(d.pages||[]).find(p=>p.id===initPageId);
      if(pg)await this.selectPage(pg);
    }
  },
  async loadBlocks(){
    if(!this.currentPage)return;
    const d=await(await fetch('/admin/api.php?action=blocks&page_id='+this.currentPage.id)).json();
    this.blocks=d.blocks||[];
  },
  async loadSections(){
    if(!this.currentPage)return;
    const d=await(await fetch('/admin/api.php?action=sections&page_id='+this.currentPage.id)).json();
    this.sections=d.sections||[];
  },
  async selectPage(pg){
    this.activeTab='pages';
    this.currentPage=pg;
    this.currentSection=null;
    this.blocks=[];
    this.sections=[];
    history.replaceState(null,'','/admin/?page_id='+pg.id);
    this._initPreviewListeners();
    await this.loadPagePreview();
  },

  _initPreviewListeners(){
    if(this._previewListenerAttached)return;
    this._previewListenerAttached=true;
    document.getElementById('pagePreviewArea')?.addEventListener('click', e=>{
      const btn=e.target.closest('[data-action]');
      if(!btn)return;
      e.stopPropagation();
      const action=btn.dataset.action;
      const blockId=btn.dataset.blockId;
      if(action==='addBlock'){this.openAdd();return;}
      const block=this.blocks.find(b=>b.id===blockId);
      if(!block)return;
      if(action==='editBlock') this.openEdit(block);
      else if(action==='deleteBlock') this.removeBlock(block);
      else if(action==='toggleVis') this.toggleVis(block);
    });
  },

  async loadPagePreview(){
    if(!this.currentPage)return;
    this.previewLoading=true;
    const [fragRes, bd, sd]=await Promise.all([
      fetch('/admin/render-fragment.php?page_id='+this.currentPage.id).then(r=>r.json()),
      fetch('/admin/api.php?action=blocks&page_id='+this.currentPage.id).then(r=>r.json()),
      fetch('/admin/api.php?action=sections&page_id='+this.currentPage.id).then(r=>r.json()),
    ]);
    this.blocks=bd.blocks||[];
    this.sections=sd.sections||[];
    this.previewLoading=false;
    const area=document.getElementById('pagePreviewArea');
    if(area&&fragRes.ok){
      area.innerHTML=fragRes.html;
      // Run any <script> tags injected (e.g. timer)
      area.querySelectorAll('script').forEach(old=>{
        const s=document.createElement('script');
        s.textContent=old.textContent;
        old.replaceWith(s);
      });
      this._initSortablePreview();
    }
  },

  _initSortablePreview(){
    // sortable for ungrouped blocks
    const root=document.getElementById('blocksSortable');
    if(!root)return;
    if(this._previewSortable){this._previewSortable.destroy();this._previewSortable=null;}
    const directWraps=[...root.children].filter(el=>el.classList.contains('admin-block-wrap'));
    if(directWraps.length){
      this._previewSortable=Sortable.create(root,{
        handle:'.drag-handle',animation:150,ghostClass:'opacity-50',
        filter:'.section-group',
        onEnd:async()=>{
          const ids=[...root.querySelectorAll(':scope>.admin-block-wrap')].map(el=>el.dataset.blockId);
          await fetch('/admin/api.php?action=reorder',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ids})});
          await this.loadPagePreview();
        },
      });
    }
    // sortable per section
    root.querySelectorAll('.section-group-sortable').forEach(sec=>{
      Sortable.create(sec,{
        handle:'.drag-handle',animation:150,ghostClass:'opacity-50',
        onEnd:async()=>{
          const ids=[...sec.querySelectorAll('.admin-block-wrap')].map(el=>el.dataset.blockId);
          await fetch('/admin/api.php?action=reorder',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ids})});
          await this.loadPagePreview();
        },
      });
    });
  },
  initSortable(el){
    el=el||document.getElementById('blocksTbody');
    if(!el)return;
    if(this._sortable){this._sortable.destroy();this._sortable=null;}
    this._sortable=Sortable.create(el,{
      handle:'.drag-handle',animation:150,ghostClass:'opacity-40',
      onEnd:async()=>{
        const ids=[...el.querySelectorAll('tr[data-block-id]')].map(tr=>tr.dataset.blockId);
        await fetch('/admin/api.php?action=reorder',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ids})});
        await this.loadBlocks();
        await this.$nextTick();
        this.initSortable(el);
      },
    });
  },

  typeName(id){return TYPE_MAP[parseInt(id)]||'';},
  getLabel(id){return LABELS[parseInt(id)]||TYPE_MAP[parseInt(id)]||'';},
  getIcon(name){return ICONS[name]||'';},
  blocksInSection(id){return this.pageBlocks.filter(b=>b.section_id===id).length;},
  jsonPlaceholder(t){return PLACEHOLDERS[t]||'{}';},
  loadGoogleFont(f){loadGoogleFont(f);},
  txtSize(s){return txtSize(s);},
  preview(b){
    const o=b.options||{};
    if(o.text)return o.text.slice(0,80);
    if(o.title)return o.title;
    if(o.url)return o.url;
    if(o.address)return o.address;
    if(o.html)return o.html.slice(0,60);
    if(Array.isArray(o.items))return o.items.length+' элем.';
    if(Array.isArray(o.fields))return o.fields.length+' поля';
    return'—';
  },

  // Page
  async openAddPage(){
    this.editPageId=null;this.pageForm={title:'',slug:'',is_main:false,slugEdited:false,folder_id:null};this.pageFormError='';
    // load folders for the dropdown
    const d=await(await fetch('/admin/api.php?action=folders')).json();
    this.folders=(d.folders||[]);
    this.pageModal=true;
  },
  autoSlug(){if(!this.pageForm.slugEdited)this.pageForm.slug=slugify(this.pageForm.title);},
  async savePage(){
    this.pageFormError='';
    const{title,slug,is_main,folder_id}=this.pageForm;
    if(!title.trim()){this.pageFormError='Введите название';return;}
    if(this.editPageId){
      const d=await(await fetch('/admin/api.php?action=updatePage',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:this.editPageId,title:title.trim(),slug,folder_id:folder_id||null})})).json();
      if(d.ok){
        if(is_main){
          await fetch('/admin/api.php?action=setMain',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:this.editPageId})});
        }
        if(this.currentPage?.id===this.editPageId){this.currentPage={...this.currentPage,title:title.trim(),slug};}
        this.pageModal=false;
        window.dispatchEvent(new CustomEvent('sidebar-reload'));
      }
    }else{
      const d=await(await fetch('/admin/api.php?action=addPage',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({title:title.trim(),slug,folder_id:folder_id||null})})).json();
      if(d.ok){
        if(is_main){
          await fetch('/admin/api.php?action=setMain',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:d.page.id})});
        }
        this.pageModal=false;
        window.dispatchEvent(new CustomEvent('sidebar-reload'));
        await this.selectPage(d.page);
      }
    }
  },

  // Folders
  async addFolder(){
    const title=prompt('Название папки:','Папка');
    if(!title||!title.trim())return;
    const d=await(await fetch('/admin/api.php?action=addFolder',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({title:title.trim()})})).json();
    if(d.ok){
      this.folders.push(d.folder);
      window.dispatchEvent(new CustomEvent('sidebar-reload'));
    }
  },

  // Block
  async uploadPicturesImages(e){
    this.picturesUploading=true;
    if(!this.opts.list)this.opts.list=[];
    for(const file of e.target.files){
      const fd=new FormData();fd.append('file',file);
      const r=await fetch('/admin/upload.php',{method:'POST',body:fd}).then(r=>r.json());
      if(r.url)this.opts.list.push({p:{filename:r.url},s:'',t:'',link:{title:'',type:'link',value:''}});
    }
    e.target.value='';
    this.picturesUploading=false;
  },
  selectType(id){this.form.block_type_id=id;var n=TYPE_MAP[id];this.opts=n==='messenger'?{items:[{messenger:'telegram',v:'',t:'',i:null}]}:n==='socialnetworks'?{items:[{type:'instagram',link:''}]}:n==='music'?{items:[{type:'file',value:'',title:''}]}:n==='pricing'?{fields:[{title:'',price:0}],currency:'₽'}:n==='collapse'?{fields:[{title:'',text:'',opened:false}]}:n==='plans'?{fields:[{title:'',price:0,description:''}]}:{};this.optsJson=PLACEHOLDERS[n]||'{}';},
  openAdd(){
    this.editId=null;
    this.form={block_type_id:1,section_id:this.currentSection||'',is_visible:true,anchor:''};
    this.opts={};this.optsJson='{}';this.formError='';this.blockModalTab='content';this.blockModal=true;
    this.initFormFieldsSortable();
  },
  openEdit(b){
    this.editId=b.id;
    this.form={block_type_id:b.block_type_id,section_id:b.section_id||'',is_visible:!!b.is_visible,anchor:b.anchor||''};
    const o=b.options||{};this.opts={...o};this.optsJson=JSON.stringify(o,null,2);
    this.formError='';this.blockModalTab='content';this.blockModal=true;
    this.initFormFieldsSortable();
  },
  getOptions(){
    const t=this.typeName(this.form.block_type_id);
    if(JSON_TYPES.includes(t)){try{return JSON.parse(this.optsJson);}catch{this.formError='Невалидный JSON';return null;}}
    return{...this.opts};
  },
  async saveBlock(){
    this.formError='';
    const options=this.getOptions();
    if(options===null)return;
    this.saving=true;
    const id=parseInt(this.form.block_type_id);
    const payload={
      id:this.editId,block_type_id:id,block_type_name:TYPE_MAP[id],
      page_id:this.currentPage?.id||null,
      section_id:this.form.section_id||null,
      is_visible:this.form.is_visible,anchor:this.form.anchor||null,options,
    };
    try{
      const d=await(await fetch('/admin/api.php?action=save',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})).json();
      if(d.ok){this.blockModal=false;await this.loadPagePreview();}
      else this.formError=d.error||'Ошибка';
    }catch{this.formError='Ошибка сети';}
    finally{this.saving=false;}
  },
  async removeBlock(b){
    if(!confirm(`Удалить блок «${LABELS[b.block_type_id]||b.block_type_name}»?`))return;
    const d=await(await fetch('/admin/api.php?action=delete',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:b.id})})).json();
    if(d.ok){this.blocks=this.blocks.filter(x=>x.id!==b.id);await this.loadPagePreview();}
  },
  async toggleVis(b){
    const nv=!b.is_visible;
    const d=await(await fetch('/admin/api.php?action=toggle',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:b.id,is_visible:nv})})).json();
    if(d.ok){b.is_visible=nv;await this.loadPagePreview();}
  },

  // Section
  openAddSection(){this.newSectionTitle='';this.sectionModal=true;},
  async saveSection(){
    const title=this.newSectionTitle.trim();if(!title)return;
    const d=await(await fetch('/admin/api.php?action=addSection',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({title,page_id:this.currentPage?.id})})).json();
    if(d.ok){this.sections.push(d.section);this.sectionModal=false;}
  },
  async deleteSection(id){
    if(!confirm('Удалить секцию? Блоки останутся без секции.'))return;
    const d=await(await fetch('/admin/api.php?action=deleteSection',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})})).json();
    if(d.ok){
      this.sections=this.sections.filter(s=>s.id!==id);
      if(this.currentSection===id)this.currentSection=null;
      this.blocks.forEach(b=>{if(b.section_id===id)b.section_id=null;});
    }
  },
  openSectionSettings(sec){
    this.sectionSettingsTarget=sec;
    const o=sec.options||{};
    this.sectionSettingsForm={
      bg_color:o.bg_color||'',
      padding_top:o.padding_top||0,
      padding_bottom:o.padding_bottom||0,
    };
    this.sectionSettingsModal=true;
  },
  async saveSectionSettings(){
    const id=this.sectionSettingsTarget?.id;
    if(!id)return;
    const options={...this.sectionSettingsForm};
    const d=await(await fetch('/admin/api.php?action=updateSection',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,options})})).json();
    if(d.ok){
      const sec=this.sections.find(s=>s.id===id);
      if(sec)sec.options=options;
      this.sectionSettingsModal=false;
    }
  },
  async loadSettings(){
    if(this.settingsLoaded)return;
    try{
      const d=await(await fetch('/admin/api.php?action=getSettings')).json();
      if(d.settings)Object.assign(this.design,d.settings);
      this.settingsLoaded=true;
    }catch(e){}
  },
  async saveDesign(){
    this.designSaving=true;
    try{
      await fetch('/admin/api.php?action=saveSettings',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(this.design)});
    }finally{this.designSaving=false;}
  },

  async loadAllSubmissions(){
    this.allSubsLoading=true;
    try{
      const d=await(await fetch('/admin/api.php?action=submissions')).json();
      this.allSubmissions=d.submissions||[];
    }finally{this.allSubsLoading=false;}
  },
  async loadPayments(){
    this.paymentsLoading=true;
    try{
      const d=await(await fetch('/admin/api.php?action=payments')).json();
      this.payments=d.payments||[];
      if(this.payments.length>0)this.loadOrders();
    }finally{this.paymentsLoading=false;}
  },
  openAddPayment(provider='getplatinum'){
    this.paymentForm={id:'',provider:'getplatinum',label:'',credentials:{},is_active:true};
    this.paymentError='';this.paymentModal=true;
  },
  editPayment(pm){
    this.paymentForm={id:pm.id,provider:'getplatinum',label:pm.label,credentials:{...pm.credentials},is_active:pm.is_active};
    this.paymentError='';this.paymentModal=true;
  },
  async savePayment(){
    this.paymentForm.provider='getplatinum';
    if(!this.paymentForm.credentials.api_key){this.paymentError='Укажите API-ключ';return;}
    this.paymentSaving=true;this.paymentError='';
    try{
      const d=await(await fetch('/admin/api.php?action=savePayment',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(this.paymentForm)})).json();
      if(d.ok){await this.loadPayments();this.paymentModal=false;}
      else this.paymentError=d.error||'Ошибка';
    }catch{this.paymentError='Ошибка сети';}
    finally{this.paymentSaving=false;}
  },
  async togglePayment(pm){
    const d=await(await fetch('/admin/api.php?action=togglePayment',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:pm.id})})).json();
    if(d.ok)pm.is_active=d.is_active;
  },
  async deletePayment(pm){
    if(!confirm('Удалить подключение GetPlatinum?'))return;
    const d=await(await fetch('/admin/api.php?action=deletePayment',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:pm.id})})).json();
    if(d.ok)this.payments=this.payments.filter(x=>x.id!==pm.id);
  },
  async loadOrders(){
    this.ordersLoading=true;
    try{const d=await(await fetch('/admin/api.php?action=orders')).json();this.orders=d.orders||[];}
    finally{this.ordersLoading=false;}
  },
  async deleteAllSub(s){
    if(!confirm('Удалить заявку?'))return;
    const d=await(await fetch('/admin/api.php?action=deleteSubmission',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:s.id})})).json();
    if(d.ok)this.allSubmissions=this.allSubmissions.filter(x=>x.id!==s.id);
  },

  async openSubmissions(b){
    this._subsBlockId=b.id;this.submissions=[];this.subsLoading=true;this.subsModal=true;
    try{
      const d=await(await fetch('/admin/api.php?action=submissions&block_id='+b.id)).json();
      this.submissions=d.submissions||[];
    }finally{this.subsLoading=false;}
  },
  async deleteSubmission(s){
    if(!confirm('Удалить заявку?'))return;
    const d=await(await fetch('/admin/api.php?action=deleteSubmission',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:s.id})})).json();
    if(d.ok)this.submissions=this.submissions.filter(x=>x.id!==s.id);
  },

  async loadSiteSettings(){
    if(this.siteSettingsLoaded)return;
    try{
      const d=await(await fetch('/admin/api.php?action=getSiteSettings')).json();
      if(d.settings)Object.assign(this.siteSettings,d.settings);
      this.siteSettingsLoaded=true;
      if(this._headCm)this._headCm.setValue(this.siteSettings.head_code||'');
    }catch(e){}
  },
  async saveSiteSettings(){
    if(this._headCm)this.siteSettings.head_code=this._headCm.getValue();
    this.siteSettingsSaving=true;
    try{
      await fetch('/admin/api.php?action=saveSiteSettings',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(this.siteSettings)});
    }finally{this.siteSettingsSaving=false;}
  },
  async loadFormProducts(){
    try{
      const d=await(await fetch('/admin/api.php?action=products')).json();
      this.formProducts=(d.products||[]).filter(p=>p.is_active);
    }catch{}
  },

  // Products
  async loadProducts(){
    this.productsLoading=true;
    try{
      const [pd,pgd]=await Promise.all([
        fetch('/admin/api.php?action=products').then(r=>r.json()),
        fetch('/admin/api.php?action=pages').then(r=>r.json()),
      ]);
      this.products=pd.products||[];
      this.productPages=pgd.pages||[];
    }finally{this.productsLoading=false;}
  },
  productPageTitle(pageId){
    const pg=this.productPages.find(p=>p.id===pageId);
    return pg?pg.title:'—';
  },
  openProductModal(){
    this.productForm={id:'',title:'',priceRub:0,image_url:'',success_page_id:'',is_active:true};
    this.productError='';this.productModal=true;
  },
  editProduct(prod){
    this.productForm={id:prod.id,title:prod.title,priceRub:prod.price/100,image_url:prod.image_url||'',success_page_id:prod.success_page_id||'',is_active:prod.is_active};
    this.productError='';this.productModal=true;
  },
  async uploadProductImage(e){
    const file=e.target.files[0];if(!file)return;
    this.productImageUploading=true;
    try{
      const fd=new FormData();fd.append('file',file);
      const r=await fetch('/admin/upload.php',{method:'POST',body:fd}).then(r=>r.json());
      if(r.url)this.productForm.image_url=r.url;
      else this.productError=r.error||'Ошибка загрузки';
    }finally{this.productImageUploading=false;e.target.value='';}
  },
  async saveProduct(){
    this.productError='';
    if(!this.productForm.title.trim()){this.productError='Введите название';return;}
    if(!this.productForm.priceRub||this.productForm.priceRub<=0){this.productError='Укажите цену';return;}
    this.productSaving=true;
    try{
      const d=await(await fetch('/admin/api.php?action=saveProduct',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({
        id:this.productForm.id||undefined,
        title:this.productForm.title.trim(),
        price:Math.round(this.productForm.priceRub*100),
        currency:'RUB',
        image_url:this.productForm.image_url||null,
        success_page_id:this.productForm.success_page_id||null,
        is_active:this.productForm.is_active,
      })})).json();
      if(d.ok){this.productModal=false;await this.loadProducts();}
      else this.productError=d.error||'Ошибка';
    }catch{this.productError='Ошибка сети';}
    finally{this.productSaving=false;}
  },
  async deleteProduct(prod){
    if(!confirm(`Удалить товар «${prod.title}»?`))return;
    const d=await(await fetch('/admin/api.php?action=deleteProduct',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:prod.id})})).json();
    if(d.ok)this.products=this.products.filter(x=>x.id!==prod.id);
  },

  async loadModuleEmail(){
    if(this.modulesUI.emailLoaded)return;
    this.modulesUI.emailLoading=true;
    try{
      const d=await(await fetch('/admin/api.php?action=getModuleSettings&module=email')).json();
      if(d.settings){
        this.moduleEmail.provider=d.settings.provider||'mail';
        this.moduleEmail.domain=d.settings.domain||'';
        this.moduleEmail.sender=d.settings.sender||'';
        this.moduleEmail.password=d.settings.password||'';
      }
      this.modulesUI.emailLoaded=true;
    }catch(e){}
    this.modulesUI.emailLoading=false;
  },
  async saveModuleEmail(){
    if(!this.moduleEmail.domain||!this.moduleEmail.sender||!this.moduleEmail.password){this.modulesUI.emailError='Заполните все поля';return;}
    this.modulesUI.emailError='';
    this.modulesUI.emailSaving=true;
    this.modulesUI.emailSaved=false;
    await fetch('/admin/api.php?action=saveModuleSettings',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({module:'email',settings:this.moduleEmail})});
    this.modulesUI.emailSaving=false;
    this.modulesUI.emailSaved=true;
    setTimeout(()=>this.modulesUI.emailSaved=false,3000);
  },

  _formFieldsSortable:null,
  initFormFieldsSortable(){
    this.$nextTick(()=>{
      const el=this.$refs.formFieldsList;
      if(!el)return;
      if(this._formFieldsSortable){this._formFieldsSortable.destroy();this._formFieldsSortable=null;}
      this._formFieldsSortable=Sortable.create(el,{
        handle:'.form-field-handle',
        animation:150,
        ghostClass:'opacity-30',
        onEnd:(evt)=>{
          const arr=[...(this.opts.fields||[])];
          const [moved]=arr.splice(evt.oldIndex,1);
          arr.splice(evt.newIndex,0,moved);
          this.opts.fields=arr;
        }
      });
    });
  },
  toggleFormMailing(mailingId,trigger,checked){
    if(!this.opts.mailings)this.opts.mailings=[];
    let entry=this.opts.mailings.find(m=>m.id===mailingId);
    if(!entry){entry={id:mailingId,on_submit:false,on_payment:false};this.opts.mailings.push(entry);}
    entry[trigger]=checked;
    this.opts.mailings=this.opts.mailings.filter(m=>m.on_submit||m.on_payment);
  },
  async loadMailingsIfNeeded(){
    if(!this.mailings.length&&!this.mailingsLoading)await this.loadMailings();
  },

  // ── Mailings ──
  async loadMailings(){
    this.mailingsLoading=true;
    try{const d=await(await fetch('/admin/api.php?action=mailings')).json();this.mailings=d.mailings||[];}
    finally{this.mailingsLoading=false;}
  },
  mailingTemplateLabel(id){return({plain:'Простой',minimal:'Минимальный',card:'Карточка'})[id]||id;},
  openMailingEditor(){
    this.mailingForm={id:'',subject:'',body:'',template:'plain',isHtml:false};
    this.mailingError='';this.mailingModal=true;
  },
  editMailing(ml){
    this.mailingForm={id:ml.id,subject:ml.subject,body:ml.body,template:ml.template||'plain',isHtml:ml.template==='html'};
    this.mailingError='';this.mailingModal=true;
  },
  _mailingTplBodies:{
    plain:'Здравствуйте, {{name}}!\n\nСпасибо за покупку «{{product}}».\n\nСумма: {{price}}\n\nС уважением,\nВаша команда',
    minimal:'{{name}}, спасибо за заказ!\n\nВы приобрели: {{product}}\nСтоимость: {{price}}\n\nЕсли у вас есть вопросы — просто ответьте на это письмо.',
    card:'Уважаемый(ая) {{name}},\n\nВаш заказ подтверждён\n\nТовар: {{product}}\nСумма: {{price}}\n\nМы свяжемся с вами для уточнения деталей.\n\nСпасибо, что выбрали нас!',
  },
  _mailingWrap(id,html){
    if(id==='plain')return '<div style="font-family:Arial,sans-serif;color:#333;line-height:1.6">'+html+'</div>';
    if(id==='minimal')return '<div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto">'
      +'<div style="border-bottom:3px solid #3b82f6;padding-bottom:12px;margin-bottom:16px;font-weight:700;font-size:15px;color:#3b82f6">✉ Рассылка</div>'
      +'<div style="color:#333;line-height:1.6">'+html+'</div>'
      +'<div style="margin-top:20px;padding-top:14px;border-top:1px solid #e5e7eb;font-size:12px;color:#9ca3af">Вы получили это письмо, потому что подписаны на рассылку.</div>'
      +'</div>';
    if(id==='card')return '<div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;background:#f9fafb;padding:24px;border-radius:0">'
      +'<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px">'
      +'<div style="font-weight:700;font-size:16px;color:#111;margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid #3b82f6">Подтверждение заказа</div>'
      +'<div style="color:#333;line-height:1.6">'+html+'</div>'
      +'</div>'
      +'<div style="text-align:center;margin-top:16px;font-size:11px;color:#9ca3af">© 2026 Ваша компания</div>'
      +'</div>';
    return html;
  },
  _mailingSubstVars(text){
    const vars={'{{name}}':'Иван','{{product}}':'Онлайн-курс','{{price}}':'9 900 ₽'};
    return text.replace(/\{\{(name|product|price)\}\}/g,m=>vars[m]||m);
  },
  _mailingTextToHtml(text){
    let t=text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    return t.replace(/\n/g,'<br>');
  },
  mailingTplPreview(id){
    const body=this._mailingTplBodies[id]||'';
    return this._mailingWrap(id,this._mailingTextToHtml(this._mailingSubstVars(body)));
  },
  applyMailingTemplate(id){
    if(this.mailingForm.body&&!confirm('Заменить текст шаблоном?'))return;
    this.mailingForm.body=this._mailingTplBodies[id]||'';
    this.mailingForm.isHtml=false;
  },
  toggleMailingHtml(){
    if(!this.mailingForm.isHtml){
      this._mailingTextBackup=this.mailingForm.body||'';
      this.mailingForm.body=this._mailingWrap(this.mailingForm.template,this._mailingTextToHtml(this._mailingTextBackup));
      this.mailingForm.isHtml=true;
    }else{
      this.mailingForm.body=this._mailingTextBackup;
      this.mailingForm.isHtml=false;
    }
  },
  insertMailingVar(v){
    const ta=this.$refs.mailingBody;
    if(!ta)return;
    const s=ta.selectionStart,e=ta.selectionEnd;
    this.mailingForm.body=this.mailingForm.body.substring(0,s)+v+this.mailingForm.body.substring(e);
    this.$nextTick(()=>{ta.focus();ta.selectionStart=ta.selectionEnd=s+v.length;});
  },
  get mailingPreviewHtml(){
    const body=this.mailingForm.body||'';
    if(this.mailingForm.isHtml)return this._mailingSubstVars(body);
    return this._mailingWrap(this.mailingForm.template,this._mailingTextToHtml(this._mailingSubstVars(body)));
  },
  async saveMailing(){
    if(!this.mailingForm.subject||!this.mailingForm.body){this.mailingError='Заполните тему и текст';return;}
    this.mailingError='';this.mailingSaving=true;
    try{
      const payload={...this.mailingForm,template:this.mailingForm.isHtml?'html':this.mailingForm.template};
      const d=await(await fetch('/admin/api.php?action=saveMailing',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)})).json();
      if(d.error){this.mailingError=d.error;return;}
      this.mailingModal=false;
      await this.loadMailings();
    }finally{this.mailingSaving=false;}
  },
  async deleteMailing(ml){
    if(!confirm(`Удалить рассылку «${ml.subject}»?`))return;
    await fetch('/admin/api.php?action=deleteMailing',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:ml.id})});
    this.mailings=this.mailings.filter(x=>x.id!==ml.id);
  },

  _initHeadCm(){
    const el=document.getElementById('headCodeEditor');
    if(!el||this._headCm)return;
    this._headCm=CodeMirror(el,{
      value:this.siteSettings.head_code||'',
      mode:'htmlmixed',
      theme:'material-darker',
      lineNumbers:true,
      lineWrapping:true,
      tabSize:2,
      indentWithTabs:false,
    });
  },
};}
</script>
<?php require __DIR__ . '/_template-save.php'; ?>
</body>
</html>
