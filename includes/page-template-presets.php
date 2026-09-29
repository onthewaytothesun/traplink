<?php
// Presets are immutable documents. Add a new entry here to extend the catalog.
$presets = [];
// $category must be a key of PageTemplateService::CATEGORIES.
$make = static function(string $key, string $category, string $title, string $description, array $design, array $content) use (&$presets): void {
    $id = 'builtin-' . $key;
    $blocks = [];
    foreach ($content as $i=>$block) {
        [$typeId,$type,$options] = $block;
        $blocks[] = ['id'=>$key.'-b'.$i,'section_id'=>null,'block_type_id'=>$typeId,'block_type_name'=>$type,'options'=>$options,'is_visible'=>1,'sort_order'=>($i+1)*10,'anchor'=>$block[3]??null];
    }
    $presets[$id] = ['id'=>$id,'title'=>$title,'description'=>$description,'category'=>$category,'kind'=>'builtin','snapshot'=>[
        'version'=>1,'page'=>['id'=>$key.'-page','title'=>$title,'slug'=>'','theme'=>['_template_design'=>array_replace(['screen'=>'#ffffff','text_color'=>'#242424','link_bg'=>'#242424','link_color'=>'#ffffff','link_radius'=>12,'link_border_width'=>0,'link_shadow'=>'none','page_font'=>''],$design)]],
        'sections'=>[], 'blocks'=>$blocks,
    ]];
};
$text = static fn($text,$size='md',$align='left') => [1,'text',['text'=>str_replace('\n', "\n", $text),'text_size'=>$size,'text_align'=>$align]];
$link = static fn($title,$href='#contact') => [2,'link',['title'=>$title,'value'=>$href,'action'=>'website']];
$space = static fn($height=24) => [5,'break',['height'=>$height]];
$make('profile','personal','Личная визитка','Знакомство, ваши услуги и быстрый способ связаться.', ['screen'=>'#f5f0e8','text_color'=>'#323129','link_bg'=>'#575c45'], [
    $space(32),[8,'avatar',['picture'=>'/uploads/tpl-profile-avatar.jpg','size'=>'lg']],
    $text('ЗНАКОМСТВО','sm','center'),$text('Анна Иванова','h1','center'),$text('Помогаю превращать идеи в понятные решения.','lg','center'),$space(),
    [15,'banner',['picture'=>'/uploads/tpl-profile-banner.jpg','link'=>'']],
    $space(),$text('Обо мне','h3'),$text('Здесь расскажите о себе: чем занимаетесь, кому помогаете и почему вам доверяют.'),$space(),
    $link('Посмотреть услуги','#services'),[1,'text',['text'=>'Чем я могу помочь','text_size'=>'h2'],'services'],
    [20,'media',['fields'=>[['title'=>'Консультация','text'=>'Разберём вашу задачу и составим план действий.'],['title'=>'Работа над проектом','text'=>'От первой идеи до готового результата.']]]],
    $space(),[1,'text',['text'=>'Давайте познакомимся','text_size'=>'h2'],'contact'],$text('Добавьте свой адрес электронной почты или ссылку на мессенджер в кнопку ниже.'),$link('Написать мне','mailto:hello@example.com'),$space(32),
]);
$make('services','services','Услуги специалиста','Предложение, стоимость, частые вопросы и форма заявки.', ['screen'=>'#f3f6fa','link_bg'=>'#245b80'], [
    [15,'banner',['picture'=>'/uploads/tpl-services-hero.jpg','link'=>'']],
    $space(28),$text('ВАШЕ ДЕЛО — МОЯ ЭКСПЕРТИЗА','sm','center'),$text('Освободите время\nдля главного','h1','center'),$text('Расскажите, какую задачу клиента вы решаете и какой результат он получит.','lg','center'),$link('Обсудить задачу'),$space(),
    [15,'banner',['picture'=>'/uploads/tpl-services-deco.jpg','link'=>'']],$space(),
    $text('Форматы работы','h2'),[21,'pricing',['fields'=>[['title'=>'Знакомство и диагностика','price'=>1500],['title'=>'Индивидуальная консультация','price'=>5000],['title'=>'Сопровождение проекта','price'=>25000]],'currency'=>'₽']],$space(),
    $text('Вопросы и ответы','h2'),[14,'collapse',['fields'=>[['title'=>'Как начать работу?','text'=>'Оставьте заявку ниже. Мы свяжемся с вами и уточним детали.'],['title'=>'Можно ли работать онлайн?','text'=>'Да, встречи проходят в удобном для вас формате.']]]],
    [1,'text',['text'=>'Расскажите о вашей задаче','text_size'=>'h2'],'contact'],[10,'form',['fields'=>[['idx'=>0,'type_id'=>3,'title'=>'Ваше имя','required'=>true],['idx'=>1,'type_id'=>6,'title'=>'Email','required'=>true]],'form_btn'=>'Оставить заявку']],$space(28),
]);
$make('course','education','Образовательный курс','Обложка курса, программа, тарифы и ответы на вопросы.', ['screen'=>'#f1effa','text_color'=>'#29233e','link_bg'=>'#64519b'], [
    [15,'banner',['picture'=>'/uploads/tpl-course-hero.jpg','link'=>'']],
    $space(28),$text('ОНЛАЙН-КУРС · В ВАШЕМ ТЕМПЕ','sm','center'),$text('Новый навык.\nНовые возможности.','h1','center'),$text('Название курса и главное обещание: чему научится участник.','lg','center'),$link('Посмотреть программу','#program'),$space(),
    [15,'banner',['picture'=>'/uploads/tpl-course-deco.jpg','link'=>'']],
    $space(),[1,'text',['text'=>'От основ к практике','text_size'=>'h2'],'program'],[14,'collapse',['fields'=>[['title'=>'01 · Основы','text'=>'Ключевые понятия, инструменты и первая практика.','opened'=>true],['title'=>'02 · Практика','text'=>'Реальные задачи с пошаговым разбором.'],['title'=>'03 · Свой проект','text'=>'Примените знания и получите готовый результат.']]]],$space(),
    $text('Выберите свой формат','h2'),[50,'plans',['fields'=>[['title'=>'Самостоятельно','price'=>'4 900 ₽','description'=>'Уроки, задания и материалы.'],['title'=>'С поддержкой','price'=>'9 900 ₽','description'=>'Всё из базового тарифа и обратная связь.']]]],
    [1,'text',['text'=>'Остались вопросы?','text_size'=>'h2'],'contact'],$text('Укажите свои контакты и условия участия перед публикацией.'),$link('Задать вопрос','mailto:course@example.com'),$space(32),
]);
$make('portfolio','portfolio','Портфолио','Представление, избранные проекты и приглашение к сотрудничеству.', ['screen'=>'#f4f4f1','text_color'=>'#212621','link_bg'=>'#212621','link_radius'=>4], [
    [15,'banner',['picture'=>'/uploads/tpl-portfolio-hero.jpg','link'=>'']],
    $space(32),$text('ДИЗАЙНЕР · АВТОР · СОЗДАТЕЛЬ','sm'),$text('Работы,\nкоторые говорят','h1'),$text('Ваше имя / специализация / город','lg'),$space(32),
    $text('Избранные проекты','h2'),$text('01 / Название проекта','h3'),[15,'banner',['picture'=>'/uploads/tpl-portfolio-project1.jpg','link'=>'']],$text('Задача, ваша роль и результат. Замените этот текст описанием своего проекта.'),$link('Подробнее о проекте','#project-details'),$space(),
    [1,'text',['text'=>'02 / Следующая история','text_size'=>'h3'],'project-details'],[15,'banner',['picture'=>'/uploads/tpl-portfolio-project2.jpg','link'=>'']],$text('Добавьте изображения, контекст и детали процесса через редактор блоков.'),$space(32),
    [1,'text',['text'=>'Создадим что-то вместе','text_size'=>'h2'],'contact'],$link('Обсудить проект','mailto:design@example.com'),$space(36),
]);
// Compact link pages and a local business layout, with generated photography.
$make('multilink','links','Простая мультиссылка','Аватар, короткое описание и все важные ссылки на одном экране.', ['screen'=>'#faf7f2','text_color'=>'#39362f','link_bg'=>'#39362f','link_radius'=>24], [
    $space(28),[8,'avatar',['picture'=>'/uploads/tpl-multilink-generated.jpg']],
    $text('Ваше имя','h2','center'),$text('Делаю любимое дело и делюсь полезным.\nВсе мои ссылки — здесь.','md','center'),$space(12),
    [2,'link',['title'=>'Мой сайт','value'=>'https://example.com','icon'=>'globe']],
    [2,'link',['title'=>'Telegram','subtitle'=>'Мысли, новости и закулисье','value'=>'https://example.com/telegram','icon'=>'telegram']],
    [2,'link',['title'=>'Мои проекты','value'=>'https://example.com/projects','icon'=>'layers']],
    [2,'link',['title'=>'Написать мне','value'=>'mailto:hello@example.com','icon'=>'email']],
    $space(20),$text('Рада знакомству ♡','sm','center'),$space(24),
]);
$make('creator','media','Автор и соцсети','Обложка, свежий материал, каналы и контакты для сотрудничества.', ['screen'=>'#fff4ed','text_color'=>'#3d302c','link_bg'=>'#a44c32','link_radius'=>14], [
    [15,'banner',['picture'=>'/uploads/tpl-creator-generated.jpg','link'=>'']],
    $space(24),$text('ПРИВЕТ, Я САША','sm','center'),$text('Создаю. Рассказываю.\nВдохновляю.','h2','center'),$text('Про творчество, маленькие открытия и жизнь за кадром.','md','center'),$space(20),
    [2,'link',['title'=>'Свежий выпуск','subtitle'=>'С чего начать свой творческий проект','value'=>'https://example.com/latest','icon'=>'youtube']],
    [2,'link',['title'=>'Мой Telegram','subtitle'=>'То, что не вошло в видео','value'=>'https://example.com/telegram','icon'=>'telegram']],
    [2,'link',['title'=>'Полезные материалы','subtitle'=>'Подборки, заметки и чек-листы','value'=>'https://example.com/resources','icon'=>'book']],
    $space(24),$text('Давайте создавать вместе','h3','center'),$text('Открыта к интересным проектам и сотрудничеству.','md','center'),
    [2,'link',['title'=>'Предложить сотрудничество','value'=>'mailto:collab@example.com','icon'=>'email']],$space(24),
]);
$make('cafe','business','Кофейня','Фото, меню, часы работы и контакты маленького любимого места.', ['screen'=>'#f7f3ea','text_color'=>'#233a31','link_bg'=>'#285745','link_radius'=>10], [
    [15,'banner',['picture'=>'/uploads/tpl-cafe-generated.jpg','link'=>'']],
    $space(28),$text('КОФЕЙНЯ У ДОМА','sm','center'),$text('Тёплое место','h1','center'),$text('Хороший кофе, свежая выпечка\nи время для себя.','lg','center'),$space(16),
    $link('Посмотреть меню','#menu'),$link('Как нас найти','#visit'),$space(28),
    [1,'text',['text'=>'Ваш любимый заказ','text_size'=>'h2'],'menu'],
    [21,'pricing',['fields'=>[['title'=>'Эспрессо','price'=>180],['title'=>'Капучино','price'=>260],['title'=>'Флэт уайт','price'=>290],['title'=>'Матча-латте','price'=>320],['title'=>'Круассан','price'=>240]],'currency'=>'₽']],$space(28),
    [1,'text',['text'=>'Заглядывайте в гости','text_size'=>'h2'],'visit'],
    $text('Ваш город, улица и номер дома\nПн–Пт: 08:00–21:00 · Сб–Вс: 09:00–21:00'),
    [2,'link',['title'=>'Забронировать столик','value'=>'mailto:cafe@example.com','icon'=>'email']],
    $space(20),$text('Здесь всегда рады вам.','sm','center'),$space(28),
]);
return $presets;
