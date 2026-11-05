<?php

namespace Zuber\Kit\Leads\Repositories;

use Zuber\Kit\Leads\Models\LeadForm;

final readonly class LeadFormRepository
{
    public function find(int $id): ?LeadForm
    {
        return LeadForm::query()->find($id);
    }

    /**
     * Формы для выбора в блоке: id => название.
     *
     * @return array<int, string>
     */
    public function options(): array
    {
        /** @var array<int, string> */
        return LeadForm::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): LeadForm
    {
        return LeadForm::query()->create($attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(LeadForm $form, array $attributes): LeadForm
    {
        $form->update($attributes);

        return $form;
    }
}
