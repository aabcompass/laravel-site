<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Student_Rewards', function (Blueprint $table) {
            // Добавляем поле для связи с вариантом
            $table->unsignedInteger('work_variant_id')->nullable()->after('teacher_id')->comment('Привязка оценки к конкретному варианту');
            
            // Внешний ключ
            $table->foreign('work_variant_id')->references('id')->on('Work_Variants')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('Student_Rewards', function (Blueprint $table) {
            $table->dropForeign(['work_variant_id']);
            $table->dropColumn('work_variant_id');
        });
    }
};