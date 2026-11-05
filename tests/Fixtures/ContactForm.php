<?php

namespace Zuber\Kit\Tests\Fixtures;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;
use Zuber\Kit\Core\Filament\Forms\Fields\PhoneInput;
use Zuber\Kit\Modules\Spam\HoneypotField;
use Zuber\Kit\Modules\Spam\UsesSpamProtection;

/**
 * Публичная форма сайта для тестов: телефон и защита от спама.
 */
final class ContactForm extends Component implements HasSchemas
{
    use InteractsWithSchemas;
    use UsesSpamProtection;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $sent = null;

    public bool $thanked = false;

    public function mount(): void
    {
        $this->getSchema('form')?->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('name')->required(),
                PhoneInput::make()->required(),
                HoneypotField::make(),
            ]);
    }

    public function submit(): void
    {
        $state = $this->getSchema('form')?->getState() ?? [];
        $this->thanked = true;

        if ($this->isSpam()) {
            return;
        }

        $this->sent = $state;
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>{{ $this->form }}</div>
            BLADE;
    }
}
