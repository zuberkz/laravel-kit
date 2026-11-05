<?php

namespace Zuber\Kit\Users\Filament\Resources\Users\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Js;

/**
 * Копирует пароль из поля. Стандартная кнопка Filament (copyable) пишет только через
 * navigator.clipboard, а его нет на http-адресе, кроме localhost (локальный домен, тестовый стенд):
 * там кнопка молча не работала. Без clipboard API — копирование через временное текстовое поле.
 */
final class CopyPasswordAction extends Action
{
    public static function getDefaultName(): string
    {
        return 'copyPassword';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Скопировать'));
        $this->tooltip(__('Скопировать пароль'));
        $this->icon(Heroicon::OutlinedClipboardDocument);
        $this->defaultColor('gray');

        $copied = Js::from(__('Пароль скопирован'));
        $empty = Js::from(__('Сначала задайте пароль'));

        $this->alpineClickHandler(<<<JS
            const value = \$el.closest('.fi-input-wrp').querySelector('input').value
            const notify = (message) => \$tooltip(message, { theme: \$store.theme, timeout: 2000 })

            if (! value) {
                notify({$empty})

                return
            }

            if (window.navigator.clipboard && window.isSecureContext) {
                window.navigator.clipboard.writeText(value).then(() => notify({$copied}))

                return
            }

            const area = document.createElement('textarea')
            area.value = value
            area.setAttribute('readonly', '')
            area.style.position = 'fixed'
            area.style.opacity = '0'
            document.body.appendChild(area)
            area.select()
            document.execCommand('copy')
            area.remove()
            notify({$copied})
            JS);
    }
}
