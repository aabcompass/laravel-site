<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TutorController extends Controller
{
    /**
     * Страница "Мои ученики"
     */
    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin');

        // Если админ - показываем всех учителей и их учеников. Если обычный учитель - только его.
        if ($isAdmin) {
            $teachers = User::whereHas('roles', fn($q) => $q->whereIn('name', ['teacher', 'author', 'admin']))
                ->with(['tutoredStudents' => fn($q) => $q->orderBy('last_name')])
                ->orderBy('last_name')
                ->get();
        } else {
            // Оборачиваем одного учителя в коллекцию для универсальности шаблона
            $user->load(['tutoredStudents' => fn($q) => $q->orderBy('last_name')]);
            $teachers = collect([$user]);
        }

        // Список всех учеников для выпадающего списка добавления
        $allStudents = User::whereHas('roles', fn($q) => $q->where('name', 'advanced_student'))
                           ->orderBy('last_name')
                           ->get();

        return view('tutors.index', compact('teachers', 'allStudents', 'isAdmin'));
    }

    /**
     * Добавление ученика к учителю
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:Users,id',
            'teacher_id' => 'nullable|exists:Users,id' // Передается только админом
        ]);

        // Определяем, кому добавляем ученика
        $teacherId = auth()->user()->hasRole('admin') && $request->teacher_id 
                        ? $request->teacher_id 
                        : auth()->id();

        // Проверяем, нет ли уже такой связи
        $exists = DB::table('Tutor_Students')
            ->where('teacher_id', $teacherId)
            ->where('student_id', $request->student_id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Этот ученик уже добавлен к преподавателю.');
        }

        DB::table('Tutor_Students')->insert([
            'teacher_id' => $teacherId,
            'student_id' => $request->student_id,
            'created_at' => now(),
        ]);

        return back()->with('success', 'Ученик успешно добавлен!');
    }

    /**
     * Удаление ученика от учителя
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:Users,id',
            'teacher_id' => 'required|exists:Users,id'
        ]);

        // Удалять может либо сам учитель (своего ученика), либо админ
        if ($request->teacher_id != auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403, 'Вы можете удалять только своих учеников.');
        }

        DB::table('Tutor_Students')
            ->where('teacher_id', $request->teacher_id)
            ->where('student_id', $request->student_id)
            ->delete();

        return back()->with('success', 'Ученик убран из списка подопечных.');
    }
}