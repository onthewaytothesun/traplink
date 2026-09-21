<?php
// Presets are immutable documents. Add a new entry here to extend the catalog.
$presets = [];
$make = static function(string $key, string $title, string $description, array $design, array $content) use (&$presets): void {
    $id = 'builtin-' . $key;
    $blocks = [];
    foreach ($content as $i=>$block) {
        [$typeId,$type,$options] = $block;
        $blocks[] = ['id'=>$key.'-b'.$i,'section_id'=>null,'block_type_id'=>$typeId,'block_type_name'=>$type,'options'=>$options,'is_visible'=>1,'sort_order'=>($i+1)*10,'anchor'=>$block[3]??null];
    }
    $presets[$id] = ['id'=>$id,'title'=>$title,'description'=>$description,'kind'=>'builtin','snapshot'=>[
        'version'=>1,'page'=>['id'=>$key.'-page','title'=>$title,'slug'=>'','theme'=>['_template_design'=>array_replace(['screen'=>'#ffffff','text_color'=>'#242424','link_bg'=>'#242424','link_color'=>'#ffffff','link_radius'=>12,'link_border_width'=>0,'link_shadow'=>'none','page_font'=>''],$design)]],
        'sections'=>[], 'blocks'=>$blocks,
    ]];
};
$text = static fn($text,$size='md',$align='left') => [1,'text',['text'=>str_replace('\n', "\n", $text),'text_size'=>$size,'text_align'=>$align]];
$link = static fn($title,$href='#contact') => [2,'link',['title'=>$title,'value'=>$href,'action'=>'website']];
$space = static fn($height=24) => [5,'break',['height'=>$height]];
$make('profile','Личная визитка','Знакомство, ваши услуги и быстрый способ связаться.', ['screen'=>'#f5f0e8','text_color'=>'#323129','link_bg'=>'#575c45'], [
    $space(32),$text('ЗНАКОМСТВО','sm'),$text('Анна Иванова','h1'),$text('Помогаю превращать идеи в понятные решения.','lg'),$space(),
    $text('Обо мне','h3'),$text('Здесь расскажите о себе: чем занимаетесь, кому помогаете и почему вам доверяют.'),$space(),
    $link('Посмотреть услуги','#services'),[1,'text',['text'=>'Чем я могу помочь','text_size'=>'h2'],'services'],
    [20,'media',['fields'=>[['title'=>'Консультация','text'=>'Разберём вашу задачу и составим план действий.'],['title'=>'Работа над проектом','text'=>'От первой идеи до готового результата.']]]],
    $space(),[1,'text',['text'=>'Давайте познакомимся','text_size'=>'h2'],'contact'],$text('Добавьте свой адрес электронной почты или ссылку на мессенджер в кнопку ниже.'),$link('Написать мне','mailto:hello@example.com'),$space(32),
]);
$make('services','Услуги специалиста','Предложение, стоимость, частые вопросы и форма заявки.', ['screen'=>'#f3f6fa','link_bg'=>'#245b80'], [
    $space(28),$text('ВАШЕ ДЕЛО — МОЯ ЭКСПЕРТИЗА','sm'),$text('Освободите время\nдля главного','h1'),$text('Расскажите, какую задачу клиента вы решаете и какой результат он получит.','lg'),$link('Обсудить задачу'),$space(),
    $text('Форматы работы','h2'),[21,'pricing',['fields'=>[['title'=>'Знакомство и диагностика','price'=>1500],['title'=>'Индивидуальная консультация','price'=>5000],['title'=>'Сопровождение проекта','price'=>25000]]]],$space(),
    $text('Вопросы и ответы','h2'),[14,'collapse',['fields'=>[['title'=>'Как начать работу?','text'=>'Оставьте заявку ниже. Мы свяжемся с вами и уточним детали.'],['title'=>'Можно ли работать онлайн?','text'=>'Да, встречи проходят в удобном для вас формате.']]]],
    [1,'text',['text'=>'Расскажите о вашей задаче','text_size'=>'h2'],'contact'],[10,'form',['fields'=>[['idx'=>0,'type_id'=>3,'title'=>'Ваше имя','required'=>true],['idx'=>1,'type_id'=>6,'title'=>'Email','required'=>true]],'form_btn'=>'Оставить заявку']],$space(28),
]);
$make('course','Образовательный курс','Обложка курса, программа, тарифы и ответы на вопросы.', ['screen'=>'#f1effa','text_color'=>'#29233e','link_bg'=>'#64519b'], [
    $space(32),$text('ОНЛАЙН-КУРС · В ВАШЕМ ТЕМПЕ','sm'),$text('Новый навык.\nНовые возможности.','h1'),$text('Название курса и главное обещание: чему научится участник.','lg'),$link('Посмотреть программу','#program'),$space(),
    [1,'text',['text'=>'От основ к практике','text_size'=>'h2'],'program'],[14,'collapse',['fields'=>[['title'=>'01 · Основы','text'=>'Ключевые понятия, инструменты и первая практика.','opened'=>true],['title'=>'02 · Практика','text'=>'Реальные задачи с пошаговым разбором.'],['title'=>'03 · Свой проект','text'=>'Примените знания и получите готовый результат.']]]],$space(),
    $text('Выберите свой формат','h2'),[50,'plans',['fields'=>[['title'=>'Самостоятельно','price'=>'4 900 ₽','description'=>'Уроки, задания и материалы.'],['title'=>'С поддержкой','price'=>'9 900 ₽','description'=>'Всё из базового тарифа и обратная связь.']]]],
    [1,'text',['text'=>'Остались вопросы?','text_size'=>'h2'],'contact'],$text('Укажите свои контакты и условия участия перед публикацией.'),$link('Задать вопрос','mailto:course@example.com'),$space(32),
]);
$make('portfolio','Портфолио','Представление, избранные проекты и приглашение к сотрудничеству.', ['screen'=>'#f4f4f1','text_color'=>'#212621','link_bg'=>'#212621','link_radius'=>4], [
    $space(36),$text('ДИЗАЙНЕР · АВТОР · СОЗДАТЕЛЬ','sm'),$text('Работы,\nкоторые говорят','h1'),$text('Ваше имя / специализация / город','lg'),$space(32),
    $text('Избранные проекты','h2'),$text('01 / Название проекта','h3'),$text('Задача, ваша роль и результат. Замените этот текст описанием своего проекта.'),$link('Подробнее о проекте','#project-details'),$space(),
    [1,'text',['text'=>'02 / Следующая история','text_size'=>'h3'],'project-details'],$text('Добавьте изображения, контекст и детали процесса через редактор блоков.'),$space(32),
    [1,'text',['text'=>'Создадим что-то вместе','text_size'=>'h2'],'contact'],$link('Обсудить проект','mailto:design@example.com'),$space(36),
]);
return $presets;
