<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Groups', function (Blueprint $table) {
            $table->string('public_hash', 40)->nullable()->unique()->after('id')->comment('Хэш для публичной ссылки группы');
        });
    }

    public function down(): void
    {
        Schema::table('Groups', function (Blueprint $table) {
            $table->dropColumn('public_hash');
        });
    }
};