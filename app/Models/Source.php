<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    protected $table = 'Sources'; // Имя таблицы в БД
    public $timestamps = false;   // Отключаем created_at и updated_at

    protected $fillable = ['name', 'description', 'parent_id', 'sorting_num'];

    public function parent()
    {
        return $this->belongsTo(Source::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Source::class, 'parent_id')
                    ->orderBy('sorting_num')
                    ->with('children'); // Рекурсивная жадная загрузка
    }
}