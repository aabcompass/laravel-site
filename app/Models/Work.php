<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Work extends Model
{
    protected $table = 'Works';
    public $timestamps = false; // У вас в БД есть только created_at

    protected $fillable = ['topic_id', 'author_id', 'title', 'description', 'grade'];

    // Работа принадлежит теме
    public function topic() {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    // У работы есть автор
    public function author() {
        return $this->belongsTo(User::class, 'author_id');
    }

    // В работе много вариантов
    public function variants() {
        return $this->hasMany(WorkVariant::class, 'work_id');
    }
}