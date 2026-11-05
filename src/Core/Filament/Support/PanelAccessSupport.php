<?php

namespace Zuber\Kit\Core\Filament\Support;

use Filament\Facades\Filament;
use Illuminate\Auth\Access\Response;

/**
 * Доступ к разделу кита в панели: право из настроек плагина; без права — всем, кто вошёл в панель
 * (саму панель закрывает canAccessPanel модели пользователя).
 */
final class PanelAccessSupport
{
    public static function allows(?string $permission): bool
    {
        $user = Filament::auth()->user();

        if ($user === null) {
            return false;
        }

        return $permission === null || $user->can($permission);
    }

    public static function response(bool $allowed): Response
    {
        return $allowed ? Response::allow() : Response::deny();
    }
}
