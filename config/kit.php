<?php

// Настройки кита; проект меняет нужные ключи в своём config/kit.php (ключи первого уровня целиком).
return [
    // Права — в коде (PermissionEnum), роли — в базе: их заводят и правят в админке. kit:sync-roles
    // создаёт права из enum, недостающие роли из RoleEnum с правами по умолчанию (permissions()) и
    // выдаёт супер-администратору все права; права остальных ролей не трогает
    'access' => [
        'roles' => 'App\\Enums\\Access\\RoleEnum',
        'permissions' => 'App\\Enums\\Access\\PermissionEnum',
        'super_admin' => 'super_admin',
    ],

    // Заголовки безопасности на каждый ответ; HSTS — только на проде по https
    'security_headers' => [
        'enabled' => true,
        'hsts_max_age' => 31536000,
        // includeSubDomains — https для всех поддоменов домена сайта. Включать, только когда у
        // каждого поддомена (почта, старые сервисы, тестовые копии) есть https: браузер не откроет их по http
        'hsts_include_subdomains' => false,
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

    // Заявки с сайта: mail_to — адреса для уведомлений (список или строка через запятую из .env),
    // per_minute — заявок с одного IP в минуту
    'leads' => [
        'enabled' => false,
        'mail_to' => [],
        'per_minute' => 5,
    ],
];
