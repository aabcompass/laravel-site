<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Rewards', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Секретный ключ или артикул (например, HE-4)');
            $table->string('name')->comment('Название (Гелий)');
            $table->string('symbol_latex')->nullable()->comment('Символ LaTeX (^{4}_{2}He)');
            $table->string('image_path')->nullable()->comment('Путь к SVG/PNG картинке');
            
            // Описания
            $table->text('physical_desc')->nullable()->comment('Физическое описание (Впервые обнаружен на Солнце...)');
            $table->text('public_desc')->nullable()->comment('Публичное описание (За что дается)');
            $table->text('private_desc')->nullable()->comment('Приватное описание (Инструкция для учителя)');
            $table->text('perks')->nullable()->comment('Преференции (например, +1 балл к контрольной)');
            
            // Флаги и настройки
            $table->boolean('is_for_answer')->default(false)->comment('Выдается на лету за устный ответ');
            $table->string('carrier_type')->nullable()->comment('Тип физического носителя (Магнит, Брелок, Ручка и т.д.)');
            $table->boolean('requires_registration')->default(true)->comment('Требуется ли запись в базу при выдаче');
            
            // Физические параметры
            $table->integer('z_number')->nullable()->comment('Зарядовое число (Z)');
            $table->integer('a_number')->nullable()->comment('Массовое число (A)');

            // Дата создания типа награды (не путать с датой выдачи)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Rewards');
    }
};