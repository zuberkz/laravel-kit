<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_forms', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            // Поля формы из конструктора Filament: [{type, data: {key, label, required, …}}]
            $table->json('fields');
            $table->string('button_label')->nullable();
            $table->string('success_message', 500)->nullable();
            // Свои адреса для писем, через запятую; пусто — общие из kit.leads.mail_to
            $table->string('mail_to')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_forms');
    }
};
