<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Work_Variants', function (Blueprint $table) {
            if (!Schema::hasColumn('Work_Variants', 'print_show_task_id')) {
                $table->boolean('print_show_task_id')->default(false)->comment('Показывать ID задачи при печати');
            }
            if (!Schema::hasColumn('Work_Variants', 'print_show_complexity')) {
                $table->boolean('print_show_complexity')->default(false)->comment('Показывать сложность задачи при печати');
            }
        });
    }

    public function down(): void
    {
        Schema::table('Work_Variants', function (Blueprint $table) {
            $table->dropColumn(['print_show_task_id', 'print_show_complexity']);
        });
    }
};