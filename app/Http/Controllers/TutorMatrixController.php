<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Topic;
use App\Models\Source;
use App\Models\StudentAssignment;
use Illuminate\Http\Request;

class TutorMatrixController extends Controller
{
    /**
     * Отображение матрицы
     */
    public function index(Request $request)
    {
        $teacher = auth()->user();
        
        // Получаем подопечных этого учителя
        $students = $teacher->tutoredStudents()->orderBy('last_name')->get();

        // Формируем запрос для задач
        $query = Task::query()->with(['topic', 'taskImages']);

        $query->when($request->topic_id, fn($q, $v) => $q->where('topic_id', $v));
        $query->when($request->source_id, fn($q, $v) => $q->where('source_id', $v));
        $query->when($request->search, function($q, $v) {
            if (is_numeric($v)) {
                $q->where('id', $v);
            } else {
                $q->where('task_text', 'like', "%{$v}%");
            }
        });

        // Берем по 100 задач на страницу, чтобы матрица была плотной
        $tasks = $query->orderBy('id', 'desc')->paginate(100)->withQueryString();

        // Получаем все назначения для этих учеников И этих задач (чтобы не грузить лишнее)
        $assignments = StudentAssignment::whereIn('student_id', $students->pluck('id'))
            ->whereIn('task_id', $tasks->pluck('id'))
            ->get();

        // Собираем быструю матрицу в памяти: $matrix[task_id][student_id] = Assignment
        $matrix = [];
        foreach ($assignments as $assignment) {
            $matrix[$assignment->task_id][$assignment->student_id] = $assignment;
        }

        $topics = Topic::whereNull('parent_id')->orWhere('parent_id', 0)->with('children')->orderBy('sorting_num')->get();
        $sources = Source::whereNull('parent_id')->orWhere('parent_id', 0)->with('children')->orderBy('sorting_num')->get();

        return view('tutors.matrix', compact('students', 'tasks', 'matrix', 'topics', 'sources'));
    }

    /**
     * AJAX: Назначить задачу
     */
    public function assign(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:Tasks,id',
            'student_id' => 'required|exists:Users,id'
        ]);

        // Проверяем, что это ученик данного учителя (или что это админ)
        if (!auth()->user()->hasRole('admin') && !auth()->user()->tutoredStudents()->where('student_id', $request->student_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Это не ваш ученик.']);
        }

        $assignment = StudentAssignment::firstOrCreate(
            ['task_id' => $request->task_id, 'student_id' => $request->student_id],
            ['assigner_id' => auth()->id(), 'status' => 'assigned', 'assigned_at' => now()]
        );

        return response()->json(['success' => true, 'assignment_id' => $assignment->id]);
    }

    /**
     * AJAX: Отменить назначение
     */
    public function unassign(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:Tasks,id',
            'student_id' => 'required|exists:Users,id'
        ]);

        $assignment = StudentAssignment::where('task_id', $request->task_id)
            ->where('student_id', $request->student_id)
            ->first();

        if ($assignment) {
            if ($assignment->status !== 'assigned') {
                return response()->json(['success' => false, 'message' => 'Нельзя отменить: ученик уже отправил решение.']);
            }
            $assignment->delete();
        }

        return response()->json(['success' => true]);
    }
}