<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentHistory extends Model
{
    protected $table = 'Assignment_History';
    public $timestamps = false;

    protected $fillable = ['work_variant_id', 'group_id', 'teacher_id', 'due_date', 'assigned_at'];

    protected $casts = [
        'assigned_at' => 'datetime',
        'due_date' => 'datetime',
    ];

    public function variant() { return $this->belongsTo(WorkVariant::class, 'work_variant_id'); }
    public function group() { return $this->belongsTo(Group::class, 'group_id'); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }
}