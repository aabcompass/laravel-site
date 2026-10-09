<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Rewards', function (Blueprint $table) {
            $table->boolean('is_absent')->default(false)->comment('Маркер отсутствия на уроке (Вакуум)');
            $table->string('color', 20)->nullable()->comment('Цвет фона награды (hex)');
        });
    }

    public function down(): void
    {
        Schema::table('Rewards', function (Blueprint $table) {
            $table->dropColumn(['is_absent', 'color']);
        });
    }
};