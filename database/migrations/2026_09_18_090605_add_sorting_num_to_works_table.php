<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Works', function (Blueprint $table) {
            $table->integer('sorting_num')->default(0)->after('grade')->comment('Порядок сортировки');
        });

        // Автоматически заполняем сортировку для уже существующих работ (чтобы сохранить их текущий порядок)
        DB::statement('UPDATE Works SET sorting_num = id');
    }

    public function down(): void
    {
        Schema::table('Works', function (Blueprint $table) {
            $table->dropColumn('sorting_num');
        });
    }
};