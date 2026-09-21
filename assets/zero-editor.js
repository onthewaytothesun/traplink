/* Full-screen layer editor. A private draft is applied to the Alpine form explicitly. */
(function(){
'use strict';
const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const safeUrl=v=>typeof v==='string'&&!/[\x00-\x20\x7f\\]/.test(v)&&/^(https?:\/\/[^/]+|\/(?!\/))/.test(v)?v:'';
const color=(v,f='transparent')=>/^(#[\da-f]{3}|#[\da-f]{6}|#[\da-f]{8}|transparent)$/i.test(v)?v:f;
const button=(action,label,extra='')=>`<button type="button" data-action="${action}" ${extra}>${label}</button>`;
let dialog,model,device,zoom,snap,onApply,initial,drag,uploadToken=0;
function field(key,label,value,type='text',extra=''){
 return `<label class="ze-field"><span>${label}</span><input data-field="${key}" type="${type}" value="${esc(value)}" ${extra}></label>`;
}
function select(key,label,value,items){return `<label class="ze-field"><span>${label}</span><select data-field="${key}">${items.map(([v,t])=>`<option value="${esc(v)}" ${v===value?'selected':''}>${t}</option>`).join('')}</select></label>`;}
function create(){
 dialog=document.createElement('dialog');dialog.className='ze-dialog';dialog.tabIndex=-1;dialog.setAttribute('aria-label','Редактор своего блока');document.body.append(dialog);
 dialog.addEventListener('click',click);dialog.addEventListener('change',change);dialog.addEventListener('pointerdown',pointerDown);
 dialog.addEventListener('cancel',e=>{e.preventDefault();close();});
 dialog.addEventListener('keydown',key,true);
 window.addEventListener('pointermove',pointerMove);window.addEventListener('pointerup',pointerUp);window.addEventListener('pointercancel',pointerUp);window.addEventListener('blur',pointerUp);
 window.addEventListener('beforeunload',e=>{if(dialog.open&&JSON.stringify(model.export())!==initial){e.preventDefault();e.returnValue='';}});
}
function open(options,apply){
 if(!dialog)create();if(dialog.open)return;
 model=new ZeroModel.Model(options.zero);initial=JSON.stringify(model.export());onApply=apply;device='desktop';snap=true;zoom=Math.min(1,Math.max(.25,(window.innerWidth-580)/1000));uploadToken++;
 render();dialog.showModal();dialog.querySelector('[data-action="add-text"]').focus();
}
function close(){if(JSON.stringify(model.export())!==initial&&!confirm('Закрыть редактор без применения изменений?'))return;uploadToken++;drag=null;dialog.close();}
function render(){
 const m=model,l=m.selected,g=l?.[device];
 dialog.innerHTML=`<header class="ze-header"><div><strong>Свой блок</strong><span class="ze-muted">Послойный редактор</span></div><div class="ze-device">${button('desktop','Компьютер',device==='desktop'?'aria-pressed="true"':'aria-pressed="false"')}${button('mobile','Телефон',device==='mobile'?'aria-pressed="true"':'aria-pressed="false"')}</div><div>${button('cancel','Отмена')}${button('apply','Применить','class="ze-primary"')}</div></header>
 <div class="ze-toolbar"><div class="ze-add">${button('add-text','＋ Текст')}${button('add-image','＋ Изображение')}${button('add-button','＋ Кнопка')}${button('add-shape','＋ Фигура')}</div><div>${button('undo','↶','aria-label="Отменить действие" title="Отменить · Ctrl/⌘ Z" '+(!m.past.length?'disabled':''))}${button('redo','↷','aria-label="Повторить действие" title="Повторить · Ctrl/⌘ Shift Z" '+(!m.future.length?'disabled':''))}<label class="ze-snap"><input type="checkbox" data-field="snap" ${snap?'checked':''}> Сетка 8 px</label><select data-field="zoom" aria-label="Масштаб">${[.25,.5,.75,1,1.25,1.5].concat([zoom]).filter((v,i,a)=>a.indexOf(v)===i).sort((a,b)=>a-b).map(v=>`<option value="${v}" ${v===zoom?'selected':''}>${Math.round(v*100)}%</option>`).join('')}</select>${button('fit','Вписать')}</div></div>
 <div class="ze-layout"><aside class="ze-layers"><div class="ze-panel-heading">СЛОИ <span>${m.data.layers.length}</span></div><div class="ze-layer-list">${[...m.data.layers].reverse().map(x=>`<div class="ze-layer-row ${x.id===m.id?'is-selected':''}"><button type="button" data-select="${esc(x.id)}" class="ze-layer-name" title="${esc(x.name)}"><span class="ze-kind">${({text:'T',image:'▧',button:'↗',shape:'□'})[x.type]}</span>${esc(x.name)}</button><button type="button" data-toggle="hidden" data-id="${esc(x.id)}" title="${x.hidden?'Показать':'Скрыть'}" aria-label="${x.hidden?'Показать':'Скрыть'} слой">${x.hidden?'○':'●'}</button><button type="button" data-toggle="locked" data-id="${esc(x.id)}" title="${x.locked?'Разблокировать':'Заблокировать'}" aria-label="${x.locked?'Разблокировать':'Заблокировать'} слой">${x.locked?'🔒':'◇'}</button></div>`).join('')||'<p class="ze-empty">Добавьте текст, изображение, кнопку или фигуру.</p>'}</div><div class="ze-layer-actions">${button('up','Выше','title="На слой выше" '+(!l||l.locked?'disabled':''))}${button('down','Ниже','title="На слой ниже" '+(!l||l.locked?'disabled':''))}</div><p class="ze-hint">Верхние слои перекрывают нижние. Размеры и положение на телефоне настраиваются отдельно.</p></aside>
 <main class="ze-workspace"><div class="ze-canvas-label">${device==='desktop'?'КОМПЬЮТЕР · 1000':'ТЕЛЕФОН · 375'} × ${m.data[device].height}</div><div class="ze-canvas-space"><div class="ze-canvas"></div></div><p class="ze-hint ze-canvas-hint">Перетаскивайте элементы • Углы меняют размер • Стрелки — 1 px, Shift — 10 px</p></main>
 <aside class="ze-properties"><div class="ze-panel-heading">${l?'СВОЙСТВА СЛОЯ':'НАСТРОЙКИ ХОЛСТА'}</div>${l?`${field('name','Название слоя',l.name)}<div class="ze-pair">${field('x','X',g.x,'number')}${field('y','Y',g.y,'number')}${field('width','Ширина',g.width,'number','min="8" max="5000"')}${field('height','Высота',g.height,'number','min="8" max="5000"')}</div>${l.locked?'<p class="ze-notice">Слой заблокирован. Нажмите замок в списке слоёв для редактирования.</p>':''}
 ${l.type!=='shape'?`<label class="ze-field"><span>${l.type==='image'?'Описание изображения':'Текст'}</span><textarea data-field="text" rows="3">${esc(l.text)}</textarea></label>`:''}
 ${l.type==='image'?`${field('src','Адрес изображения',l.src,'url')}<label class="ze-upload">Загрузить изображение<input type="file" data-upload accept="image/*" ${l.locked?'disabled':''}></label>`:''}
 ${['text','button'].includes(l.type)?`<div class="ze-pair">${field('fontSize','Размер текста',g.fontSize,'number','min="6" max="300"')}${select('fontWeight','Начертание',l.fontWeight,[['400','Обычное'],['700','Жирное']])}</div>${select('fontFamily','Шрифт',l.fontFamily,[['Arial','Arial'],['Georgia','Georgia'],['Verdana','Verdana'],['Courier New','Courier New']])}${select('align','Выравнивание',l.align,[['left','Слева'],['center','По центру'],['right','Справа']])}${field('color','Цвет текста',l.color,'color')}`:''}
 ${field('fill','Фон слоя',l.fill,'text','placeholder="#ffffff или transparent"')}<div class="ze-pair">${field('radius','Скругление',l.radius,'number','min="0" max="1000"')}${field('opacity','Непрозрачность',l.opacity,'number','min="0" max="1" step="0.05"')}</div>${field('href','Ссылка',l.href,'text','placeholder="https://… или #якорь"')}<div class="ze-actions">${button('duplicate','Дублировать')}${button('delete','Удалить','class="ze-danger" '+(l.locked?'disabled':''))}</div><hr>`:'<p class="ze-empty">Выберите слой на холсте или в списке слева.</p>'}
 <div class="ze-panel-heading">ХОЛСТ</div>${field('boardHeight','Высота · '+(device==='desktop'?'компьютер':'телефон'),m.data[device].height,'number','min="100" max="5000"')}${field('background','Цвет фона',m.data.background,'color')}<p class="ze-hint">Текст, цвета и порядок слоёв общие для обоих экранов.</p><p class="ze-error" role="status"></p></aside></div><footer class="ze-footer"><span>Черновик · ${device==='desktop'?'Компьютер':'Телефон'}</span><span>После применения нажмите «Сохранить» в форме блока.</span></footer>`;
 paint();if(dialog.open)dialog.focus();
}
function paint(){
 const w=ZeroModel.widths[device],h=model.data[device].height,canvas=dialog.querySelector('.ze-canvas');
 Object.assign(dialog.querySelector('.ze-canvas-space').style,{width:w*zoom+'px',height:h*zoom+'px'});
 Object.assign(canvas.style,{width:w+'px',height:h+'px',transform:`scale(${zoom})`,backgroundColor:color(model.data.background,'#ffffff')});canvas.classList.toggle('with-grid',snap);
 canvas.innerHTML=model.data.layers.filter(l=>!l.hidden).map(l=>{
  const g=l[device],selected=l.id===model.id,style=`left:${g.x}px;top:${g.y}px;width:${g.width}px;height:${g.height}px;font-size:${g.fontSize}px;color:${color(l.color,'#18202c')};background:${color(l.fill)};border-radius:${Number(l.radius)||0}px;opacity:${Number(l.opacity)};font-family:${['Arial','Georgia','Verdana','Courier New'].includes(l.fontFamily)?l.fontFamily:'Arial'};font-weight:${l.fontWeight==='700'?'700':'400'};text-align:${l.align};${l.type==='button'?`display:flex;align-items:center;justify-content:${({left:'flex-start',center:'center',right:'flex-end'})[l.align]};`:''}`;
  const content=l.type==='image'?(safeUrl(l.src)?`<img src="${esc(safeUrl(l.src))}" alt="${esc(l.text)}" draggable="false">`:'<span class="ze-image-placeholder">▧<small>Загрузите изображение</small></span>'):l.type==='shape'?'':esc(l.text);
  // Selection lives outside the clipped element, so corner handles remain usable.
  return `<div class="ze-element ${selected?'is-selected':''} ${l.locked?'is-locked':''}" data-layer="${esc(l.id)}" style="${style}"><div class="ze-content ze-content-${l.type}">${content}</div>${selected&&!l.locked?['nw','ne','sw','se'].map(k=>`<span class="ze-handle ze-${k}" data-handle="${k}"></span>`).join(''):''}</div>`;
 }).join('');
}
function click(e){
 const toggle=e.target.closest('[data-toggle]');if(toggle){model.select(toggle.dataset.id);model.update({[toggle.dataset.toggle]:!model.selected[toggle.dataset.toggle]});render();return;}
 const selectEl=e.target.closest('[data-select]');if(selectEl){model.select(selectEl.dataset.select);render();return;}
 const action=e.target.closest('[data-action]')?.dataset.action;if(!action)return;
 if(action.startsWith('add-'))model.add(action.slice(4));
 else if(action==='cancel'){close();return;}
 else if(action==='apply'){onApply(model.export());uploadToken++;dialog.close();return;}
 else if(action==='desktop'||action==='mobile')device=action;
 else if(action==='fit'){zoom=Math.min(1,Math.max(.1,(dialog.querySelector('.ze-workspace').clientWidth-64)/ZeroModel.widths[device]));}
 else if(action==='up')model.reorder(1);
 else if(action==='down')model.reorder(-1);
 else if(action==='delete')model.remove();
 else if(['undo','redo','duplicate'].includes(action))model[action]();
 render();
}
async function change(e){
 if(e.target.matches('[data-upload]')){
  const file=e.target.files[0];if(!file||!model.selected||model.selected.locked)return;
  const id=model.id,token=uploadToken;const status=dialog.querySelector('.ze-error');status.textContent='Загружаю изображение…';
  try{const fd=new FormData();fd.append('file',file);const r=await fetch('/admin/upload.php',{method:'POST',body:fd});const data=await r.json();if(!r.ok||!data.url)throw Error(data.error||'Не удалось загрузить изображение');
   if(token!==uploadToken||!dialog.open)return;const layer=model.data.layers.find(l=>l.id===id);if(!layer||layer.locked)return;
   const selected=model.id;model.select(id);model.update({src:data.url});model.select(selected);render();
  }catch(err){if(token===uploadToken&&dialog.open)dialog.querySelector('.ze-error').textContent=err.message;}return;
 }
 const k=e.target.dataset.field;if(!k)return;const v=e.target.type==='number'?Number(e.target.value):e.target.value;
 if(k==='zoom')zoom=Number(v);else if(k==='snap')snap=e.target.checked;
 else if(k==='boardHeight')model.board({height:v},device);else if(k==='background')model.board({background:v});
 else if(['x','y','width','height','fontSize'].includes(k))model.update({[k]:v},device);
 else if(k==='fill'&&!/^(#[\da-f]{3}|#[\da-f]{6}|#[\da-f]{8}|transparent)$/i.test(v)){e.target.setCustomValidity('Введите HEX-цвет или transparent');e.target.reportValidity();return;}
 else {e.target.setCustomValidity('');model.update({[k]:v});}
 // Keep the form nodes alive on blur: replacing them would swallow the
 // click on Apply or a toolbar button that caused this change event.
 if(k==='zoom'||k==='snap'){paint();return;}
 paint();
 dialog.querySelector('[data-action="undo"]').disabled=!model.past.length;
 dialog.querySelector('[data-action="redo"]').disabled=!model.future.length;
 dialog.querySelector('.ze-canvas-label').textContent=(device==='desktop'?'КОМПЬЮТЕР · 1000':'ТЕЛЕФОН · 375')+' × '+model.data[device].height;
 if(k==='name')for(const row of dialog.querySelectorAll('[data-select]'))if(row.dataset.select===model.id){row.innerHTML=`<span class="ze-kind">${({text:'T',image:'▧',button:'↗',shape:'□'})[model.selected.type]}</span>${esc(model.selected.name)}`;row.title=model.selected.name;}
 if(e.target.type==='number')e.target.value=k==='boardHeight'?model.data[device].height:['x','y','width','height','fontSize'].includes(k)?model.selected[device][k]:model.selected[k];
}
function pointerDown(e){
 if(e.button!==0)return;const node=e.target.closest('[data-layer]');
 if(node||e.target.classList.contains('ze-canvas')){const active=document.activeElement;if(active?.matches('input,textarea,select'))active.blur();}
 if(!node){if(e.target.classList.contains('ze-canvas')){model.select(null);render();}return;}
 const handle=e.target.dataset.handle;model.select(node.dataset.layer);const l=model.selected;
 if(l.locked){render();return;}e.preventDefault();
 drag={id:l.id,x:e.clientX,y:e.clientY,g:{...l[device]},handle,started:false};render();
}
function pointerMove(e){
 if(!drag||!dialog.open)return;e.preventDefault();const dx=(e.clientX-drag.x)/zoom,dy=(e.clientY-drag.y)/zoom;
 if(!drag.started&&Math.abs(dx)+Math.abs(dy)<2)return;
 if(!drag.started){model.checkpoint();drag.started=true;}
 const round=v=>snap?Math.round(v/8)*8:Math.round(v);const g=drag.g;let patch;
 if(!drag.handle)patch={x:round(g.x+dx),y:round(g.y+dy)};
 else {let left=g.x,top=g.y,right=g.x+g.width,bottom=g.y+g.height;
  if(drag.handle.includes('e'))right=Math.max(left+8,round(right+dx));
  if(drag.handle.includes('s'))bottom=Math.max(top+8,round(bottom+dy));
  if(drag.handle.includes('w'))left=Math.min(right-8,round(left+dx));
  if(drag.handle.includes('n'))top=Math.min(bottom-8,round(top+dy));
  patch={x:left,y:top,width:right-left,height:bottom-top};
 }
 model.update(patch,device,false);paint();
}
function pointerUp(){if(!drag)return;drag=null;render();}
function key(e){
 if(e.key==='Escape'){e.preventDefault();e.stopPropagation();close();return;}
 if(e.target.matches('input,textarea,select,[contenteditable]'))return;
 const mod=e.ctrlKey||e.metaKey,k=e.key.toLowerCase();let handled=true;
 if(mod&&k==='z')e.shiftKey?model.redo():model.undo();
 else if(mod&&k==='y')model.redo();else if(mod&&k==='d')model.duplicate();
 else if(e.key==='Delete'||e.key==='Backspace')model.remove();
 else if(['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(e.key)&&model.selected){const g=model.selected[device],step=e.shiftKey?10:1;model.update({x:g.x+(e.key==='ArrowLeft'?-step:e.key==='ArrowRight'?step:0),y:g.y+(e.key==='ArrowUp'?-step:e.key==='ArrowDown'?step:0)},device);}
 else handled=false;
 if(handled){e.preventDefault();e.stopPropagation();render();}
}
window.ZeroEditor={open};
})();
