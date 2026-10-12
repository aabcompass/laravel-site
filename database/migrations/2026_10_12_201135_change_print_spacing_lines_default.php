<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Меняем значение по умолчанию на уровне структуры таблицы
        Schema::table('Work_Variants', function (Blueprint $table) {
            // В Laravel 11 изменение колонок работает из коробки
            $table->unsignedTinyInteger('print_spacing_lines')->default(0)->change();
        });

        // 2. Обновляем уже созданные варианты (меняем 1 на 0)
        // Если ты специально где-то ставил больше 1 (например 2 или 3), мы это не трогаем.
        DB::table('Work_Variants')->where('print_spacing_lines', 1)->update(['print_spacing_lines' => 0]);
    }

    public function down(): void
    {
        Schema::table('Work_Variants', function (Blueprint $table) {
            $table->unsignedTinyInteger('print_spacing_lines')->default(1)->change();
        });
    }
};