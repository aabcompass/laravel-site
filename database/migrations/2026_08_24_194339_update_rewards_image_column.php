<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Rewards', function (Blueprint $table) {
            // Удаляем старое поле для пути к файлу
            if (Schema::hasColumn('Rewards', 'image_path')) {
                $table->dropColumn('image_path');
            }
            // Добавляем текстовое поле для хранения кода SVG
            $table->longText('svg_content')->nullable()->comment('Код SVG изображения');
        });
    }

    public function down(): void
    {
        Schema::table('Rewards', function (Blueprint $table) {
            $table->string('image_path')->nullable();
            $table->dropColumn('svg_content');
        });
    }
};