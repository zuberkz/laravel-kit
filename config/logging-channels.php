<?php

// Каналы логов, которые пакет добавляет в logging.channels; канал с тем же именем в config/logging.php
// проекта важнее — там же меняется срок хранения.
return [
    // Отказы по бизнес-правилам (DomainException): не ошибки, а статистика — отдельно от laravel.log
    'domain' => [
        'driver' => 'daily',
        'path' => storage_path('logs/domain.log'),
        'level' => 'info',
        'max_files' => 30,
        'replace_placeholders' => true,
    ],
];
