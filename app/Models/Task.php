<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $table = 'Tasks';
    
    // В таблице Tasks у вас есть created_at и updated_at (ON UPDATE CURRENT_TIMESTAMP),
    // поэтому оставляем timestamps включенными. Но Laravel ожидает формат дат по умолчанию,
    // так что просто разрешим ему ими управлять.
    
    // Оставляем только нужные поля (без галочек checked/published)
    protected $fillable = [
        'topic_id',
        'source_id',
        'author_id', // Визуально назовем "Добавил"
        'complexity',
        'task_text',
        'answer_numeric',
        'answer_units',
        'advice_text',
        'author_solution_text',
        'is_self_assignable',
    ];

    // --- СВЯЗИ ---

    // Задача "принадлежит" Теме
    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    // Задача "принадлежит" Источнику
    public function source()
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    // Задача "принадлежит" Автору (Пользователю)
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // Обычная связь "один ко многим" с фильтрацией по слову 'task'
    public function taskImages()
    {
        return $this->hasMany(Attachment::class, 'attachable_id')
                    ->where('attachable_type', 'task');
    }

    // Обычная связь "один ко многим" с фильтрацией по слову 'author_solution'
    public function solutionImages()
    {
        return $this->hasMany(Attachment::class, 'attachable_id')
                    ->where('attachable_type', 'author_solution');
    }

    // --- СВЯЗЬ ДЛЯ БЕЙДЖИКА "ИСПОЛЬЗУЕТСЯ В РАБОТАХ" ---
    public function variants()
    {
        return $this->belongsToMany(
            WorkVariant::class, 
            'Work_Variant_Tasks', // Имя промежуточной таблицы
            'task_id',            // Внешний ключ текущей модели
            'work_variant_id'     // Внешний ключ связываемой модели
        );
    }

    /**
     * Можно ли ученику самому взять эту задачу?
     * @param bool|null $variantRule - Правило, переопределенное в конкретном варианте
     */
    public function canBeSelfAssigned($variantRule = null)
    {
        // 1. Если в варианте жестко задано Да(1) или Нет(0) - слушаемся варианта
        if ($variantRule !== null) {
            return (bool) $variantRule;
        }

        // 2. Иначе смотрим глобальную настройку самой задачи
        if ($this->is_self_assignable !== null) {
            return (bool) $this->is_self_assignable;
        }

        // 3. Если везде NULL - запрещено
        return false;
    }
}