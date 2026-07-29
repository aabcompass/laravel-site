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
        'author_solution_text'
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
}