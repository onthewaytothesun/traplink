# timeweb / one-way.dev

Сайт-конструктор на шаред-хостинге Timeweb. Фронтенд-заглушка (Vue) + PHP-бэкенд с админкой блоков.

## Сервер

- Хост: `vh340.timeweb.ru`, пользователь `cc26299`
- SSH алиас: `timeweb` (см. `~/.ssh/config`)
- Веб-корень: `~/public_html/`
- Живой адрес: **https://timeweb.one-way.dev**
- PHP 8.1, MySQL (localhost)

## Деплой

Файлы деплоятся вручную через `scp`:

```bash
scp local/file.php timeweb:~/public_html/file.php
```

Локальная копия бэкенда: `/Users/antonfedorov/Documents/Projects/timeweb/`
Vue-дашборд: `vue/dashboard/` (Vite + Vue 3 + Tailwind)

## Структура на сервере

```
~/public_html/
├── index.php          # Точка входа: рендерит главную страницу из БД или фоллбек на index.html
├── index.html         # Старый Vue-дашборд (фоллбек если главная страница не задана)
├── pub.php            # Рендерит страницы по /p/{slug}
├── page.php           # Общий рендерер блоков (include в index.php и pub.php)
├── config.php         # Конфиг БД и логин/пароль админки (закрыт .htaccess)
├── .htaccess          # DirectoryIndex + роутинг /p/slug → pub.php
└── admin/
    ├── index.php      # Админка (логин + SPA на Alpine.js + Tailwind CDN)
    └── api.php        # JSON API для CRUD операций
```

## БД

- База: `cc26299_tap`
- Хост: `localhost`
- Логин/пароль: в `config.php` (вне веб-корня не работает из-за прав Apache — файл лежит в `public_html/config.php` и закрыт в `.htaccess`)

### Таблицы

**`tap_pages`** — страницы сайта
| поле | тип | описание |
|------|-----|----------|
| id | varchar(64) | `p-{hex}` |
| title | varchar(255) | название |
| slug | varchar(128) | URL-идентификатор (`/p/{slug}`) |
| is_main | tinyint(1) | главная страница (открывается по `/`) |
| sort_order | int | порядок |

**`tap_sections`** — секции внутри страниц
| поле | тип | описание |
|------|-----|----------|
| id | varchar(64) | `s-{hex}` |
| page_id | varchar(64) | FK → tap_pages |
| title | varchar(255) | название |
| sort_order | int | порядок |

**`tap_blocks`** — блоки контента
| поле | тип | описание |
|------|-----|----------|
| id | varchar(64) | `{hex}` |
| page_id | varchar(64) | FK → tap_pages |
| section_id | varchar(64) | FK → tap_sections (необязательно) |
| block_type_id | int | числовой тип (1–51) |
| block_type_name | varchar(64) | строковый тип (text, link, ...) |
| options | longtext | JSON с данными блока |
| is_visible | tinyint(1) | видимость |
| sort_order | int | порядок |
| anchor | varchar(128) | якорь для навигации |

## Роутинг

```
/            → index.php  → главная страница из БД (или index.html если не задана)
/p/{slug}    → pub.php    → страница по slug
/admin/      → admin/index.php → админка
/admin/api.php?action=... → JSON API
```

## Админка

URL: https://timeweb.one-way.dev/admin
Логин/пароль: в `config.php` (ADMIN_LOGIN / ADMIN_PASSWORD)

Стек: PHP-сессия для авторизации, Alpine.js 3 + Tailwind CSS CDN (без сборки).

### API actions (POST/GET к `/admin/api.php`)

| action | метод | описание |
|--------|-------|----------|
| pages | GET | список страниц |
| addPage | POST | создать страницу |
| updatePage | POST | обновить title/slug |
| setMain | POST | пометить как главную (снимает флаг с остальных) |
| deletePage | POST | удалить страницу |
| sections?page_id= | GET | секции страницы |
| addSection | POST | создать секцию |
| deleteSection | POST | удалить секцию |
| blocks?page_id= | GET | блоки страницы |
| save | POST | создать или обновить блок |
| delete | POST | удалить блок |
| toggle | POST | переключить is_visible |
| reorder | POST | изменить sort_order (массив ids) |

## Типы блоков

Документация по структуре options: `/Users/antonfedorov/Downloads/phpshtormprojects/taplinkMovingOut/blocks.md`

Поддерживаемые типы и их рендеринг в `page.php`:

| id | name | рендеринг |
|----|------|-----------|
| 1 | text | `<p>` / `<h1>`–`<h3>`, выравнивание |
| 2 | link | кнопка-ссылка, 3 стиля |
| 3 | messenger | ссылки на мессенджеры (Telegram, WhatsApp, Viber...) |
| 4 | video | YouTube/Vimeo embed |
| 5 | break | разделитель с высотой |
| 6 | socialnetworks | иконки соцсетей |
| 7 | html | произвольный HTML |
| 8 | avatar | круглое фото |
| 9 | pictures | — (JSON editor) |
| 10 | form | — (JSON editor) |
| 11 | page | — (JSON editor) |
| 12 | map | Google Maps embed |
| 13 | timer | обратный отсчёт с JS |
| 14 | collapse | FAQ-аккордеон (`<details>`) |
| 15 | banner | баннер с картинкой и ссылкой |
| 20 | media | список иконка+текст |
| 21 | pricing | прайс-лист |
| 22 | music | — (JSON editor) |
| 36 | digitals-product | — (JSON editor) |
| 50 | plans | — (JSON editor) |
| 51 | zero | произвольный HTML (как html) |

Блоки без рендерера редактируются через JSON-поле в админке.

## Vue дашборд (заглушка)

Путь: `vue/dashboard/`
Стек: Vue 3, Vite, Tailwind CSS, Radix Vue, Lucide
Сборка: `npm run build` → `dist/`
Деплой dist: `scp -r dist/* timeweb:~/public_html/` (перезапишет assets и index.html)

> Показывается только если в БД нет страницы с `is_main=1`
