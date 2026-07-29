<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkVariant extends Model
{
    // Просто указываем имя таблицы, больше нам пока ничего не нужно
    protected $table = 'Work_Variants';
    public $timestamps = false;
}