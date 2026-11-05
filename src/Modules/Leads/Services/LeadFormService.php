<?php

namespace Zuber\Kit\Modules\Leads\Services;

use Filament\Forms\Components\Field;
use Illuminate\Support\Str;
use Zuber\Kit\Modules\Leads\Data\LeadData;
use Zuber\Kit\Modules\Leads\Data\LeadFieldData;
use Zuber\Kit\Modules\Leads\Data\LeadFormData;
use Zuber\Kit\Modules\Leads\Data\LeadFormDefinitionData;
use Zuber\Kit\Modules\Leads\Enums\LeadFieldTypeEnum;
use Zuber\Kit\Modules\Leads\Exceptions\LeadFormWithoutContactException;
use Zuber\Kit\Modules\Leads\Models\LeadForm;
use Zuber\Kit\Modules\Leads\Repositories\LeadFormRepository;

/**
 * Формы заявок из админки: поля Filament для сайта, ответы для заявки, проверка формы при сохранении.
 */
final readonly class LeadFormService
{
    public function __construct(private LeadFormRepository $forms) {}

    /**
     * Форма для сайта; формы нет или её удалили — встроенная «Обратный звонок».
     */
    public function definition(?int $id): LeadFormDefinitionData
    {
        $form = $id === null ? null : $this->forms->find($id);

        return $form === null ? LeadFormDefinitionData::callback() : LeadFormDefinitionData::fromModel($form);
    }

    /**
     * @return array<int, Field>
     */
    public function components(LeadFormDefinitionData $definition): array
    {
        return collect($definition->fields)->map(fn (LeadFieldData $field): Field => $field->type->component($field))->all();
    }

    /**
     * Заявка из ответов: контакты — из первых полей «Имя», «Телефон», «Email», остальное — снимком.
     *
     * @param array<string, mixed> $state данные формы после валидации
     */
    public function lead(LeadFormDefinitionData $definition, array $state, ?string $source, ?string $url): LeadData
    {
        $answers = collect($definition->fields)
            ->map(fn (LeadFieldData $field): array => ['field' => $field, 'value' => $field->type->display($state[$field->key] ?? null)])
            ->filter(fn (array $answer): bool => $answer['value'] !== null);

        $first = fn (LeadFieldTypeEnum $type): ?string => $answers->first(fn (array $answer): bool => $answer['field']->type === $type)['value'] ?? null;
        $phone = collect($definition->fields)->first(fn (LeadFieldData $field): bool => $field->type === LeadFieldTypeEnum::PHONE);
        $rawPhone = $phone === null ? null : ($state[$phone->key] ?? null);

        return new LeadData(
            formId: $definition->id,
            answers: $answers->map(fn (array $answer): array => ['label' => $answer['field']->label, 'value' => (string) $answer['value']])->values()->all(),
            name: $first(LeadFieldTypeEnum::NAME),
            phone: is_string($rawPhone) && $rawPhone !== '' ? $rawPhone : null,
            email: $first(LeadFieldTypeEnum::EMAIL),
            source: $source === '' ? null : $source,
            url: $url,
        );
    }

    /**
     * @throws LeadFormWithoutContactException
     */
    public function create(LeadFormData $data): LeadForm
    {
        return $this->forms->create($this->attributes($data));
    }

    /**
     * @throws LeadFormWithoutContactException
     */
    public function update(LeadForm $form, LeadFormData $data): LeadForm
    {
        return $this->forms->update($form, $this->attributes($data));
    }

    /**
     * @throws LeadFormWithoutContactException
     *
     * @return array<string, mixed>
     */
    private function attributes(LeadFormData $data): array
    {
        $fields = $this->withKeys($data->fields);

        if (! collect($fields)->contains(fn (LeadFieldData $field): bool => $field->type->isContact())) {
            throw LeadFormWithoutContactException::make();
        }

        return [
            'name' => $data->name,
            'fields' => collect($fields)->map(fn (LeadFieldData $field): array => $field->toBuilderItem())->all(),
            'button_label' => $data->buttonLabel,
            'success_message' => $data->successMessage,
            'mail_to' => $data->mailTo,
        ];
    }

    /**
     * Ключ ответа — из подписи (транслитом), уникальный в форме; уже выданный ключ не меняется,
     * чтобы ответы старых заявок и правки подписи не расходились.
     *
     * @param  array<int, array<array-key, mixed>> $items
     * @return array<int, LeadFieldData>
     */
    private function withKeys(array $items): array
    {
        $used = [];

        return collect($items)
            ->map(function (array $item) use (&$used): ?LeadFieldData {
                $data = is_array($item['data'] ?? null) ? $item['data'] : [];
                $label = is_string($data['label'] ?? null) ? $data['label'] : '';
                $key = is_string($data['key'] ?? null) && $data['key'] !== '' ? $data['key'] : Str::slug($label, '_');
                $key = $key === '' ? 'field' : $key;
                $unique = $key;
                $number = 2;

                while (in_array($unique, $used, true)) {
                    $unique = $key . '_' . $number++;
                }

                $used[] = $unique;

                return LeadFieldData::fromBuilderItem([...$item, 'data' => [...$data, 'key' => $unique]]);
            })
            ->filter()
            ->values()
            ->all();
    }
}
