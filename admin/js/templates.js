(function () {
  'use strict';
  async function api(action, body) {
    const response = await fetch('/admin/api.php?action=' + action, body === undefined ? {} : {
      method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(body)
    });
    const data = await response.json();
    if (!response.ok || data.error) throw new Error(data.error || 'Не удалось выполнить запрос.');
    return data;
  }
  function requestToken(id) {
    const key = 'template-create-' + id;
    let token = sessionStorage.getItem(key);
    if (!token) {
      const bytes = crypto.getRandomValues(new Uint8Array(16));
      token = Array.from(bytes, b => b.toString(16).padStart(2, '0')).join('');
      sessionStorage.setItem(key, token);
    }
    return token;
  }
  document.addEventListener('alpine:init', () => {
    Alpine.data('pageTemplates', () => ({
      templates: [], categories: [], loading: false, error: '', filter: 'all', search: '', busy: '', preview: null,
      init() { this.load(); },
      get usedCategories() {
        return this.categories.filter(c => this.templates.some(t => t.category === c.id));
      },
      categoryTitle(id) { return this.categories.find(c => c.id === id)?.title || ''; },
      get filtered() {
        const q = this.search.trim().toLowerCase();
        const f = this.filter;
        return this.templates.filter(t => (f === 'all' || (f === 'custom' ? t.kind === 'custom' : t.category === f)) &&
          (!q || (t.title + ' ' + t.description).toLowerCase().includes(q)));
      },
      async load() {
        if (this.loading) return;
        this.loading = true; this.error = '';
        try {
          const data = await api('templates');
          this.templates = data.templates; this.categories = data.categories || [];
          if (!['all', 'custom'].includes(this.filter) && !this.usedCategories.some(c => c.id === this.filter)) this.filter = 'all';
        }
        catch (e) { this.error = e.message || 'Не удалось загрузить шаблоны.'; }
        finally { this.loading = false; }
      },
      previewUrl(t) { return '/admin/template-preview.php?id=' + encodeURIComponent(t.id); },
      async create(t) {
        if (this.busy) return;
        this.busy = t.id; this.error = '';
        try {
          const result = await api('createFromTemplate', {template_id: t.id, request_id: requestToken(t.id)});
          if (!result.page?.id) throw new Error('Сервер не вернул созданную страницу.');
          sessionStorage.removeItem('template-create-' + t.id);
          window.location.assign('/admin/?page_id=' + encodeURIComponent(result.page.id));
        } catch (e) {
          this.error = e.message || 'Не удалось создать страницу. Попробуйте снова.';
          this.busy = '';
        }
      },
      async remove(t) {
        if (this.busy || t.kind !== 'custom' || !confirm('Удалить шаблон «' + t.title + '»? Созданные страницы останутся.')) return;
        this.busy = t.id; this.error = '';
        try {
          await api('deleteTemplate', {id: t.id});
          this.templates = this.templates.filter(item => item.id !== t.id);
          if (this.preview?.id === t.id) this.preview = null;
        } catch (e) { this.error = e.message; }
        finally { this.busy = ''; }
      }
    }));
    Alpine.data('templateSave', () => ({
      open: false, page: null, title: '', description: '', category: '', categories: [], saving: false, error: '', success: false,
      show(page) {
        if (!page?.id || this.saving) return;
        this.page = page; this.title = page.title; this.description = ''; this.category = '';
        if (!this.categories.length) api('templates').then(d => { this.categories = d.categories || []; }).catch(() => {});
        this.error = ''; this.success = false; this.open = true;
        this.$nextTick(() => this.$refs.templateTitle.focus());
      },
      close() { if (!this.saving) this.open = false; },
      async save() {
        if (this.saving || this.success) return;
        if (!this.title.trim()) { this.error = 'Введите название шаблона.'; return; }
        this.saving = true; this.error = '';
        try {
          await api('savePageTemplate', {page_id: this.page.id, title: this.title.trim(), description: this.description.trim(), category: this.category});
          this.success = true;
          window.dispatchEvent(new CustomEvent('templates-changed'));
        } catch (e) { this.error = e.message || 'Не удалось сохранить шаблон.'; }
        finally { this.saving = false; }
      }
    }));
  });
})();
