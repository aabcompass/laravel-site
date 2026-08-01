<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = 'Groups';
    public $timestamps = false; // В таблице есть только created_at, Laravel его заполнит сам

    // Разрешаем заполнять новые поля
    protected $fillable = ['name', 'description', 'grade'];

    // Связь: В группе много учеников
    public function students()
    {
        return $this->hasMany(User::class, 'group_id');
    }
}