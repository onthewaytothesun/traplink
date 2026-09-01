<?php
session_start();
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
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1/Sortable.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/theme/material-darker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/htmlmixed/htmlmixed.min.js"></script>
<style>
  [x-cloak]{display:none!important}
  .sb::-webkit-scrollbar{width:3px}.sb::-webkit-scrollbar-track{background:transparent}.sb::-webkit-scrollbar-thumb{background:#374151;border-radius:2px}
  .CodeMirror{height:280px;font-size:13px;font-family:'JetBrains Mono','Fira Mono',monospace;border-radius:0.5rem;}
</style>
</head>
<body class="bg-gray-950 text-white" x-data="app()" x-init="init()">

<!-- Header -->
<?php $navMode = 'index'; require __DIR__ . '/_nav.php'; ?>

<div class="flex pt-14" style="height:100vh">

  <!-- Sidebar: Pages -->
  <aside x-show="activeTab==='pages'" class="w-56 shrink-0 bg-gray-900 border-r border-gray-800 flex flex-col overflow-hidden">
    <div class="p-3 border-b border-gray-800 flex items-center justify-between">
      <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Страницы</span>
      <div class="flex items-center gap-1">
        <button @click="addFolder()" title="Новая папка" class="text-gray-500 hover:text-blue-400 transition-colors p-0.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
        </button>
        <button @click="openAddPage()" title="Новая страница" class="text-gray-500 hover:text-blue-400 transition-colors p-0.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        </button>
      </div>
    </div>
    <nav class="flex-1 overflow-y-auto sb p-2 space-y-0.5">

      <!-- ── Folders ── -->
      <template x-for="folder in folders" :key="folder.id">
        <div>
          <!-- Folder row -->
          <div class="group/folder relative flex items-center gap-1 px-2 py-1.5 rounded-lg hover:bg-gray-800 cursor-pointer select-none"
               @click="folder._open = !folder._open">
            <svg class="w-3 h-3 text-gray-500 shrink-0 transition-transform" :class="folder._open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
            <span class="text-xs text-gray-300 font-medium truncate flex-1" x-text="folder.title"></span>
            <span class="text-xs text-gray-600 shrink-0" x-text="pagesInFolder(folder.id).length"></span>
            <!-- folder actions -->
            <div class="absolute right-1 top-1 flex gap-0.5 opacity-0 group-hover/folder:opacity-100 transition-opacity" @click.stop>
              <button @click="renameFolder(folder)" title="Переименовать" class="p-1 rounded text-gray-600 hover:text-gray-300 hover:bg-gray-700 transition-colors">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </button>
              <button @click="deleteFolder(folder)" title="Удалить папку" class="p-1 rounded text-gray-600 hover:text-red-400 hover:bg-gray-700 transition-colors">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </div>
          <!-- Pages in folder -->
          <div x-show="folder._open" class="ml-3 mt-0.5 space-y-0.5 border-l border-gray-800 pl-2">
            <template x-for="pg in pagesInFolder(folder.id)" :key="pg.id">
              <div class="group relative">
                <button @click="selectPage(pg)"
                  :class="currentPage?.id === pg.id ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800'"
                  class="w-full text-left px-2 py-1.5 rounded-lg text-sm transition-colors pr-12">
                  <div class="flex items-center gap-1 min-w-0">
                    <span x-show="pg.is_main" class="shrink-0 text-xs text-yellow-400">★</span>
                    <span class="truncate" x-text="pg.title"></span>
                  </div>
                </button>
                <div class="absolute right-1 top-1 flex gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click.stop="openEditPage(pg)" :class="currentPage?.id===pg.id?'text-white/60 hover:text-white':'text-gray-600 hover:text-gray-300'" class="p-1 rounded hover:bg-gray-700 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
                  </button>
                  <button @click.stop="deletePage(pg)" :class="currentPage?.id===pg.id?'text-white/60 hover:text-red-300':'text-gray-600 hover:text-red-400'" class="p-1 rounded hover:bg-gray-700 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </div>
              </div>
            </template>
            <div x-show="pagesInFolder(folder.id).length===0" class="text-xs text-gray-600 px-2 py-1">пусто</div>
          </div>
        </div>
      </template>

      <!-- ── Ungrouped pages ── -->
      <template x-for="pg in pagesWithoutFolder" :key="pg.id">
        <div class="group relative">
          <button @click="selectPage(pg)"
            :class="currentPage?.id === pg.id ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800'"
            class="w-full text-left px-2.5 py-2 rounded-lg text-sm transition-colors pr-14">
            <div class="flex items-center gap-1.5 min-w-0">
              <span x-show="pg.is_main" class="shrink-0 text-xs bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 px-1 py-0 rounded leading-4">★</span>
              <span class="truncate font-medium" x-text="pg.title"></span>
            </div>
            <div class="text-xs opacity-50 mt-0.5 font-mono truncate" x-text="pg.slug ? '/p/'+pg.slug : '—'"></div>
          </button>
          <div class="absolute right-1.5 top-1.5 flex gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
            <button @click.stop="openEditPage(pg)" :class="currentPage?.id===pg.id?'text-white/60 hover:text-white':'text-gray-600 hover:text-gray-300'" class="p-1 rounded hover:bg-gray-700 transition-colors">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
            </button>
            <button @click.stop="deletePage(pg)" :class="currentPage?.id===pg.id?'text-white/60 hover:text-red-300':'text-gray-600 hover:text-red-400'" class="p-1 rounded hover:bg-gray-700 transition-colors">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
          </div>
        </div>
      </template>

      <div x-show="pages.length === 0 && !loading" class="text-center py-6 text-gray-600 text-xs">
        Страниц нет.<br>
        <button @click="openAddPage()" class="text-blue-400 hover:underline mt-1">Создать первую</button>
      </div>
    </nav>
  </aside>

  <!-- Main -->
  <main x-show="activeTab==='pages'" class="flex-1 flex flex-col overflow-hidden">

    <!-- No page selected -->
    <div x-show="!currentPage && !loading" class="flex-1 flex items-center justify-center text-gray-600">
      <div class="text-center">
        <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-sm">Выберите страницу слева<br>или <button @click="openAddPage()" class="text-blue-400 hover:underline">создайте новую</button></p>
      </div>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="flex-1 flex items-center justify-center text-gray-600">
      <svg class="animate-spin w-6 h-6" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
    </div>

    <!-- Page content -->
    <template x-if="currentPage && !loading">
      <div class="flex flex-col flex-1 overflow-hidden">

        <!-- Sections bar -->
        <div class="border-b border-gray-800 bg-gray-900/50 px-5 py-2 flex items-center gap-2 flex-wrap">
          <button @click="currentSection = null"
            :class="currentSection === null ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-white'"
            class="text-xs px-3 py-1.5 rounded-full transition-colors">
            Все <span class="opacity-60" x-text="pageBlocks.length"></span>
          </button>
          <template x-for="sec in pageSections" :key="sec.id">
            <div class="flex items-center gap-1 group/sec">
              <button @click="currentSection = sec.id"
                :class="currentSection === sec.id ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-white'"
                class="text-xs px-3 py-1.5 rounded-full transition-colors"
                x-text="sec.title + ' (' + blocksInSection(sec.id) + ')'"></button>
              <button @click="deleteSection(sec.id)"
                class="opacity-0 group-hover/sec:opacity-100 text-gray-600 hover:text-red-400 transition-all">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </template>
          <button @click="openAddSection()"
            class="text-xs text-gray-600 hover:text-blue-400 px-2 py-1.5 transition-colors flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Секция
          </button>
        </div>

        <!-- Toolbar -->
        <div class="px-5 py-3 flex items-center justify-between shrink-0">
          <span class="text-sm text-gray-400"
            x-text="currentSection ? pageSections.find(s=>s.id===currentSection)?.title : currentPage.title"></span>
          <button @click="openAdd()"
            class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-500 text-white px-3.5 py-2 rounded-lg text-sm font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Добавить блок
          </button>
        </div>

        <!-- Blocks -->
        <div class="flex-1 overflow-y-auto sb px-5 pb-5">
          <div x-show="visibleBlocks.length === 0" class="text-center py-16 text-gray-600">
            <svg class="w-9 h-9 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            Блоков нет. <button @click="openAdd()" class="text-blue-400 hover:underline">Добавить</button>
          </div>

          <div x-show="visibleBlocks.length > 0" class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-gray-800 text-xs text-gray-500 uppercase tracking-wider">
                  <th class="w-8 px-2 py-3"></th>
                  <th class="text-left px-4 py-3 w-8">#</th>
                  <th class="text-left px-4 py-3">Тип</th>
                  <th class="text-left px-4 py-3">Содержимое</th>
                  <th class="text-left px-4 py-3 hidden md:table-cell">Секция</th>
                  <th class="text-left px-4 py-3">Видимость</th>
                  <th class="px-4 py-3 w-20"></th>
                </tr>
              </thead>
              <tbody id="blocksTbody">
                <template x-for="(b, i) in visibleBlocks" :key="b.id">
                  <tr class="border-b border-gray-800/40 hover:bg-gray-800/30 transition-colors" :data-block-id="b.id">
                    <td class="px-2 py-3">
                      <div class="drag-handle cursor-grab active:cursor-grabbing text-gray-600 hover:text-gray-400 select-none flex items-center justify-center w-6 mx-auto">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-gray-600" x-text="i + 1"></td>
                    <td class="px-4 py-3">
                      <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-md bg-gray-800 flex items-center justify-center shrink-0 text-gray-200">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"
                            x-html="getIcon(b.block_type_name)"></svg>
                        </div>
                        <div>
                          <div class="text-gray-200 text-xs font-medium" x-text="getLabel(b.block_type_id)"></div>
                          <div class="text-gray-600 text-xs font-mono" x-text="b.block_type_name"></div>
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-gray-400 max-w-xs">
                      <span class="block truncate" x-text="preview(b)"></span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 hidden md:table-cell"
                      x-text="b.section_id ? (pageSections.find(s=>s.id===b.section_id)?.title || '—') : '—'"></td>
                    <td class="px-4 py-3">
                      <button @click="toggleVis(b)"
                        :class="b.is_visible ? 'bg-green-500/10 text-green-400 border-green-500/20' : 'bg-gray-800 text-gray-600 border-gray-700'"
                        class="text-xs border px-2 py-0.5 rounded-full transition-colors"
                        x-text="b.is_visible ? 'Виден' : 'Скрыт'"></button>
                    </td>
                    <td class="px-4 py-3">
                      <div class="flex items-center justify-end gap-0.5">
                        <template x-if="b.block_type_name==='form'">
                          <button @click="openSubmissions(b)" class="text-gray-500 hover:text-blue-400 p-1.5 rounded hover:bg-gray-700 transition-colors" title="Заявки">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                          </button>
                        </template>
                        <button @click="openEdit(b)" class="text-gray-500 hover:text-white p-1.5 rounded hover:bg-gray-700 transition-colors">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                          </svg>
                        </button>
                        <button @click="removeBlock(b)" class="text-gray-600 hover:text-red-400 p-1.5 rounded hover:bg-gray-700 transition-colors">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                          </svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </template>
  </main>

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

      <button @click="saveSiteSettings()" :disabled="siteSettingsSaving"
        class="w-full bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white py-2.5 rounded-lg text-sm font-medium transition-colors"
        x-text="siteSettingsSaving?'Сохраняю…':'Сохранить настройки'"></button>
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

    <div class="overflow-y-auto sb p-5 space-y-4 flex-1">

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
        <label class="block text-sm text-gray-400 mb-1.5">Секция</label>
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

      <!-- JSON fallback for complex types -->
      <template x-if="['messenger','socialnetworks','collapse','media','pricing','music','plans','form','pictures','avatar','digitals-product'].includes(typeName(form.block_type_id))">
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
<script>
function txtSize(s){const m={h1:'50px',h2:'30px',h3:'24px',sm:'14px',md:'17px',lg:'20px'};return m[s]||'17px';}
function txtLineHeight(s){const m={h1:'1.15',h2:'1.25',h3:'1.4',sm:'1.45',md:'1.45',lg:'1.45'};return m[s]||'1.45';}
function txtWeight(s,bold){if(bold!==undefined&&bold!==null)return bold?'700':'400';return['h1','h2'].includes(s)?'700':'400';}
function loadGoogleFont(f){if(!f)return;const id='gfont-'+f.replace(/\s+/g,'-');if(!document.getElementById(id)){const l=document.createElement('link');l.id=id;l.rel='stylesheet';l.href='https://fonts.googleapis.com/css2?family='+encodeURIComponent(f)+':wght@400;600;700&display=swap';document.head.appendChild(l);}}
function app(){return{
  pages:[],blocks:[],sections:[],folders:[],
  loading:true,
  currentPage:null,currentSection:null,
  blockModal:false,sectionModal:false,pageModal:false,
  editId:null,editPageId:null,
  saving:false,formError:'',pageFormError:'',
  newSectionTitle:'',
  form:{block_type_id:1,section_id:'',is_visible:true,anchor:''},
  opts:{},optsJson:'{}',
  activeTab:'pages',
  design:{screen:'#ffffff',text_color:'#343a40',link_bg:'#ffffff',link_color:'#343a40',link_radius:7,link_border_width:0,link_border_color:'#ffffff',link_shadow:'none',link_shadow_color:'rgba(0,0,0,.15)',page_font:''},
  designSaving:false,
  settingsLoaded:false,
  siteSettings:{head_code:'',seo_title:'',seo_description:''},
  siteSettingsSaving:false,
  siteSettingsLoaded:false,
  _headCm:null,
  subsModal:false,subsLoading:false,submissions:[],_subsBlockId:null,
  allSubmissions:[],allSubsLoading:false,
  pageForm:{title:'',slug:'',is_main:false,slugEdited:false,folder_id:null},

  _sortable:null,

  blockTypes:BT_LIST.map(id=>({id,label:LABELS[id]||TYPE_MAP[id],icon:ICONS[TYPE_MAP[id]]||''})),

  get pageBlocks(){return this.blocks.filter(b=>b.page_id===this.currentPage?.id);},
  get pageSections(){return this.sections.filter(s=>s.page_id===this.currentPage?.id);},
  get visibleBlocks(){
    const bs=this.pageBlocks;
    return this.currentSection===null?bs:bs.filter(b=>b.section_id===this.currentSection);
  },

  async init(){
    await Promise.all([this.loadPages(),this.loadFolders(),this.loadSettings()]);
    this.loading=false;
    this.$watch('activeTab',tab=>{
      if(tab==='settings'){
        this.loadSiteSettings().then(()=>this.$nextTick(()=>this._initHeadCm()));
      }
    });
  },
  async loadPages(){
    const d=await(await fetch('/admin/api.php?action=pages')).json();
    this.pages=d.pages||[];
  },
  async loadFolders(){
    const d=await(await fetch('/admin/api.php?action=folders')).json();
    this.folders=(d.folders||[]).map(f=>({...f,_open:false}));
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
  selectPage(pg){
    window.location.href='/admin/page-preview.php?page_id='+pg.id;
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
  openAddPage(){this.editPageId=null;this.pageForm={title:'',slug:'',is_main:false,slugEdited:false,folder_id:null};this.pageFormError='';this.pageModal=true;},
  openEditPage(pg){this.editPageId=pg.id;this.pageForm={title:pg.title,slug:pg.slug,is_main:pg.is_main,slugEdited:true,folder_id:pg.folder_id||null};this.pageFormError='';this.pageModal=true;},
  autoSlug(){if(!this.pageForm.slugEdited)this.pageForm.slug=slugify(this.pageForm.title);},
  async savePage(){
    this.pageFormError='';
    const{title,slug,is_main,folder_id}=this.pageForm;
    if(!title.trim()){this.pageFormError='Введите название';return;}
    if(this.editPageId){
      const d=await(await fetch('/admin/api.php?action=updatePage',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:this.editPageId,title:title.trim(),slug,folder_id:folder_id||null})})).json();
      if(d.ok){
        const pg=this.pages.find(p=>p.id===this.editPageId);
        if(pg){pg.title=title.trim();pg.slug=slug;pg.folder_id=folder_id||null;}
        if(is_main&&!pg.is_main){await this.setMain(this.editPageId);}
        else if(this.currentPage?.id===this.editPageId){this.currentPage={...this.currentPage,title:title.trim(),slug};}
        this.pageModal=false;
      }
    }else{
      const d=await(await fetch('/admin/api.php?action=addPage',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({title:title.trim(),slug,folder_id:folder_id||null})})).json();
      if(d.ok){
        this.pages.push(d.page);
        if(is_main)await this.setMain(d.page.id);
        this.pageModal=false;
        await this.selectPage(d.page);
      }
    }
  },
  async setMain(id){
    const d=await(await fetch('/admin/api.php?action=setMain',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})})).json();
    if(d.ok)this.pages.forEach(p=>{p.is_main=p.id===id;});
  },
  async deletePage(pg){
    if(!confirm(`Удалить страницу «${pg.title}»? Блоки будут отвязаны.`))return;
    const d=await(await fetch('/admin/api.php?action=deletePage',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:pg.id})})).json();
    if(d.ok){
      this.pages=this.pages.filter(p=>p.id!==pg.id);
      if(this.currentPage?.id===pg.id){this.currentPage=null;this.blocks=[];this.sections=[];}
    }
  },

  // Folders
  get pagesWithoutFolder(){return this.pages.filter(p=>!p.folder_id);},
  pagesInFolder(id){return this.pages.filter(p=>p.folder_id===id);},
  async addFolder(){
    const title=prompt('Название папки:','Папка');
    if(!title||!title.trim())return;
    const d=await(await fetch('/admin/api.php?action=addFolder',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({title:title.trim()})})).json();
    if(d.ok)this.folders.push({...d.folder,_open:false});
  },
  async renameFolder(folder){
    const title=prompt('Переименовать:',folder.title);
    if(!title||!title.trim()||title.trim()===folder.title)return;
    const d=await(await fetch('/admin/api.php?action=updateFolder',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:folder.id,title:title.trim()})})).json();
    if(d.ok)folder.title=title.trim();
  },
  async deleteFolder(folder){
    if(!confirm(`Удалить папку «${folder.title}»? Страницы останутся.`))return;
    const d=await(await fetch('/admin/api.php?action=deleteFolder',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:folder.id})})).json();
    if(d.ok){
      this.pages.forEach(p=>{if(p.folder_id===folder.id)p.folder_id=null;});
      this.folders=this.folders.filter(f=>f.id!==folder.id);
    }
  },

  // Block
  selectType(id){this.form.block_type_id=id;this.opts={};this.optsJson=PLACEHOLDERS[TYPE_MAP[id]]||'{}';},
  openAdd(){
    this.editId=null;
    this.form={block_type_id:1,section_id:this.currentSection||'',is_visible:true,anchor:''};
    this.opts={};this.optsJson='{}';this.formError='';this.blockModal=true;
  },
  openEdit(b){
    this.editId=b.id;
    this.form={block_type_id:b.block_type_id,section_id:b.section_id||'',is_visible:!!b.is_visible,anchor:b.anchor||''};
    const o=b.options||{};this.opts={...o};this.optsJson=JSON.stringify(o,null,2);
    this.formError='';this.blockModal=true;
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
      if(d.ok){await this.loadBlocks();this.blockModal=false;}
      else this.formError=d.error||'Ошибка';
    }catch{this.formError='Ошибка сети';}
    finally{this.saving=false;}
  },
  async removeBlock(b){
    if(!confirm(`Удалить блок «${LABELS[b.block_type_id]||b.block_type_name}»?`))return;
    const d=await(await fetch('/admin/api.php?action=delete',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:b.id})})).json();
    if(d.ok)this.blocks=this.blocks.filter(x=>x.id!==b.id);
  },
  async toggleVis(b){
    const nv=!b.is_visible;
    const d=await(await fetch('/admin/api.php?action=toggle',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:b.id,is_visible:nv})})).json();
    if(d.ok)b.is_visible=nv;
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
</body>
</html>
