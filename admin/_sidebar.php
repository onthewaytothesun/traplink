<?php
/**
 * Shared page-list sidebar (Alpine.js component).
 * Required vars:
 *   $sidebarCurrentId — string, currently open page id ('' for index.php)
 *   $sidebarMode      — 'navigate' | 'select'
 */
?>
<aside class="w-52 shrink-0 bg-gray-900 border-r border-gray-800 flex flex-col overflow-hidden"
  x-data="sidebarComp({currentPageId: <?= htmlspecialchars(json_encode($sidebarCurrentId ?? ''), ENT_QUOTES) ?>, mode: <?= htmlspecialchars(json_encode($sidebarMode ?? 'navigate'), ENT_QUOTES) ?>})">

  <!-- Header -->
  <div class="p-3 border-b border-gray-800 flex items-center justify-between">
    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Страницы</span>
    <div class="flex items-center gap-1">
      <?php if (($sidebarMode ?? 'navigate') === 'select'): ?>
      <button @click="$dispatch('sidebar-add-folder')" title="Новая папка" class="text-gray-500 hover:text-blue-400 transition-colors p-0.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
      </button>
      <button @click="$dispatch('sidebar-add-page')" title="Новая страница" class="text-gray-500 hover:text-blue-400 transition-colors p-0.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      </button>
      <?php else: ?>
      <a href="/admin/" class="text-gray-500 hover:text-blue-400 transition-colors p-0.5 no-underline" title="Управление страницами">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
          <circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
        </svg>
      </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Search -->
  <div class="px-2 pt-2">
    <div class="relative">
      <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="m21 21-4.35-4.35"/></svg>
      <input type="text" x-model="search" placeholder="Поиск…"
        class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg pl-8 pr-7 py-1.5 text-xs focus:outline-none focus:border-blue-500 placeholder-gray-600 transition-colors">
      <button x-show="search" @click="search=''" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-white transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
  </div>

  <!-- Nav -->
  <nav class="flex-1 overflow-y-auto sb p-2 space-y-0.5">

    <!-- Folders -->
    <template x-for="folder in filteredFolders" :key="folder.id">
      <div>
        <!-- Folder row -->
        <div class="group/folder relative flex items-center gap-1 px-2 py-1.5 rounded-lg hover:bg-gray-800 cursor-pointer select-none"
             @click="folder._open = !folder._open">
          <svg class="w-3 h-3 text-gray-500 shrink-0 transition-transform" :class="folder._open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
          <span class="text-xs text-gray-300 font-medium truncate flex-1" x-text="folder.title"></span>
          <span class="text-xs text-gray-600 shrink-0" x-text="pagesInFolder(folder.id).length"></span>
        </div>
        <!-- Pages in folder -->
        <div x-show="folder._open || search" class="ml-3 mt-0.5 space-y-0.5 border-l border-gray-800 pl-2">
          <template x-for="pg in pagesInFolder(folder.id)" :key="pg.id">
            <div class="group relative">
              <template x-if="mode === 'navigate'">
                <a :href="`/admin/page-preview.php?page_id=${pg.id}`"
                  :class="currentPageId === pg.id ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800'"
                  class="block px-2 py-1.5 pr-12 rounded-lg text-sm transition-colors no-underline">
                  <div class="flex items-center gap-1 min-w-0">
                    <span x-show="pg.is_main" class="shrink-0 text-xs text-yellow-400">★</span>
                    <span class="truncate" x-text="pg.title"></span>
                  </div>
                </a>
              </template>
              <template x-if="mode === 'select'">
                <button @click="clickPage(pg)"
                  :class="currentPageId === pg.id ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800'"
                  class="w-full text-left px-2 py-1.5 pr-12 rounded-lg text-sm transition-colors">
                  <div class="flex items-center gap-1 min-w-0">
                    <span x-show="pg.is_main" class="shrink-0 text-xs text-yellow-400">★</span>
                    <span class="truncate" x-text="pg.title"></span>
                  </div>
                </button>
              </template>
              <div class="absolute right-1 top-1 hidden group-hover:flex gap-0.5 z-10">
                <button @click.prevent.stop="openEdit(pg)"
                  :class="currentPageId === pg.id ? 'text-blue-200 hover:text-white hover:bg-blue-500' : 'text-gray-600 hover:text-white hover:bg-gray-700'"
                  class="p-1 rounded transition-colors" title="Настройки">
                  <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>
                </button>
                <button @click.prevent.stop="deletePage(pg)"
                  :class="currentPageId === pg.id ? 'text-blue-200 hover:text-red-400 hover:bg-blue-500' : 'text-gray-600 hover:text-red-400 hover:bg-gray-700'"
                  class="p-1 rounded transition-colors" title="Удалить">
                  <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </div>
            </div>
          </template>
          <div x-show="pagesInFolder(folder.id).length === 0" class="text-xs text-gray-600 px-2 py-1">пусто</div>
        </div>
      </div>
    </template>

    <!-- Ungrouped pages -->
    <template x-for="pg in pagesWithoutFolder" :key="pg.id">
      <div class="group relative">
        <template x-if="mode === 'navigate'">
          <a :href="`/admin/page-preview.php?page_id=${pg.id}`"
            :class="currentPageId === pg.id ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800'"
            class="block px-2.5 py-2 pr-14 rounded-lg text-sm transition-colors no-underline">
            <div class="flex items-center gap-1.5 min-w-0">
              <span x-show="pg.is_main" class="shrink-0 text-xs bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 px-1 py-0 rounded leading-4">★</span>
              <span class="truncate font-medium" x-text="pg.title"></span>
            </div>
            <div class="text-xs opacity-50 mt-0.5 font-mono truncate" x-text="pg.slug ? '/p/'+pg.slug : '/'"></div>
          </a>
        </template>
        <template x-if="mode === 'select'">
          <button @click="clickPage(pg)"
            :class="currentPageId === pg.id ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800'"
            class="w-full text-left px-2.5 py-2 pr-14 rounded-lg text-sm transition-colors">
            <div class="flex items-center gap-1.5 min-w-0">
              <span x-show="pg.is_main" class="shrink-0 text-xs bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 px-1 py-0 rounded leading-4">★</span>
              <span class="truncate font-medium" x-text="pg.title"></span>
            </div>
            <div class="text-xs opacity-50 mt-0.5 font-mono truncate" x-text="pg.slug ? '/p/'+pg.slug : '—'"></div>
          </button>
        </template>
        <div class="absolute right-1.5 top-1.5 hidden group-hover:flex gap-0.5 z-10">
          <button @click.prevent.stop="openEdit(pg)"
            :class="currentPageId === pg.id ? 'text-blue-200 hover:text-white hover:bg-blue-500' : 'text-gray-600 hover:text-white hover:bg-gray-700'"
            class="p-1 rounded transition-colors" title="Настройки">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>
          </button>
          <button @click.prevent.stop="deletePage(pg)"
            :class="currentPageId === pg.id ? 'text-blue-200 hover:text-red-400 hover:bg-blue-500' : 'text-gray-600 hover:text-red-400 hover:bg-gray-700'"
            class="p-1 rounded transition-colors" title="Удалить">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
          </button>
        </div>
      </div>
    </template>

    <div x-show="pages.length === 0" class="text-center py-6 text-gray-600 text-xs">
      Страниц нет.
    </div>
    <div x-show="pages.length > 0 && pagesWithoutFolder.length === 0 && filteredFolders.length === 0 && search" class="text-center py-6 text-gray-600 text-xs">
      Ничего не найдено
    </div>

  </nav>

  <!-- Edit page modal (inline in aside) -->
  <div x-show="editModal" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center"
    style="background:rgba(0,0,0,.6);"
    @click.self="editModal=false">
    <div class="rounded-xl p-5 w-80 space-y-3 bg-gray-900 border border-gray-800">
      <div class="text-sm font-semibold text-white">Настройки страницы</div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">Название</label>
        <input type="text" x-model="editForm.title" @keydown.enter="saveEdit()"
          class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">Slug (URL)</label>
        <input type="text" x-model="editForm.slug" @keydown.enter="saveEdit()"
          class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-blue-500"
          placeholder="my-page">
      </div>
      <div x-show="folders.length > 0">
        <label class="block text-xs text-gray-500 mb-1">Папка</label>
        <select x-model="editForm.folder_id"
          class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
          <option value="">— Без папки —</option>
          <template x-for="f in folders" :key="f.id">
            <option :value="f.id" x-text="f.title"></option>
          </template>
        </select>
      </div>
      <div class="flex justify-end gap-2 pt-1">
        <button @click="editModal=false" class="text-gray-400 hover:text-white px-3 py-1.5 text-sm transition-colors">Отмена</button>
        <button @click="saveEdit()" :disabled="editForm.saving"
          class="text-white px-4 py-1.5 rounded-lg text-sm font-medium transition-colors disabled:opacity-50"
          style="background:#1f6feb;color:white"
          x-text="editForm.saving ? 'Сохраняю…' : 'Сохранить'"></button>
      </div>
    </div>
  </div>

</aside>
