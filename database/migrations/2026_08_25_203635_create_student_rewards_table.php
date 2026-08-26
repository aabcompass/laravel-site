<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Student_Rewards', function (Blueprint $table) {
            $table->id();
            
            // Внешние ключи
            // student_id делаем nullable для будущих безымянных QR-кодов
            $table->unsignedInteger('student_id')->nullable();
            $table->unsignedBigInteger('reward_id'); // В Laravel 11 $table->id() создает BigInteger
            $table->unsignedInteger('teacher_id');
            
            // Флажки
            $table->boolean('is_accounted')->default(false)->comment('Учтено в школьном журнале');
            $table->boolean('is_handed_over')->default(false)->comment('Вручен физический носитель');
            
            $table->string('claim_hash', 40)->nullable()->unique()->comment('Хэш для получения по QR');

            // Даты (Laravel автоматически создаст created_at и updated_at)
            $table->timestamps();

            // Связи
            $table->foreign('student_id')->references('id')->on('Users')->onDelete('cascade');
            $table->foreign('reward_id')->references('id')->on('Rewards')->onDelete('cascade');
            $table->foreign('teacher_id')->references('id')->on('Users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Student_Rewards');
    }
};