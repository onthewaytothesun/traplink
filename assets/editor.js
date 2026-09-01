// Shared admin constants — used by admin/index.php and admin/page-preview.php
const TYPE_MAP={1:'text',2:'link',3:'messenger',4:'video',5:'break',6:'socialnetworks',7:'html',8:'avatar',9:'pictures',10:'form',11:'page',12:'map',13:'timer',14:'collapse',15:'banner',20:'media',21:'pricing',22:'music',36:'digitals-product',50:'plans',51:'zero'};
const LABELS={1:'Текст',2:'Кнопка',3:'Мессенджер',4:'Видео',5:'Разделитель',6:'Соцсети',7:'HTML',8:'Аватар',9:'Карусель',10:'Форма',11:'Страница',12:'Карта',13:'Таймер',14:'FAQ',15:'Баннер',20:'Иконка+текст',21:'Прайс',22:'Музыка',36:'Цифр. товар',50:'Тарифы',51:'Свой блок'};
const ICONS={
  text:'<path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h12M4 18h8"/>',
  link:'<path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.1-1.1m-.757-4.9a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>',
  messenger:'<path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>',
  video:'<path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
  break:'<path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 16h14" opacity=".35"/>',
  socialnetworks:'<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
  html:'<path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>',
  avatar:'<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
  pictures:'<path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
  form:'<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>',
  page:'<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
  map:'<path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>',
  timer:'<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
  collapse:'<path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>',
  banner:'<path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h14a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l4.5 4.5a2 2 0 002.828 0L12 12l1.5 1.5a2 2 0 002.828 0L21 9"/>',
  media:'<path stroke-linecap="round" stroke-linejoin="round" d="M4 6h4v4H4zM4 14h4v4H4zM12 6h8M12 9h5M12 14h8M12 17h5"/>',
  pricing:'<path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>',
  music:'<path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>',
  'digitals-product':'<path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>',
  plans:'<path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>',
  zero:'<path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>',
};
const LINK_ICONS={
  link:    'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.1-1.1m-.757-4.9a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
  telegram:'M21 5L2 12.5l7 1M21 5l-2.5 15-9.5-6.5M21 5L9.5 13.5m0 0v5.5l3-3',
  whatsapp:'M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z',
  phone:   'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
  email:   'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
  globe:   'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
  camera:  'M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2zM12 17a4 4 0 100-8 4 4 0 000 8z',
  youtube: 'M22.54 6.42a2.78 2.78 0 00-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 00-1.95 1.96A29 29 0 001 12a29 29 0 00.46 5.58A2.78 2.78 0 003.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 001.95-1.95A29 29 0 0023 12a29 29 0 00-.46-5.58zM9.75 15.02V8.98L15.5 12l-5.75 3.02z',
  star:    'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z',
  layout:  'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 00-1 1v6a1 1 0 001 1h4a1 1 0 001-1v-6a1 1 0 00-1-1h-4z',
  layers:  'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5',
  code:    'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
  book:    'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
  zap:     'M13 10V3L4 14h7v7l9-11h-7z',
  heart:   'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
  check:   'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
  puzzle:  'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z',
};
const LINK_ICONS_LIST=[
  {id:'link',     label:'Ссылка',    path:LINK_ICONS.link},
  {id:'telegram', label:'Telegram',  path:LINK_ICONS.telegram},
  {id:'whatsapp', label:'WhatsApp',  path:LINK_ICONS.whatsapp},
  {id:'phone',    label:'Телефон',   path:LINK_ICONS.phone},
  {id:'email',    label:'Email',     path:LINK_ICONS.email},
  {id:'globe',    label:'Сайт',      path:LINK_ICONS.globe},
  {id:'youtube',  label:'YouTube',   path:LINK_ICONS.youtube},
  {id:'camera',   label:'Фото',      path:LINK_ICONS.camera},
  {id:'star',     label:'Звезда',    path:LINK_ICONS.star},
  {id:'layout',   label:'Шаблон',    path:LINK_ICONS.layout},
  {id:'layers',   label:'Слои',      path:LINK_ICONS.layers},
  {id:'code',     label:'Код',       path:LINK_ICONS.code},
  {id:'book',     label:'Книга',     path:LINK_ICONS.book},
  {id:'zap',      label:'Молния',    path:LINK_ICONS.zap},
  {id:'heart',    label:'Сердце',    path:LINK_ICONS.heart},
  {id:'check',    label:'Галочка',   path:LINK_ICONS.check},
  {id:'puzzle',   label:'Пазл',      path:LINK_ICONS.puzzle},
];
const JSON_TYPES=['messenger','socialnetworks','collapse','media','pricing','music','plans','form','pictures','avatar','digitals-product'];
const PLACEHOLDERS={
  messenger:'{"items":[{"messenger":"telegram","v":"username"}],"messenger_style":{"layout":"full"}}',
  socialnetworks:'{"items":[{"type":"instagram","link":"https://instagram.com/example/"}],"socials_style":{"layout":"full"}}',
  collapse:'{"fields":[{"title":"Вопрос?","text":"Ответ","opened":false}]}',
  media:'{"fields":[{"title":"Заголовок","text":"Текст","thumb":{}}]}',
  pricing:'{"fields":[{"title":"Товар","price":1000}]}',
  music:'{"items":[{"type":"spotify","value":"https://..."}]}',
  plans:'{"fields":[{"title":"Базовый","price":999}]}',
  form:'{"fields":[{"type_id":3,"title":"Имя","required":false,"idx":1},{"type_id":6,"title":"Email","required":true,"idx":2}],"form_btn":"Отправить","form_type":"text"}',
  pictures:'{"list":[],"cols":2,"picture_size":"cover"}',
  avatar:'{"picture":"https://...","size":"md"}',
  'digitals-product':'{"items":[]}',
  zero:'{}',
};
const BT_LIST=[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,20,21,22,36,50,51];
const RU_MAP={а:'a',б:'b',в:'v',г:'g',д:'d',е:'e',ё:'yo',ж:'zh',з:'z',и:'i',й:'y',к:'k',л:'l',м:'m',н:'n',о:'o',п:'p',р:'r',с:'s',т:'t',у:'u',ф:'f',х:'h',ц:'ts',ч:'ch',ш:'sh',щ:'sch',ъ:'',ы:'y',ь:'',э:'e',ю:'yu',я:'ya'};
function slugify(s){return s.toLowerCase().split('').map(c=>RU_MAP[c]||c).join('').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,80);}
function colorPickerHex(c){const m=String(c||'').match(/^#([0-9a-f]{6})([0-9a-f]{2})?$/i);return m?'#'+m[1].toLowerCase():'#ffffff';}
function colorPickerMerge(picked,current){const m=String(current||'').match(/^#[0-9a-f]{6}([0-9a-f]{2})$/i);return picked.toLowerCase()+(m?m[1]:'ff');}
function loadGoogleFont(font){
  if(!font)return;
  const id='gf-'+font.replace(/\s+/g,'-');
  if(document.getElementById(id))return;
  const l=document.createElement('link');
  l.id=id;l.rel='stylesheet';
  l.href='https://fonts.googleapis.com/css2?family='+encodeURIComponent(font)+':wght@400;600;700&display=swap';
  document.head.appendChild(l);
}
function txtSize(s){return{h1:'50px',h2:'30px',h3:'24px',lg:'20px',md:'17px',sm:'14px'}[s]||'17px';}
function txtWeight(s,bold){return bold?'700':'400';}
function txtLineHeight(s){return{h1:'57.5px',h2:'37.5px',h3:'33.6px',lg:'29px',md:'24.65px',sm:'20.3px'}[s]||'1.45';}
