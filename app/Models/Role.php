<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'Roles'; // Точное имя вашей таблицы
    public $timestamps = false; // Отключаем даты
}