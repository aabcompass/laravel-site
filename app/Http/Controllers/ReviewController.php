<?php

namespace App\Http\Controllers;

use App\Models\StudentAssignment;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Страница проверки решения
     */
    public function show(StudentAssignment $assignment)
    {
        $user = auth()->user();
        
        // ЗАЩИТА: Проверить может админ, либо тот, кто назначил, либо текущий репетитор ученика
        $isTutor = $user->tutoredStudents()->where('student_id', $assignment->student_id)->exists();
        if (!$user->hasRole('admin') && $assignment->assigner_id !== $user->id && !$isTutor) {
            abort(403, 'У вас нет прав на проверку этой работы.');
        }

        // Подгружаем все необходимые связи (чтобы страница загрузилась за 1 запрос к БД)
        $assignment->load([
            'student', 
            'task.topic', 
            'task.taskImages', 
            'task.solutionImages', 
            'attachments'
        ]);

        return view('assignments.review', compact('assignment'));
    }

    /**
     * Сохранение результатов проверки
     */
    public function update(Request $request, StudentAssignment $assignment)
    {
        $user = auth()->user();
        
        $isTutor = $user->tutoredStudents()->where('student_id', $assignment->student_id)->exists();
        if (!$user->hasRole('admin') && $assignment->assigner_id !== $user->id && !$isTutor) {
            abort(403);
        }

        // Проверяем, на какую кнопку нажал учитель
        $status = $request->input('action_status'); // 'accepted' или 'revision_needed'
        
        if (!in_array($status, ['accepted', 'revision_needed'])) {
            return back()->with('error', 'Неверный статус проверки.');
        }

        $request->validate([
            'teacher_comment' => 'nullable|string',
            'mark_percent' => 'nullable|integer|min:0|max:100',
        ]);

        // Сохраняем вердикт
        $assignment->update([
            'teacher_comment' => $request->teacher_comment,
            'mark_percent' => $request->mark_percent,
            'status' => $status,
            'reviewer_id' => $user->id,
            'checked_at' => now(),
        ]);

        $assignment->load(['task', 'student']);
        $assignment->student->notify(new \App\Notifications\TelegramAssignmentNotification($assignment, 'reviewed'));

        return redirect()->route('tutors.matrix')->with('success', "Работа ученика {$assignment->student->last_name} проверена!");
    }
}