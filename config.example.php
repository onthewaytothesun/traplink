<?php
// Скопируйте в config.php и заполните. config.php в git не попадает,
// а .htaccess не отдаёт его из браузера.
return [
    // База MySQL (создаётся в панели хостинга)
    'db_host' => 'localhost',
    'db_name' => '',
    'db_user' => '',
    'db_pass' => '',

    // Вход в админку /admin. Пустые значения означают admin / admin — задайте свои.
    'admin_login'    => '',
    'admin_password' => '',
];
