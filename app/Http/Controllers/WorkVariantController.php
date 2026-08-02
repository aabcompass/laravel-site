<?php

namespace App\Http\Controllers;

use App\Models\Work;
use App\Models\WorkVariant;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkVariantController extends Controller
{
    // --- СПИСОК ВАРИАНТОВ РАБОТЫ ---
    public function index(Request $request, Work $work)
    {
        $showArchived = $request->boolean('show_archived');

        $variants = $work->variants()
            ->with(['author', 'assignments.group']) // Подгружаем автора и историю выдачи
            ->withCount('tasks') // Считаем количество задач
            ->when(!$showArchived, fn($q) => $q->where('is_archived', false))
            ->orderBy('sorting_num')
            ->orderBy('id')
            ->get();

        // Группы понадобятся для модального окна выдачи варианта
        $groups = Group::orderBy('grade')->orderBy('name')->get();

        return view('variants.index', compact('work', 'variants', 'showArchived', 'groups'));
    }

    // --- СОЗДАНИЕ НОВОГО ПУСТОГО ВАРИАНТА ---
    public function store(Request $request, Work $work)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $maxSort = $work->variants()->max('sorting_num');

        WorkVariant::create([
            'work_id' => $work->id,
            'name' => $request->name,
            'sorting_num' => $maxSort !== null ? $maxSort + 1 : 0,
            'author_id' => auth()->id(), // Кто нажал кнопку, тот и автор
            'version' => 1,
        ]);

        return back()->with('success', 'Новый вариант создан.');
    }

    // --- КЛОНИРОВАНИЕ ВАРИАНТА ---
    public function clone(WorkVariant $variant)
    {
        DB::transaction(function () use ($variant) {
            // 1. Копируем сам вариант
            $newVariant = $variant->replicate();
            $newVariant->name = $variant->name . ' (Копия)';
            $newVariant->author_id = auth()->id(); // Автором становится тот, кто клонировал
            $newVariant->version = 1;
            $newVariant->parent_id = $variant->id; // Запоминаем, откуда скопировали
            $newVariant->is_archived = false;
            $newVariant->public_hash = null; // Хэш должен быть уникальным, сбрасываем
            $newVariant->save();

            // 2. Копируем привязку задач (таблица Work_Variant_Tasks)
            // Достаем все задачи с их сортировкой и привязываем к новому варианту
            $tasksToAttach = [];
            foreach ($variant->tasks as $task) {
                $tasksToAttach[$task->id] = ['sorting_num' => $task->pivot->sorting_num];
            }
            $newVariant->tasks()->sync($tasksToAttach);
        });

        return back()->with('success', 'Вариант успешно клонирован. Вы стали его автором.');
    }

    // --- АРХИВИРОВАНИЕ / РАЗАРХИВИРОВАНИЕ ---
    public function toggleArchive(WorkVariant $variant)
    {
        // Только автор или админ могут архивировать
        if ($variant->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403, 'Нет прав на изменение статуса архива.');
        }

        $variant->update(['is_archived' => !$variant->is_archived]);
        
        $status = $variant->is_archived ? 'перемещен в архив' : 'восстановлен из архива';
        return back()->with('success', "Вариант {$status}.");
    }

    // --- ПЕРЕИМЕНОВАНИЕ ВАРИАНТА ---
    public function updateName(Request $request, WorkVariant $variant)
    {
        if ($variant->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $request->validate(['name' => 'required|string|max:255']);
        $variant->update(['name' => $request->name]);

        return back()->with('success', 'Название варианта обновлено.');
    }
}