<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        // Получаем все группы, сразу считаем количество учеников в каждой
        // Сортируем сначала по классу (grade), потом по названию
        $groups = Group::withCount('students')
                       ->orderBy('grade')
                       ->orderBy('name')
                       ->get();

        return view('groups.index', compact('groups'));
    }

    public function create()
    {
        return view('groups.edit'); // Будем использовать один шаблон для create и edit
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        Group::create($data);

        return redirect()->route('groups.index')->with('success', "Группа '{$data['name']}' успешно создана.");
    }

    public function edit(Group $group)
    {
        return view('groups.edit', compact('group'));
    }

    public function update(Request $request, Group $group)
    {
        $data = $request->validate($this->rules($group->id));
        $group->update($data);

        return redirect()->route('groups.index')->with('success', "Группа '{$data['name']}' обновлена.");
    }

    public function destroy(Group $group)
    {
        // Не даем удалить группу, если в ней есть ученики
        if ($group->students()->count() > 0) {
            return back()->with('error', "Нельзя удалить группу '{$group->name}', так как в ней есть ученики.");
        }

        $group->delete();
        return redirect()->route('groups.index')->with('success', 'Группа удалена.');
    }

    private function rules($ignoreId = null)
    {
        // Проверяем уникальность названия группы. 
        // Если это обновление ($ignoreId), исключаем саму себя из проверки.
        $uniqueRule = 'unique:Groups,name';
        if ($ignoreId) {
            $uniqueRule .= ',' . $ignoreId;
        }

        return [
            'name' => ['required', 'string', 'max:100', $uniqueRule],
            'grade' => ['nullable', 'integer', 'min:1', 'max:12'],
            'description' => ['nullable', 'string'],
        ];
    }
}