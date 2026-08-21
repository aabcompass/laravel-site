<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Поле в таблице Tasks
        Schema::table('Tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('Tasks', 'is_self_assignable')) {
                // Используем boolean(tinyint), nullable. 
                // null - не определено, 1 - можно, 0 - нельзя
                $table->boolean('is_self_assignable')->nullable()->comment('Разрешено ли ученикам брать задачу самим');
            }
        });

        // 2. Поле в сводной таблице Work_Variant_Tasks
        Schema::table('Work_Variant_Tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('Work_Variant_Tasks', 'is_self_assignable')) {
                $table->boolean('is_self_assignable')->nullable()->comment('Приоритетное правило для конкретного варианта');
            }
        });

        // 3. Флажок в таблице назначений (Student_Assignments)
        Schema::table('Student_Assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('Student_Assignments', 'is_self_assigned')) {
                $table->boolean('is_self_assigned')->default(false)->comment('Задача взята по инициативе ученика');
            }
        });
    }

    public function down(): void
    {
        Schema::table('Student_Assignments', function (Blueprint $table) {
            $table->dropColumn('is_self_assigned');
        });
        Schema::table('Work_Variant_Tasks', function (Blueprint $table) {
            $table->dropColumn('is_self_assignable');
        });
        Schema::table('Tasks', function (Blueprint $table) {
            $table->dropColumn('is_self_assignable');
        });
    }
};