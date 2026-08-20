<?php

namespace App\Http\Controllers;

use App\Models\StudentAssignment;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class StudentAssignmentController extends Controller
{
    /**
     * Отображение страницы с заданием для ученика
     */
    public function show(StudentAssignment $assignment)
    {
        // 1. ЗАЩИТА: Проверяем, что это задание принадлежит текущему авторизованному ученику
        if ($assignment->student_id !== auth()->id()) {
            abort(403, 'Доступ запрещен. Это чужое задание.');
        }

        // 2. Жадная загрузка (чтобы не делать кучу мелких SQL запросов в шаблоне)
        $assignment->load([
            'task.taskImages',       // Картинки условия
            'task.solutionImages',   // Картинки эталонного решения
            'attachments',           // Картинки решения ученика
            'reviewer'               // Кто проверял
        ]);

        return view('assignments.show', compact('assignment'));
    }

    /**
     * Обработка отправки решения на проверку
     */
    public function submit(Request $request, StudentAssignment $assignment)
    {
        // 1. ЗАЩИТА
        if ($assignment->student_id !== auth()->id()) abort(403);
        
        // Отправлять можно только если статус 'assigned' (назначено) или 'revision_needed' (на доработку)
        if (!in_array($assignment->status, ['assigned', 'revision_needed'])) {
            return back()->with('error', 'Вы не можете отправить это задание в текущем статусе.');
        }

        // 2. ВАЛИДАЦИЯ
        $request->validate([
            'solution_text' => 'nullable|string',
            'answer_numeric' => 'nullable|numeric',
            'solution_files.*' => 'nullable|file|mimes:jpeg,png,webm,jpg,gif,pdf|max:10240', // до 10 МБ
        ]);

        // 3. ОБНОВЛЕНИЕ ДАННЫХ
        $assignment->update([
            'solution_text' => $request->solution_text,
            'answer_numeric' => $request->answer_numeric,
            'status' => 'submitted', // Меняем статус
            'submitted_at' => now(), // Ставим текущую дату и время
        ]);

        // 4. ЗАГРУЗКА ФАЙЛОВ
        if ($request->hasFile('solution_files')) {
            foreach ($request->file('solution_files') as $file) {
                
                // 1. СНАЧАЛА читаем все данные о файле (пока он во временной папке)
                $originalName = $file->getClientOriginalName();
                $mimeType = $file->getMimeType();
                $fileSize = $file->getSize();
                $extension = $file->getClientOriginalExtension();
                
                // 2. Генерируем безопасное имя
                $safeFilename = uniqid("solution_{$assignment->id}_", true) . '.' . strtolower($extension);
                
                // 3. ПЕРЕМЕЩАЕМ файл в старую папку
                $file->move(public_path('uploads/solutions'), $safeFilename);

                // 4. Записываем в базу
                Attachment::create([
                    'attachable_id' => $assignment->id,
                    'attachable_type' => 'student_assignment',
                    'uploader_id' => auth()->id(),
                    'file_path' => 'uploads/solutions/' . $safeFilename,
                    'original_filename' => $originalName,
                    'mime_type' => $mimeType,
                    'file_size_bytes' => $fileSize,
                    'scale' => 100 // По умолчанию 100%
                ]);
            }
        }

        return back()->with('success', 'Решение успешно отправлено на проверку!');
    }

    /**
     * Обработка удаления отдельной картинки решения
     */
    public function destroyAttachment(StudentAssignment $assignment, Attachment $attachment)
    {
        // 1. ЗАЩИТА (Ученик удаляет только из своего задания)
        if ($assignment->student_id !== auth()->id() || $attachment->uploader_id !== auth()->id()) {
            abort(403);
        }

        // Запрещаем удалять, если работа уже на проверке
        if (!in_array($assignment->status, ['assigned', 'revision_needed'])) {
            return back()->with('error', 'Нельзя удалить файл, пока работа на проверке.');
        }

        // 2. ФИЗИЧЕСКОЕ УДАЛЕНИЕ
        $filePath = public_path($attachment->file_path);
        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        // 3. УДАЛЕНИЕ ИЗ БАЗЫ
        $attachment->delete();

        return back()->with('success', 'Файл удален.');
    }


    /**
     * Отзыв отправленной работы (если учитель еще не проверил)
     */
    public function recall(StudentAssignment $assignment)
    {
        if ($assignment->student_id !== auth()->id()) abort(403);

        if ($assignment->status !== 'submitted') {
            return back()->with('error', 'Можно отозвать только отправленную работу.');
        }

        // Возвращаем статус и обнуляем дату отправки
        $assignment->update([
            'status' => 'assigned',
            'submitted_at' => null
        ]);

        return back()->with('success', 'Работа отозвана для доработки.');
    }

    public function index(Request $request)
    {
        $student = auth()->user(); // Получаем объект ученика целиком

        // 1. СТАТИСТИКА ДЛЯ КЛИКАБЕЛЬНЫХ КНОПОК
        $statusCounts = StudentAssignment::where('student_id', $student->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        $totalCount = array_sum($statusCounts);

        // 2. ПОЛУЧАЕМ ТЕМЫ ДЛЯ ВЫПАДАЮЩЕГО СПИСКА
        $topicIds = StudentAssignment::where('student_id', $student->id)
            ->join('Tasks', 'Student_Assignments.task_id', '=', 'Tasks.id')
            ->distinct()
            ->pluck('Tasks.topic_id');
            
        $topics = \App\Models\Topic::whereIn('id', $topicIds)->orderBy('name')->get();

        // 3. СТРОИМ ОСНОВНОЙ ЗАПРОС (ИНДИВИДУАЛЬНЫЕ ЗАДАЧИ)
        $query = StudentAssignment::query()
            ->where('student_id', $student->id)
            ->select('Student_Assignments.*')
            ->join('Tasks', 'Student_Assignments.task_id', '=', 'Tasks.id')
            ->with(['task.topic', 'task.taskImages']);

        $query->when($request->status, fn($q, $v) => $q->where('status', $v));
        $query->when($request->topic_id, fn($q, $v) => $q->where('Tasks.topic_id', $v));

        $sort = $request->input('sort', 'date_desc');
        switch ($sort) {
            case 'complexity_asc':  $query->orderBy('Tasks.complexity', 'asc'); break;
            case 'complexity_desc': $query->orderBy('Tasks.complexity', 'desc'); break;
            case 'date_asc':        $query->orderBy('Student_Assignments.assigned_at', 'asc'); break;
            case 'date_desc':
            default:
                $query->orderByRaw("CASE WHEN status = 'revision_needed' THEN 0 ELSE 1 END")
                      ->orderBy('Student_Assignments.assigned_at', 'desc');
                break;
        }

        $assignments = $query->paginate(15)->withQueryString();

        // 4. ДАННЫЕ ДЛЯ ГРАФИКА
        $complexityStats = [];
        $acceptedTasks = StudentAssignment::where('student_id', $student->id)
            ->where('status', 'accepted')
            ->join('Tasks', 'Student_Assignments.task_id', '=', 'Tasks.id')
            ->selectRaw('Tasks.complexity, count(*) as count')
            ->groupBy('Tasks.complexity')
            ->orderBy('Tasks.complexity')
            ->get();

        foreach ($acceptedTasks as $stat) {
            $complexityStats['labels'][] = 'Сложность ' . $stat->complexity;
            $complexityStats['data'][] = $stat->count;
        }

        // =====================================================================
        // НОВЫЙ БЛОК: 5. ПОЛУЧАЕМ ВАРИАНТЫ, ВЫДАННЫЕ ГРУППЕ УЧЕНИКА
        // =====================================================================
        $groupVariants = collect();
        $vSort = $request->input('v_sort', 'date_desc'); // Переменная для сортировки таблицы вариантов

        if ($student->group_id) {
            $queryVars = \App\Models\AssignmentHistory::query()
                ->select('Assignment_History.*') // Выбираем поля только из истории
                ->where('Assignment_History.group_id', $student->group_id)
                // Джоиним таблицы, чтобы по ним можно было сортировать
                ->join('Work_Variants', 'Assignment_History.work_variant_id', '=', 'Work_Variants.id')
                ->join('Works', 'Work_Variants.work_id', '=', 'Works.id')
                ->with(['variant.work']); // Жадная загрузка для вывода
            
            // Логика сортировки колонок таблицы
            switch ($vSort) {
                case 'work_asc':     $queryVars->orderBy('Works.title', 'asc'); break;
                case 'work_desc':    $queryVars->orderBy('Works.title', 'desc'); break;
                case 'variant_asc':  $queryVars->orderBy('Work_Variants.name', 'asc'); break;
                case 'variant_desc': $queryVars->orderBy('Work_Variants.name', 'desc'); break;
                case 'date_asc':     $queryVars->orderBy('Assignment_History.assigned_at', 'asc'); break;
                case 'date_desc':
                default:             $queryVars->orderBy('Assignment_History.assigned_at', 'desc'); break;
            }

            $groupVariants = $queryVars->paginate(15, ['*'], 'vp')->withQueryString();
        }

        return view('assignments.index', compact(
            'assignments', 'statusCounts', 'totalCount', 'topics', 'complexityStats', 'sort', 'groupVariants', 'vSort'
        ));
    }

    /**
     * Страница "Мой прогресс" (Сводка по темам)
     */
    public function progress()
    {
        $studentId = auth()->id();

        // 1. Получаем дерево тем для красивого вывода
        $topicsTree = \App\Models\Topic::whereNull('parent_id')
                        ->orWhere('parent_id', 0)
                        ->with('children')
                        ->orderBy('sorting_num')
                        ->get();

        // 2. Считаем, сколько вообще задач есть в базе по каждой теме (Группировка)
        $totalTasksDb = \App\Models\Task::selectRaw('topic_id, count(*) as count')
                        ->groupBy('topic_id')
                        ->pluck('count', 'topic_id');

        // 3. Получаем ВСЕ назначения ученика одним запросом (JOIN с задачами, чтобы знать сложность)
        $assignments = StudentAssignment::where('student_id', $studentId)
            ->join('Tasks', 'Student_Assignments.task_id', '=', 'Tasks.id')
            ->select('Student_Assignments.*', 'Tasks.topic_id', 'Tasks.complexity')
            ->get();

        // 4. Формируем массив статистики для каждой темы
        $stats = [];
        $allTopics = \App\Models\Topic::all(); // Плоский список всех тем
        
        foreach ($allTopics as $topic) {
            // Отбираем задания только для текущей темы
            $topicAssigns = $assignments->where('topic_id', $topic->id);
            
            // Разделяем на решенные и нерешенные
            $solved = $topicAssigns->where('status', 'accepted');
            $unsolved = $topicAssigns->where('status', '!=', 'accepted');

            $stats[$topic->id] = [
                'total_db' => $totalTasksDb[$topic->id] ?? 0,
                'assigned' => $topicAssigns->count(),
                'solved' => $solved->count(),
                'submitted' => $topicAssigns->where('status', 'submitted')->count(),
                // Считаем средние значения (если задач нет, будет null)
                'avg_score' => $solved->avg('mark_percent'),
                'avg_comp_solved' => $solved->avg('complexity'),
                'avg_comp_unsolved' => $unsolved->avg('complexity'),
            ];
        }

        return view('assignments.progress', compact('topicsTree', 'stats'));
    }

    /**
     * Просмотр варианта работы учеником
     */
    public function showVariant(\App\Models\WorkVariant $variant)
    {
        $student = auth()->user();

        // Защита: Убеждаемся, что вариант действительно выдан группе этого ученика
        $isAssignedToStudentGroup = \App\Models\AssignmentHistory::where('work_variant_id', $variant->id)
                                        ->where('group_id', $student->group_id)
                                        ->exists();

        if (!$isAssignedToStudentGroup) {
            abort(403, 'Доступ запрещен. Этот вариант не назначен вашей группе.');
        }

        // Получаем задачи варианта (вместе с картинками условий)
        $variantTasks = $variant->tasks()->with('taskImages')->get();

        return view('assignments.variant', compact('variant', 'variantTasks'));
    }
}