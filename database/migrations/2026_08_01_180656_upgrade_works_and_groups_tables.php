<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Выполнить миграцию (Добавить новые поля/таблицы).
     */
    public function up(): void
    {
        // 1. Улучшаем таблицу Groups (добавляем класс/параллель)
        Schema::table('Groups', function (Blueprint $table) {
            // Добавляем только если колонки еще нет
            if (!Schema::hasColumn('Groups', 'grade')) {
                $table->unsignedTinyInteger('grade')->nullable()->comment('Класс/параллель (7, 8, 9, 10, 11)');
            }
        });

        // 2. Улучшаем Работы (добавляем привязку к классу)
        Schema::table('Works', function (Blueprint $table) {
            if (!Schema::hasColumn('Works', 'grade')) {
                $table->unsignedTinyInteger('grade')->nullable()->comment('Для какого класса работа');
            }
        });

        // 3. Улучшаем Варианты (Версионирование, Архив, Автор)
        Schema::table('Work_Variants', function (Blueprint $table) {
            if (!Schema::hasColumn('Work_Variants', 'version')) {
                $table->unsignedSmallInteger('version')->default(1)->comment('Версия варианта');
            }
            if (!Schema::hasColumn('Work_Variants', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->comment('Помещен ли в архив');
            }
            if (!Schema::hasColumn('Work_Variants', 'parent_id')) {
                $table->unsignedInteger('parent_id')->nullable()->comment('Из какого варианта скопирован (или старая версия)');
                // Делаем мягкую связь (без жесткого foreign key, чтобы старые данные не ломались)
                $table->index('parent_id'); 
            }
            if (!Schema::hasColumn('Work_Variants', 'author_id')) {
                // По умолчанию автор варианта = автор работы, но мы добавим поле для будущих заимствований
                $table->unsignedInteger('author_id')->nullable()->comment('Владелец конкретно этого варианта');
                
                // Чтобы не сломать старую БД, foreign key добавим позже, если понадобится.
                $table->index('author_id');
            }
        });

        // 4. Журнал выдачи вариантов группам (История)
        // Если у вас была старая Group_Variant_Assignments, мы её не трогаем, а создаем новую, правильную
        if (!Schema::hasTable('Assignment_History')) {
            Schema::create('Assignment_History', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('work_variant_id');
                $table->unsignedInteger('group_id');
                $table->unsignedInteger('teacher_id');
                
                // Дополнительная инфа: можно задать срок сдачи
                $table->dateTime('due_date')->nullable();
                $table->timestamp('assigned_at')->useCurrent();

                // Внешние ключи для целостности (ссылаемся на ваши старые таблицы)
                $table->foreign('work_variant_id')->references('id')->on('Work_Variants')->onDelete('cascade');
                $table->foreign('group_id')->references('id')->on('Groups')->onDelete('cascade');
                $table->foreign('teacher_id')->references('id')->on('Users')->onDelete('cascade');
            });
        }
    }

    /**
     * Откатить миграцию (если что-то пошло не так).
     */
    public function down(): void
    {
        Schema::dropIfExists('Assignment_History');

        Schema::table('Work_Variants', function (Blueprint $table) {
            $table->dropColumn(['version', 'is_archived', 'parent_id', 'author_id']);
        });

        Schema::table('Works', function (Blueprint $table) {
            $table->dropColumn('grade');
        });

        Schema::table('Groups', function (Blueprint $table) {
            $table->dropColumn('grade');
        });
    }
};