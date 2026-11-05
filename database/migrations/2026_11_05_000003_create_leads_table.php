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
            $table->string('name');
            $table->string('phone', 20);
            $table->text('message')->nullable();
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
