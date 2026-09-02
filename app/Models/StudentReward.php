<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentReward extends Model
{
    protected $table = 'Student_Rewards';

    protected $fillable = [
        'student_id', 'reward_id', 'teacher_id', 
        'is_accounted', 'is_handed_over', 'claim_hash', 'created_at',
        'reason'
    ];

    protected $casts = [
        'is_accounted' => 'boolean',
        'is_handed_over' => 'boolean',
    ];

    public function student() { return $this->belongsTo(User::class, 'student_id'); }
    public function reward() { return $this->belongsTo(Reward::class, 'reward_id'); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }
}