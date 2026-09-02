<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Student_Rewards', function (Blueprint $table) {
            if (!Schema::hasColumn('Student_Rewards', 'reason')) {
                $table->string('reason', 150)->nullable()->comment('За что выдана награда');
            }
        });
    }

    public function down(): void
    {
        Schema::table('Student_Rewards', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
    }
};