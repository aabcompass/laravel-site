<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    // 1. Указываем точное имя таблицы
    protected $table = 'Topics';

    // 2. Отключаем timestamps (created_at, updated_at), так как в вашей таблице их нет
    public $timestamps = false;

    // 3. Указываем доступные поля
    protected $fillable = ['name', 'description', 'parent_id', 'sorting_num'];

    // 4. МАГИЯ LARAVEL: описываем связь "Родительская тема"
    public function parent()
    {
        return $this->belongsTo(Topic::class, 'parent_id');
    }

    public function children()
    {
        // Добавление ->with('children') заставляет Laravel рекурсивно 
        // подгружать дочерние элементы для каждого дочернего элемента!
        return $this->hasMany(Topic::class, 'parent_id')
                    ->orderBy('sorting_num')
                    ->with('children'); 
    }
}