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
            // 1. Копируем сам вариант, ИСКЛЮЧАЯ комментарий и инструкции
            $newVariant = $variant->replicate(['teacher_comment', 'print_instructions']);
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

    // =========================================================================
    // УРОВЕНЬ 2: НАПОЛНЕНИЕ ВАРИАНТА ЗАДАЧАМИ
    // =========================================================================

    /**
     * Отображение страницы конструктора (Две колонки)
     */
    public function build(Request $request, WorkVariant $variant)
    {
        // 1. Получаем задачи, уже добавленные в этот вариант
        // Используем связь tasks() из модели, которая сразу сортирует по pivot_sorting_num
        $variantTasks = $variant->tasks()->with(['topic', 'taskImages'])->get();
        // Массив ID уже добавленных задач (чтобы исключить их из поиска слева)
        $attachedTaskIds = $variantTasks->pluck('id')->toArray();

        // 2. Получаем задачи из Базы (Левая колонка)
        $query = \App\Models\Task::with(['topic', 'author']);

        // Не показываем задачи, которые УЖЕ добавлены в правую колонку
        if (!empty($attachedTaskIds)) {
            $query->whereNotIn('id', $attachedTaskIds);
        }

        // Фильтры базы задач
        $query->when($request->topic_id, fn($q, $v) => $q->where('topic_id', $v));
        $query->when($request->source_id, fn($q, $v) => $q->where('source_id', $v));
        $query->when($request->author_id, fn($q, $v) => $q->where('author_id', $v));
        $query->when($request->search, function($q, $v) {
            if (is_numeric($v)) {
                $q->where('id', $v);
            } else {
                $q->where('task_text', 'like', "%{$v}%");
            }
        });

        // Сортировка базы задач
        $sortField = in_array($request->sort_by, ['id', 'complexity']) ? $request->sort_by : 'id';
        $query->orderBy($sortField, $request->input('sort_dir', 'desc'));

        $libraryTasks = $query->paginate(50)->withQueryString();

        // Данные для выпадающих списков фильтров
        $topics = \App\Models\Topic::whereNull('parent_id')->orWhere('parent_id', 0)->with('children')->orderBy('sorting_num')->get();
        $sources = \App\Models\Source::whereNull('parent_id')->orWhere('parent_id', 0)->with('children')->orderBy('sorting_num')->get();

        $authors = \App\Models\User::whereHas('tasks')->orderBy('last_name')->get();

        return view('variants.build', compact('variant', 'variantTasks', 'libraryTasks', 'topics', 'sources', 'authors', 'sortField'));
    }

    /**
     * Массовое добавление задач в вариант
     */
    public function attachTasks(Request $request, WorkVariant $variant)
    {
        if ($variant->isAssigned()) abort(403, 'Вариант уже выдан, изменение запрещено.');
        if ($variant->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) abort(403);

        $request->validate(['task_ids' => 'required|array']);

        // Находим максимальный sorting_num в текущем варианте
        $maxSort = DB::table('Work_Variant_Tasks')
                     ->where('work_variant_id', $variant->id)
                     ->max('sorting_num') ?? -1;

        $attachData = [];
        foreach ($request->task_ids as $taskId) {
            $maxSort++;
            $attachData[$taskId] = ['sorting_num' => $maxSort];
        }

        // Прикрепляем задачи без открепления старых (syncWithoutDetaching)
        $variant->tasks()->syncWithoutDetaching($attachData);

        return back()->with('success', 'Задачи добавлены в вариант.');
    }

    /**
     * Удаление одной задачи из варианта
     */
    public function detachTask(Request $request, WorkVariant $variant, \App\Models\Task $task)
    {
        if ($variant->isAssigned()) abort(403);
        if ($variant->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) abort(403);

        $variant->tasks()->detach($task->id);

        // Если запрос пришел из Javascript (AJAX), просто возвращаем "ОК"
        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Задача убрана из варианта.');
    }

    /**
     * Сохранение нового порядка задач (Drag & Drop)
     */
    public function reorderTasks(Request $request, WorkVariant $variant)
    {
        if ($variant->isAssigned()) abort(403);
        if ($variant->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) abort(403);

        $request->validate(['task_order' => 'required|array']);

        DB::transaction(function () use ($request, $variant) {
            foreach ($request->task_order as $index => $taskId) {
                DB::table('Work_Variant_Tasks')
                    ->where('work_variant_id', $variant->id)
                    ->where('task_id', $taskId)
                    ->update(['sorting_num' => $index]);
            }
        });

        return back()->with('success', 'Порядок задач сохранен.');
    }

    /**
     * Автоматическая сортировка задач в варианте по сложности
     */
    public function sortByComplexity(WorkVariant $variant)
    {
        if ($variant->isAssigned()) abort(403);
        if ($variant->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) abort(403);

        // Получаем задачи и сортируем по сложности
        $tasks = $variant->tasks()->orderBy('complexity', 'asc')->get();
        
        DB::transaction(function () use ($variant, $tasks) {
            foreach ($tasks as $index => $task) {
                DB::table('Work_Variant_Tasks')
                    ->where('work_variant_id', $variant->id)
                    ->where('task_id', $task->id)
                    ->update(['sorting_num' => $index]);
            }
        });

        return back()->with('success', 'Задачи в варианте отсортированы по возрастанию сложности.');
    }

    // =========================================================================
    // УРОВЕНЬ 3: КОНФИГУРАЦИЯ ПЕЧАТИ И ПЕЧАТЬ
    // =========================================================================

    /**
     * Страница настроек печати
     */
    public function printConfig(WorkVariant $variant)
    {
        if ($variant->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) abort(403);

        // Получаем группы (для симуляции шапки, если хотим выбрать, для кого печатаем)
        $groups = Group::orderBy('grade')->orderBy('name')->get();

        return view('variants.print-config', compact('variant', 'groups'));
    }

    /**
     * Сохранение настроек печати
     */
    public function updatePrintConfig(Request $request, WorkVariant $variant)
    {
        if ($variant->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) abort(403);

            $data = $request->validate([
            'teacher_comment' => 'nullable|string',
            'print_instructions' => 'nullable|string',
            'print_font_size' => 'required|integer|min:8|max:24',
            'print_spacing_lines' => 'required|integer|min:0|max:10',
            'print_copies_per_page' => 'required|in:1,2,4',
            'print_show_name_field' => 'nullable|boolean',
            'print_show_task_id' => 'nullable|boolean',
            'print_show_complexity' => 'nullable|boolean',
        ]);

        // Чекбоксы HTML не передаются, если не отмечены, поэтому конвертируем их принудительно
        $data['print_show_name_field'] = $request->has('print_show_name_field');
        $data['print_show_task_id'] = $request->has('print_show_task_id');
        $data['print_show_complexity'] = $request->has('print_show_complexity');

        $variant->update($data);

        return back()->with('success', 'Настройки печати и комментарии сохранены.');
    }

    /**
     * Сама страница для вывода на принтер
     */
    public function print(Request $request, WorkVariant $variant)
    {
        if ($variant->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) abort(403);

        $variantTasks = $variant->tasks()->with('taskImages', 'solutionImages')->get();
        
        // Доп. опции, которые не сохраняются в базу, а передаются из формы перед самой печатью
        $showAnswers = $request->boolean('show_answers');
        $selectedGroupId = $request->input('group_id');
        $group = $selectedGroupId ? Group::find($selectedGroupId) : null;

        return view('variants.print', compact('variant', 'variantTasks', 'showAnswers', 'group'));
    }

    // =========================================================================
    // УРОВЕНЬ 4: ВЫДАЧА И ОТЗЫВ ВАРИАНТОВ ГРУППАМ
    // =========================================================================

    /**
     * Выдать вариант группе
     */
    public function assignToGroup(Request $request, WorkVariant $variant)
    {
        if ($variant->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) abort(403);

        $request->validate(['group_id' => 'required|exists:Groups,id']);

        // Проверяем, не выдавали ли уже этот вариант этой группе
        $exists = \App\Models\AssignmentHistory::where('work_variant_id', $variant->id)
                    ->where('group_id', $request->group_id)
                    ->exists();

        if ($exists) {
            return back()->with('error', 'Этот вариант уже выдан выбранной группе.');
        }

        \App\Models\AssignmentHistory::create([
            'work_variant_id' => $variant->id,
            'group_id' => $request->group_id,
            'teacher_id' => auth()->id(),
            'assigned_at' => now(),
        ]);

        return back()->with('success', 'Вариант успешно выдан группе. Теперь он защищен от изменений.');
    }

    /**
     * Отменить выдачу варианта (Отозвать)
     */
    public function revokeFromGroup(\App\Models\AssignmentHistory $history)
    {
        $variant = $history->variant;
        
        if ($variant->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) abort(403);

        $history->delete();

        return back()->with('success', 'Выдача варианта отменена.');
    }

    // --- УДАЛЕНИЕ ВАРИАНТА ---
    public function destroy(WorkVariant $variant)
    {
        // 1. Проверяем права (Только автор или админ)
        if ($variant->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403, 'Удалить вариант может только его автор или администратор.');
        }

        // 2. Проверяем бизнес-логику (Нельзя удалить выданный вариант)
        if ($variant->isAssigned()) {
            return back()->with('error', 'Нельзя удалить вариант, так как он уже выдан ученикам.');
        }

        // 3. Удаляем. (Задачи из варианта открепятся автоматически благодаря ON DELETE CASCADE в базе)
        $variant->delete();

        return back()->with('success', 'Вариант успешно удален.');
    }

    /**
     * AJAX: Изменение права на самоназначение задачи внутри варианта
     */
    public function toggleSelfAssign(Request $request, WorkVariant $variant, \App\Models\Task $task)
    {
        // Проверка прав (как всегда: не выдан и автор/админ)
        if ($variant->isAssigned()) abort(403);
        if ($variant->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) abort(403);

        $val = $request->input('is_self_assignable');
        // Приводим пустую строку к null, а 0/1 к числам
        $val = $val === null || $val === '' ? null : (int)$val;

        // Обновляем напрямую в промежуточной таблице
        \Illuminate\Support\Facades\DB::table('Work_Variant_Tasks')
            ->where('work_variant_id', $variant->id)
            ->where('task_id', $task->id)
            ->update(['is_self_assignable' => $val]);

        return response()->json(['success' => true]);
    }
}