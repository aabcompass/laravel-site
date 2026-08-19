<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkVariant extends Model
{
    protected $table = 'Work_Variants';
    public $timestamps = false;

    protected $fillable = [
        'work_id', 'name', 'description', 'sorting_num', 
        'public_hash', 'version', 'is_archived', 'parent_id', 'author_id',
        // НОВЫЕ ПОЛЯ:
        'teacher_comment', 'print_instructions', 'print_font_size', 
        'print_spacing_lines', 'print_copies_per_page', 'print_show_name_field'
    ];

    public function work() { return $this->belongsTo(Work::class, 'work_id'); }
    public function author() { return $this->belongsTo(User::class, 'author_id'); }
    
    // История выдачи этого варианта
    public function assignments() {
        return $this->hasMany(AssignmentHistory::class, 'work_variant_id');
    }

    // Связь с задачами (Many-to-Many)
    public function tasks() {
        return $this->belongsToMany(Task::class, 'Work_Variant_Tasks', 'work_variant_id', 'task_id')
                    ->withPivot('sorting_num')
                    ->orderBy('pivot_sorting_num');
    }

    // Вспомогательный метод: Выдан ли этот вариант кому-нибудь?
    public function isAssigned() {
        return $this->assignments()->count() > 0;
    }
}