<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Group extends Model
{
    protected $table = 'Groups';
    public $timestamps = false;

    // Добавили public_hash
    protected $fillable = ['name', 'description', 'grade', 'public_hash'];

    public function students()
    {
        return $this->hasMany(User::class, 'group_id');
    }

    // Авто-генерация хэша при создании НОВОЙ группы
    protected static function booted()
    {
        static::creating(function ($group) {
            if (empty($group->public_hash)) {
                $group->public_hash = Str::random(16); // 16 случайных символов
            }
        });
    }
}