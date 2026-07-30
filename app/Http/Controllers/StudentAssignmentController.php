<?php

namespace App\Http\Controllers;

use App\Models\StudentAssignment;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

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
            'solution_files.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:10240', // до 10 МБ
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
}