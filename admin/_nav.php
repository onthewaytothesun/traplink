<?php
/**
 * Shared top navigation bar.
 * Required vars:
 *   $navMode  — 'index' | 'preview'
 * Preview-only vars:
 *   $navPage    — page row array
 *   $navLiveUrl — string
 */
?>
<header class="fixed inset-x-0 top-0 z-40 h-14 bg-gray-900/95 backdrop-blur border-b border-gray-800 flex items-center px-5 justify-between">
  <div class="flex items-center gap-2.5 min-w-0">
    <div class="w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h6"/>
      </svg>
    </div>
    <span class="font-semibold text-gray-400 text-sm shrink-0">Блоки</span>
    <div class="w-px h-4 bg-gray-700 mx-1 shrink-0"></div>
    <?php if ($navMode === 'index'): ?>
      <button @click="activeTab='pages'"
        :class="activeTab==='pages'?'bg-gray-800 text-white':'text-gray-500 hover:text-gray-200'"
        class="px-3 py-1.5 rounded-lg text-sm transition-colors">Страницы</button>
      <button @click="activeTab='design';loadSettings()"
        :class="activeTab==='design'?'bg-gray-800 text-white':'text-gray-500 hover:text-gray-200'"
        class="px-3 py-1.5 rounded-lg text-sm transition-colors">Дизайн</button>
    <?php else: ?>
      <a href="/admin/"
        class="px-3 py-1.5 rounded-lg text-sm transition-colors text-gray-500 hover:text-gray-200 hover:bg-gray-800 no-underline">Страницы</a>
      <button @click="designOpen=!designOpen"
        :class="designOpen?'bg-gray-800 text-white':'text-gray-500 hover:text-gray-200'"
        class="px-3 py-1.5 rounded-lg text-sm transition-colors">Дизайн</button>
      <div class="w-px h-4 bg-gray-700 mx-1 shrink-0"></div>
      <span class="text-sm font-medium text-gray-200 truncate"><?= htmlspecialchars($navPage['title']) ?></span>
      <?php if ($navPage['is_main']): ?>
      <span class="shrink-0 text-xs px-1.5 py-0.5 rounded border" style="background:#78350f;color:#fbbf24;border-color:#92400e;">★</span>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <div class="flex items-center gap-2 shrink-0">
    <?php if ($navMode === 'preview'): ?>
    <a href="<?= htmlspecialchars($navLiveUrl) ?>" target="_blank"
       class="flex items-center gap-1.5 text-gray-500 hover:text-white hover:bg-white/5 px-2.5 py-1.5 rounded-lg transition-colors text-sm no-underline">
      Открыть
      <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
    </a>
    <button @click="openAdd()"
      class="flex items-center gap-1.5 text-white text-sm font-medium px-3.5 py-1.5 rounded-lg transition-colors"
      style="background:#1f6feb;" onmouseover="this.style.background='#388bfd'" onmouseout="this.style.background='#1f6feb'">
      <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      Добавить
    </button>
    <?php endif; ?>
    <a href="/admin/?logout=1" class="text-gray-500 hover:text-white text-sm transition-colors">Выйти →</a>
  </div>
</header>
