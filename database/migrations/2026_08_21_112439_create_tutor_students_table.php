<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Tutor_Students', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('teacher_id');
            $table->unsignedInteger('student_id');
            $table->timestamp('created_at')->useCurrent();

            // Один учитель не может добавить одного и того же ученика дважды
            $table->unique(['teacher_id', 'student_id']);
            
            // Внешние ключи (удаляются автоматически, если удалить юзера)
            $table->foreign('teacher_id')->references('id')->on('Users')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('Users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Tutor_Students');
    }
};