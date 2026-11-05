<?php

namespace Zuber\Kit\Modules\Leads;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Zuber\Kit\Modules\Leads\Livewire\LeadForm;
use Zuber\Kit\Modules\Leads\Livewire\LeadModal;

/**
 * Заявки, если проект их включил (kit.leads.enabled): форма kit-lead-form, окно kit-lead-modal, миграции.
 */
final class LeadsServiceProvider extends ServiceProvider
{
    public function boot(Repository $config): void
    {
        if ($this->app->runningInConsole()) {
            $migrations = __DIR__ . '/../../../database/migrations';

            $this->publishesMigrations([
                $migrations . '/2026_11_05_000003_create_lead_forms_table.php' => database_path('migrations/2026_11_05_000003_create_lead_forms_table.php'),
                $migrations . '/2026_11_05_000004_create_leads_table.php' => database_path('migrations/2026_11_05_000004_create_leads_table.php'),
            ], 'kit-leads-migrations');
        }

        if ($config->get('kit.leads.enabled') === true && class_exists(Livewire::class)) {
            Livewire::component('kit-lead-form', LeadForm::class);
            Livewire::component('kit-lead-modal', LeadModal::class);
        }
    }
}
