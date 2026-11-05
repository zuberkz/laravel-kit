<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_blocks', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type', 60);
            $table->json('data')->nullable();
            // Шапка и подвал сайта: каждая позиция — не больше одного блока; unique — и поиск по позиции
            $table->string('position', 20)->nullable()->unique();
            $table->timestamps();
        });

        // Какие страницы ссылаются на общий блок: «используется на N страницах» и запрет удалить используемый
        Schema::create('page_shared_block', function (Blueprint $table): void {
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shared_block_id')->constrained()->cascadeOnDelete();

            $table->primary(['page_id', 'shared_block_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_shared_block');
        Schema::dropIfExists('shared_blocks');
    }
};
