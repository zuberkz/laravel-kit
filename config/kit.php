<?php

// Настройки кита; проект меняет нужные ключи в своём config/kit.php (ключи первого уровня целиком).
return [
    // Роли и права: enum ролей с permissions(), из него kit:sync-roles раскладывает базу
    'access' => [
        'roles' => 'App\\Enums\\Access\\RoleEnum',
    ],
];
