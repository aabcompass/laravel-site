<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Assignment_History', function (Blueprint $table) {
            $table->text('public_comment')->nullable()->comment('Комментарий для учеников (номера задач)');
        });
    }

    public function down(): void
    {
        Schema::table('Assignment_History', function (Blueprint $table) {
            $table->dropColumn('public_comment');
        });
    }
};