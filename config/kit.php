<?php

// Настройки кита; проект меняет нужные ключи в своём config/kit.php (ключи первого уровня целиком).
return [
    // Роли и права: enum ролей с permissions(), из него kit:sync-roles раскладывает базу
    'access' => [
        'roles' => 'App\\Enums\\Access\\RoleEnum',
    ],

    // Заголовки безопасности на каждый ответ; HSTS — только на проде по https
    'security_headers' => [
        'enabled' => true,
        'hsts_max_age' => 31536000,
        'frame_options' => 'SAMEORIGIN',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'permissions_policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()',
    ],

    // Публичные формы: заполненная быстрее этого форма — бот
    'spam' => [
        'min_seconds' => 3,
    ],

    // robots.txt и sitemap.xml из маршрутов кита; disallow — дополнительные закрытые пути на проде
    'seo' => [
        'routes' => true,
        'robots_disallow' => [],
    ],

    // Конструктор страниц (enabled — проект его подключил): blocks — null (классы из app/Blocks) или
    // список классов; views — каталог шаблонов блоков (null — resources/views/blocks); layout —
    // Blade-компонент страницы сайта; картинки блоков — на disk в image_directory
    'pages' => [
        'enabled' => false,
        'blocks' => null,
        'views' => null,
        'layout' => 'layouts.app',
        'disk' => 'public',
        'image_directory' => 'blocks',
    ],
];
