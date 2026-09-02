<?php
/**
 * Shared page-list sidebar (PHP-rendered, for page-preview.php).
 * Required vars:
 *   $sidebarPages     — array of tap_pages rows
 *   $sidebarCurrentId — string, currently open page id
 */
?>
<aside class="w-52 shrink-0 bg-gray-900 border-r border-gray-800 flex flex-col overflow-hidden">
  <div class="p-3 border-b border-gray-800 flex items-center justify-between">
    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Страницы</span>
    <a href="/admin/" class="text-gray-500 hover:text-blue-400 transition-colors p-0.5 no-underline" title="Управление страницами">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
        <circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
      </svg>
    </a>
  </div>
  <nav class="flex-1 overflow-y-auto sb p-2 space-y-0.5">
    <?php foreach ($sidebarPages as $pg):
      $isActive = $pg['id'] === $sidebarCurrentId;
      $pgId    = json_encode($pg['id']);
      $pgTitle = json_encode($pg['title']);
      $pgSlug  = json_encode($pg['slug'] ?? '');
    ?>
    <div class="relative group">
      <a href="/admin/page-preview.php?page_id=<?= htmlspecialchars($pg['id']) ?>"
         class="block px-2.5 py-2 pr-12 rounded-lg text-sm transition-colors no-underline <?= $isActive ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' ?>">
        <div class="flex items-center gap-1.5 min-w-0">
          <?php if ($pg['is_main']): ?>
          <span class="shrink-0 text-xs bg-yellow-500/20 text-yellow-400 border border-yellow-500/30 px-1 py-0 rounded leading-4">★</span>
          <?php endif; ?>
          <span class="truncate font-medium"><?= htmlspecialchars($pg['title']) ?></span>
        </div>
        <div class="text-xs opacity-50 mt-0.5 font-mono truncate"><?= $pg['slug'] ? '/p/' . htmlspecialchars($pg['slug']) : '/' ?></div>
      </a>
      <div class="absolute right-1 top-1 hidden group-hover:flex gap-0.5 z-10">
        <button @click.prevent="openSidebarEdit(<?= $pgId ?>, <?= $pgTitle ?>, <?= $pgSlug ?>)"
          class="p-1 rounded transition-colors <?= $isActive ? 'text-blue-200 hover:text-white hover:bg-blue-500' : 'text-gray-600 hover:text-white hover:bg-gray-700' ?>"
          title="Настройки">
          <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>
        </button>
        <button @click.prevent="sidebarDeletePage(<?= $pgId ?>, <?= $pgTitle ?>)"
          class="p-1 rounded transition-colors <?= $isActive ? 'text-blue-200 hover:text-red-400 hover:bg-blue-500' : 'text-gray-600 hover:text-red-400 hover:bg-gray-700' ?>"
          title="Удалить">
          <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </nav>
</aside>
