<div x-data="templateSave" @save-page-template.window="show($event.detail.page)" @keydown.escape.window="close()">
  <div class="tpl-overlay" x-show="open" x-cloak @click.self="close()">
    <section class="tpl-save-dialog" role="dialog" aria-modal="true" aria-labelledby="templateSaveTitle">
      <header><h2 id="templateSaveTitle">Сохранить как шаблон</h2><button type="button" @click="close()" :disabled="saving" aria-label="Закрыть">×</button></header>
      <form @submit.prevent="save()" x-show="!success">
        <p>Сохраним текущую сохранённую версию страницы: блоки, секции и оформление. Сначала сохраните изменения в редакторе.</p>
        <label>Название<input x-ref="templateTitle" type="text" x-model="title" maxlength="255" required :disabled="saving"></label>
        <label>Описание<textarea x-model="description" rows="3" maxlength="1000" placeholder="Для чего подойдёт этот шаблон" :disabled="saving"></textarea></label>
        <p class="tpl-error" role="alert" x-show="error" x-text="error"></p>
        <footer><button type="button" @click="close()" :disabled="saving">Отмена</button><button type="submit" class="tpl-primary" :disabled="saving" x-text="saving?'Сохраняем…':'Сохранить шаблон'"></button></footer>
      </form>
      <div class="tpl-success" x-show="success" role="status"><h3>Шаблон сохранён</h3><p>Он доступен в разделе «Шаблоны → Мои шаблоны». Изменение исходной страницы не повлияет на эту копию.</p><footer><button type="button" @click="close()">Готово</button><a class="tpl-primary" href="/admin/?tab=templates">К шаблонам</a></footer></div>
    </section>
  </div>
</div>
