<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // 1. Указываем точное имя вашей старой таблицы
    protected $table = 'Users';

    // 2. Указываем, какие поля разрешено обновлять/создавать
    protected $fillable = [
        'email',
        'password_hash',
        'last_name',
        'first_name',
    ];

    // 3. Прячем приватные данные (чтобы они случайно не вывелись на экран)
    protected $hidden = [
        'password_hash',
        'auth_token',
    ];

    // 4. ГЛАВНАЯ МАГИЯ: Говорим Laravel, что пароль лежит в колонке password_hash
    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    // 5. ТРЮК ДЛЯ BREEZE: Дизайн Breeze везде пытается вывести имя через {{ Auth::user()->name }}.
    // Мы создаем "виртуальное" свойство name, которое склеивает фамилию и имя.
    public function getNameAttribute()
    {
        return $this->last_name . ' ' . $this->first_name;
    }
}