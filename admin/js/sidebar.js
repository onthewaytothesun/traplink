document.addEventListener('alpine:init', () => {
  Alpine.data('sidebarComp', ({currentPageId, mode}) => ({
    pages: [],
    folders: [],
    currentPageId,
    mode, // 'navigate' | 'select'
    search: '',
    editModal: false,
    editForm: {id: '', title: '', slug: '', folder_id: '', saving: false},

    async init() {
      const [pRes, fRes] = await Promise.all([
        fetch('/admin/api.php?action=pages').then(r => r.json()),
        fetch('/admin/api.php?action=folders').then(r => r.json()),
      ]);
      this.pages = pRes.pages || [];
      this.folders = (fRes.folders || []).map(f => ({
        ...f,
        _open: (pRes.pages || []).some(p => p.folder_id === f.id && p.id === this.currentPageId)
      }));

      this.$el.addEventListener('sidebar-reload', () => this.reload());
      window.addEventListener('sidebar-reload', () => this.reload());
    },

    async reload() {
      const [pRes, fRes] = await Promise.all([
        fetch('/admin/api.php?action=pages').then(r => r.json()),
        fetch('/admin/api.php?action=folders').then(r => r.json()),
      ]);
      this.pages = pRes.pages || [];
      this.folders = (fRes.folders || []).map(f => ({
        ...f,
        _open: (pRes.pages || []).some(p => p.folder_id === f.id && p.id === this.currentPageId)
      }));
    },

    _matchesSearch(pg) {
      if (!this.search) return true;
      const q = this.search.toLowerCase();
      return pg.title.toLowerCase().includes(q) || (pg.slug || '').toLowerCase().includes(q);
    },

    pagesInFolder(folderId) {
      return this.pages.filter(p => p.folder_id === folderId && this._matchesSearch(p));
    },

    get pagesWithoutFolder() {
      return this.pages.filter(p => !p.folder_id && this._matchesSearch(p));
    },

    get filteredFolders() {
      if (!this.search) return this.folders;
      return this.folders.filter(f => this.pagesInFolder(f.id).length > 0);
    },

    clickPage(pg) {
      if (this.mode === 'navigate') {
        window.location.href = `/admin/page-preview.php?page_id=${pg.id}`;
      } else {
        this.currentPageId = pg.id;
        this.$dispatch('sidebar-page-selected', {page: pg});
      }
    },

    openEdit(pg) {
      this.editForm = {id: pg.id, title: pg.title, slug: pg.slug || '', folder_id: pg.folder_id || '', saving: false};
      this.editModal = true;
    },

    async saveEdit() {
      this.editForm.saving = true;
      const r = await fetch('/admin/api.php?action=updatePage', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: this.editForm.id, title: this.editForm.title, slug: this.editForm.slug, folder_id: this.editForm.folder_id || null})
      }).then(r => r.json());
      if (r.ok) {
        const pg = this.pages.find(p => p.id === this.editForm.id);
        if (pg) {
          pg.title = this.editForm.title;
          pg.slug = this.editForm.slug;
          pg.folder_id = this.editForm.folder_id || null;
        }
        this.editModal = false;
      }
      this.editForm.saving = false;
    },

    async deletePage(pg) {
      if (!confirm(`Удалить страницу "${pg.title}"?`)) return;
      const r = await fetch('/admin/api.php?action=deletePage', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: pg.id})
      }).then(r => r.json());
      if (r.ok) {
        this.pages = this.pages.filter(p => p.id !== pg.id);
        if (this.currentPageId === pg.id) {
          if (this.mode === 'navigate') {
            window.location.href = '/admin/';
          } else {
            this.$dispatch('sidebar-page-deleted', {pageId: pg.id});
          }
        }
      }
    },
  }));
});
