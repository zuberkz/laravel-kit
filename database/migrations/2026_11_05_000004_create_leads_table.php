<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            // Форма может быть удалена — заявка остаётся со снимком ответов
            $table->foreignId('lead_form_id')->nullable()->constrained()->nullOnDelete();
            // Контакты из полей типа «Имя», «Телефон», «Email» — для списка и «Позвонить»
            $table->string('name')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            // Снимок ответов: [{label, value}] — читается, даже если форму потом поменяли
            $table->json('answers');
            $table->string('source')->nullable();
            // Страница, с которой отправили заявку: заявки не зависят от конструктора страниц
            $table->string('url', 500)->nullable();
            $table->string('status', 20)->default('new');
            $table->timestamps();

            // Список заявок в админке: фильтр по статусу, новые сверху; счётчик новых в меню
            $table->index(['status', 'created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
