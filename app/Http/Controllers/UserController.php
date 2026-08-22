<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['roles', 'group'])->orderBy('last_name')->orderBy('first_name');

        // ФИЛЬТРЫ
        $query->when($request->search, function ($q, $v) {
            $q->where(function($q2) use ($v) {
                $q2->where('last_name', 'like', "%{$v}%")
                   ->orWhere('first_name', 'like', "%{$v}%")
                   ->orWhere('email', 'like', "%{$v}%");
            });
        });

        $query->when($request->role_id, function ($q, $v) {
            $q->whereHas('roles', fn($q2) => $q2->where('Roles.id', $v));
        });

        $query->when($request->group_id, fn($q, $v) => $q->where('group_id', $v));

        $users = $query->paginate(50)->withQueryString();

        $roles = Role::all();
        $groups = Group::orderBy('grade')->orderBy('name')->get();

        return view('users.index', compact('users', 'roles', 'groups'));
    }

    public function create()
    {
        $roles = Role::all();
        $groups = Group::orderBy('grade')->orderBy('name')->get();
        return view('users.edit', compact('roles', 'groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|unique:Users,email',
            'password' => 'required|min:6',
            'last_name' => 'required|string|max:100',
            'first_name' => 'required|string|max:100',
            'group_id' => 'nullable|integer',
            'roles' => 'required|array'
        ]);

        $user = User::create([
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']), // Хешируем пароль!
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'group_id' => $data['group_id'] ?? null,
        ]);

        // Привязываем роли
        $user->roles()->sync($data['roles']);

        return redirect()->route('users.index')->with('success', 'Пользователь успешно создан.');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $groups = Group::orderBy('grade')->orderBy('name')->get();
        // Достаем ID текущих ролей юзера (чтобы отметить чекбоксы)
        $userRoleIds = $user->roles->pluck('id')->toArray();

        return view('users.edit', compact('user', 'roles', 'groups', 'userRoleIds'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'email' => ['required', 'email', Rule::unique('Users')->ignore($user->id)],
            'password' => 'nullable|min:6', // Пароль не обязателен при редактировании
            'last_name' => 'required|string|max:100',
            'first_name' => 'required|string|max:100',
            'group_id' => 'nullable|integer',
            'roles' => 'required|array'
        ]);

        // Защита "от выстрела в ногу" (нельзя снять с себя админа)
        if ($user->id === auth()->id() && !in_array(3, $data['roles'])) { // 3 = ID роли admin
            return back()->with('error', 'Вы не можете лишить себя прав администратора.');
        }

        $user->email = $data['email'];
        $user->last_name = $data['last_name'];
        $user->first_name = $data['first_name'];
        $user->group_id = $data['group_id'] ?? null;

        // Если ввели новый пароль - обновляем его
        if (!empty($data['password'])) {
            $user->password_hash = Hash::make($data['password']);
        }
        $user->save();

        // Синхронизируем роли (Laravel сам удалит лишние и добавит новые)
        $user->roles()->sync($data['roles']);

        return redirect()->route('users.index')->with('success', 'Данные пользователя обновлены.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Вы не можете удалить сами себя.');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Пользователь удален.');
    }

    /**
     * Быстрое обновление группы прямо из списка
     */
    public function updateGroup(Request $request, User $user)
    {
        $request->validate([
            'group_id' => 'nullable|exists:Groups,id'
        ]);

        $user->update(['group_id' => $request->group_id]);

        return back()->with('success', "Группа для ученика {$user->last_name} {$user->first_name} успешно изменена.");
    }

    /**
     * Страница массовой печати QR-кодов
     */
    public function printQr(Request $request)
    {
        $query = User::with('group')->orderBy('last_name')->orderBy('first_name');

        // Применяем те же фильтры, что и в списке
        $query->when($request->role_id, function ($q, $v) {
            $q->whereHas('roles', fn($q2) => $q2->where('Roles.id', $v));
        });

        $query->when($request->group_id, fn($q, $v) => $q->where('group_id', $v));

        $users = $query->get(); // Забираем всех подходящих, без пагинации

        // Заплатка: если у кого-то из старых юзеров нет токена - сгенерируем на лету
        foreach ($users as $user) {
            if (empty($user->auth_token)) {
                $user->update(['auth_token' => bin2hex(random_bytes(20))]);
            }
        }

        return view('users.print-qr', compact('users'));
    }   

    /**
     * Страница массового добавления пользователей
     */
    public function bulkCreate()
    {
        $groups = Group::orderBy('grade')->orderBy('name')->get();
        return view('users.bulk', compact('groups'));
    }

    /**
     * Обработка массового сохранения
     */
    public function bulkStore(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:Groups,id',
            'students' => 'required|array',
            'students.*.last_name' => 'required|string|max:100',
            'students.*.first_name' => 'required|string|max:100',
        ]);

        // Ищем роль advanced_student. Если её вдруг нет, берем ID 1 (student)
        $role = Role::where('name', 'advanced_student')->first();
        $roleId = $role ? $role->id : 1; 

        $addedCount = 0;
        $skippedCount = 0;

        foreach ($request->students as $st) {
            // Проверка на дубликат (Фамилия + Имя + Группа)
            $exists = User::where('last_name', $st['last_name'])
                          ->where('first_name', $st['first_name'])
                          ->where('group_id', $request->group_id)
                          ->exists();

            if ($exists) {
                $skippedCount++;
                continue;
            }

            // Генерируем случайный уникальный email и пароль
            $randomString = Str::random(8);
            $email = "student_{$randomString}@phys408.local";
            $password = Hash::make(Str::random(12));

            $user = User::create([
                'email' => $email,
                'password_hash' => $password,
                'last_name' => $st['last_name'],
                'first_name' => $st['first_name'],
                'group_id' => $request->group_id,
            ]);

            // Привязываем роль
            $user->roles()->sync([$roleId]);
            
            // auth_token сгенерируется автоматически благодаря нашему методу booted() в модели User

            $addedCount++;
        }

        $message = "Массовое добавление завершено. Добавлено новых учеников: {$addedCount}.";
        if ($skippedCount > 0) {
            $message .= " Пропущено дубликатов: {$skippedCount}.";
        }

        return redirect()->route('users.index')->with('success', $message);
    }
}