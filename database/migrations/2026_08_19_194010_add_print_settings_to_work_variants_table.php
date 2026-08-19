<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Work_Variants', function (Blueprint $table) {
            // Текстовые поля (не копируются при клонировании)
            $table->text('teacher_comment')->nullable()->comment('Внутренний комментарий учителя');
            $table->text('print_instructions')->nullable()->comment('Текст, который печатается перед задачами (инструкция)');
            
            // Настройки печати
            $table->unsignedTinyInteger('print_font_size')->default(12)->comment('Размер шрифта (pt)');
            $table->unsignedTinyInteger('print_spacing_lines')->default(1)->comment('Пропуск между задачами (в строках)');
            $table->unsignedTinyInteger('print_copies_per_page')->default(1)->comment('Экземпляров на страницу (1, 2, 4)');
            $table->boolean('print_show_name_field')->default(false)->comment('Показывать поле "Фамилия И."');
        });
    }

    public function down(): void
    {
        Schema::table('Work_Variants', function (Blueprint $table) {
            $table->dropColumn([
                'teacher_comment', 
                'print_instructions', 
                'print_font_size', 
                'print_spacing_lines', 
                'print_copies_per_page', 
                'print_show_name_field'
            ]);
        });
    }
};