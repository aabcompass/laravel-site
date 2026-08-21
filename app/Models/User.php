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
        'group_id',
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

    // --- СВЯЗИ ---

    // Пользователь имеет много ролей через промежуточную таблицу User_Roles
    public function roles()
    {
        return $this->belongsToMany(
            Role::class, 
            'User_Roles', // Имя промежуточной таблицы
            'user_id',    // Ключ текущей модели в этой таблице
            'role_id'     // Ключ связанной модели в этой таблице
        );
    }

    // --- ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ---

    // Метод для быстрой проверки, есть ли у пользователя нужная роль
    public function hasRole($roleName)
    {
        // Перебираем все роли пользователя и проверяем совпадение по имени
        return $this->roles->contains('name', $roleName);
    }

    // Метод для проверки наличия хотя бы одной роли из списка
    public function hasAnyRole(array $roleNames)
    {
        // Метод intersect проверяет, есть ли пересечение двух массивов (коллекций)
        return $this->roles->pluck('name')->intersect($roleNames)->isNotEmpty();
    }

    // Связь: Ученик принадлежит группе
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

     public function tasks()
    {
        // Указываем внешний ключ author_id, так как по умолчанию Laravel искал бы user_id
        return $this->hasMany(Task::class, 'author_id');
    }

    // Ученики, которых ведет этот учитель
    public function tutoredStudents()
    {
        return $this->belongsToMany(User::class, 'Tutor_Students', 'teacher_id', 'student_id')
                    ->withPivot('created_at');
    }

    // Учителя, которые ведут этого ученика
    public function tutors()
    {
        return $this->belongsToMany(User::class, 'Tutor_Students', 'student_id', 'teacher_id')
                    ->withPivot('created_at');
    }
}