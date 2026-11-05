<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            // Адрес страницы с ведущим слешем: / — главная, /services/cleaning; unique — и поиск по адресу
            $table->string('path')->unique();
            $table->string('status')->default('draft');
            // Блоки конструктора: [{type, data}], общие блоки — {type: shared, data: {shared_block_id}}
            $table->json('blocks')->nullable();
            $table->boolean('show_header')->default(true);
            $table->boolean('show_footer')->default(true);
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('og_image')->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
