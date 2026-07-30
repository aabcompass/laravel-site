<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAssignment extends Model
{
    protected $table = 'Student_Assignments';

    // В таблице нет updated_at, но есть кастомные даты. Отключаем автоматику Laravel
    public $timestamps = false;

    // Разрешенные для массового заполнения поля
    protected $fillable = [
        'task_id',
        'student_id',
        'assigner_id',
        'reviewer_id',
        'status', // enum('assigned', 'in_progress', 'submitted', 'revision_needed', 'accepted')
        'solution_text',
        'answer_numeric',
        'teacher_comment',
        'mark_percent',
        'assigned_at',
        'due_date',
        'submitted_at',
        'checked_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'due_date' => 'datetime',
        'submitted_at' => 'datetime',
        'checked_at' => 'datetime',
    ];

    // --- СВЯЗИ ---

    // Задача
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    // Ученик
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // Учитель, который назначил
    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigner_id');
    }

    // Учитель, который проверил
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // Вложенные файлы (решение ученика)
    // Используем hasMany вместо morphMany, чтобы Laravel искал именно по слову 'student_assignment'
    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'attachable_id')
                    ->where('attachable_type', 'student_assignment');
    }
}