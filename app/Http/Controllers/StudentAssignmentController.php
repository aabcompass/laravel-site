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
        $studentId = auth()->id();

        // 1. СТАТИСТИКА ДЛЯ КЛИКАБЕЛЬНЫХ КНОПОК
        // Считаем количество задач по статусам
        $statusCounts = StudentAssignment::where('student_id', $studentId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        $totalCount = array_sum($statusCounts);

        // 2. ПОЛУЧАЕМ ТЕМЫ ДЛЯ ВЫПАДАЮЩЕГО СПИСКА
        // Берем только те темы, задачи из которых назначены этому студенту
        $topicIds = StudentAssignment::where('student_id', $studentId)
            ->join('Tasks', 'Student_Assignments.task_id', '=', 'Tasks.id')
            ->distinct()
            ->pluck('Tasks.topic_id');
            
        $topics = \App\Models\Topic::whereIn('id', $topicIds)->orderBy('name')->get();

        // 3. СТРОИМ ОСНОВНОЙ ЗАПРОС
        // Используем JOIN с Tasks, чтобы мы могли делать сортировку по сложности задачи (complexity)
        $query = StudentAssignment::query()
            ->where('student_id', $studentId)
            ->select('Student_Assignments.*') // Выбираем поля только из назначений
            ->join('Tasks', 'Student_Assignments.task_id', '=', 'Tasks.id')
            ->with(['task.topic', 'task.taskImages']); // Жадная загрузка

        // --- ФИЛЬТРЫ ---
        $query->when($request->status, fn($q, $v) => $q->where('status', $v));
        $query->when($request->topic_id, fn($q, $v) => $q->where('Tasks.topic_id', $v));

        // --- СОРТИРОВКА ---
        $sort = $request->input('sort', 'date_desc');
        switch ($sort) {
            case 'complexity_asc':
                $query->orderBy('Tasks.complexity', 'asc');
                break;
            case 'complexity_desc':
                $query->orderBy('Tasks.complexity', 'desc');
                break;
            case 'date_asc':
                $query->orderBy('Student_Assignments.assigned_at', 'asc');
                break;
            case 'date_desc':
            default:
                // Сначала задачи, требующие доработки, затем по дате назначения
                $query->orderByRaw("CASE WHEN status = 'revision_needed' THEN 0 ELSE 1 END")
                      ->orderBy('Student_Assignments.assigned_at', 'desc');
                break;
        }

        // --- ПАГИНАЦИЯ ---
        $assignments = $query->paginate(15)->withQueryString();

        // 4. ДАННЫЕ ДЛЯ ГРАФИКА СЛОЖНОСТИ (Только для 'accepted')
        $complexityStats = [];
        $acceptedTasks = StudentAssignment::where('student_id', $studentId)
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

        // Возвращаем в шаблон
        return view('assignments.index', compact(
            'assignments', 'statusCounts', 'totalCount', 'topics', 'complexityStats', 'sort'
        ));
    }
}