<section class="tpl-section" x-show="activeTab==='templates'" x-cloak x-data="pageTemplates" @templates-changed.window="load()">
  <div class="tpl-heading">
    <div><p class="tpl-eyebrow">БИБЛИОТЕКА СТРАНИЦ</p><h1>Шаблоны</h1><p>Начните с готовой структуры и настройте её под себя.</p></div>
    <p class="tpl-help">Чтобы добавить свой шаблон, откройте страницу<br>и нажмите «Сохранить как шаблон».</p>
  </div>
  <div class="tpl-tools">
    <div class="tpl-tabs" role="group" aria-label="Категория шаблонов">
      <button type="button" @click="filter='all'" :aria-pressed="filter==='all'">Все</button>
      <template x-for="c in usedCategories" :key="c.id">
        <button type="button" @click="filter=c.id" :aria-pressed="filter===c.id" x-text="c.title"></button>
      </template>
      <button type="button" @click="filter='custom'" :aria-pressed="filter==='custom'">Мои шаблоны</button>
    </div>
    <input class="tpl-search" type="search" x-model="search" placeholder="Найти шаблон" aria-label="Найти шаблон">
  </div>
  <div class="tpl-error" role="alert" x-show="error"><span x-text="error"></span><button type="button" @click="load()" :disabled="loading">Обновить список</button></div>
  <p class="tpl-state" x-show="loading" role="status">Загружаем шаблоны…</p>
  <div class="tpl-empty" x-show="!loading && !error && !filtered.length">
    <h2 x-text="search ? 'Ничего не найдено' : 'Здесь будут ваши шаблоны'"></h2>
    <p x-text="search ? 'Попробуйте другое название или описание.' : 'Сохраните готовую страницу как шаблон, чтобы использовать её снова.'"></p>
  </div>
  <div class="tpl-grid" x-show="!loading">
    <template x-for="t in filtered" :key="t.id">
      <article class="tpl-card">
        <button type="button" class="tpl-preview" @click="preview=t" :aria-label="'Предпросмотр: '+t.title">
          <iframe :src="previewUrl(t)" :title="'Превью: '+t.title" sandbox loading="lazy" tabindex="-1"></iframe>
          <span class="tpl-preview-label">Предпросмотр ↗</span>
        </button>
        <div class="tpl-card-content">
          <div class="tpl-card-meta"><span x-text="(t.kind==='custom'?'Мой шаблон':'')+(t.kind==='custom'&&t.category?' · ':'')+categoryTitle(t.category)"></span><span x-text="t.block_count+' блоков'"></span></div>
          <h2 x-text="t.title"></h2><p class="tpl-description" x-text="t.description || 'Сохранённая структура и оформление страницы.'"></p>
          <div class="tpl-card-actions">
            <button type="button" class="tpl-primary" @click="create(t)" :disabled="!!busy" x-text="busy===t.id?'Подождите…':'Создать'"></button>
            <button type="button" x-show="t.kind==='custom'" @click="remove(t)" :disabled="!!busy" class="tpl-delete" :aria-label="'Удалить шаблон '+t.title">Удалить</button>
          </div>
        </div>
      </article>
    </template>
  </div>
  <div class="tpl-overlay" x-show="preview" x-cloak @click.self="preview=null" @keydown.escape.window="preview=null">
    <section class="tpl-preview-dialog" role="dialog" aria-modal="true" aria-label="Предпросмотр шаблона">
      <header><strong x-text="preview?.title"></strong><button type="button" @click="preview=null" aria-label="Закрыть предпросмотр">Закрыть ×</button></header>
      <template x-if="preview"><iframe :src="previewUrl(preview)" sandbox :title="preview.title"></iframe></template>
      <footer><span>Содержание можно изменить после создания.</span><button type="button" class="tpl-primary" @click="create(preview)" :disabled="!!busy" x-text="busy?'Создаём…':'Создать страницу'"></button></footer>
      <p class="tpl-error" x-show="error" x-text="error"></p>
    </section>
  </div>
</section>
