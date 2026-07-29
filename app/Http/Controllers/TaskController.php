<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Topic;
use App\Models\Source;
use App\Models\User;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TaskController extends Controller
{
    // --- ВЫВОД СПИСКА ---
    public function index(Request $request)
    {
        // 1. Получаем параметры из URL (с дефолтными значениями)
        $perPage = $request->input('per_page', 100);
        $sortField = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'desc');

        // 2. Начинаем строить запрос
        $query = Task::query()
            // Подгружаем жадно (Eager Loading) всё, что понадобится для отрисовки карточек и popup'ов
            ->with(['topic', 'source', 'author', 'taskImages'])
            // Магия Laravel: автоматически добавит свойство $task->variants_count
            ->withCount('variants');

        // 3. ФИЛЬТРЫ (when выполняет код, только если значение передано и не пустое)
        $query->when($request->topic_id, fn($q, $v) => $q->where('topic_id', $v));
        $query->when($request->source_id, fn($q, $v) => $q->where('source_id', $v));
        $query->when($request->author_id, fn($q, $v) => $q->where('author_id', $v));

        // 4. ПОИСК (Если ввели число - ищем по ID, если текст - ищем по содержимому)
        $query->when($request->search, function($q, $v) {
            if (is_numeric($v)) {
                $q->where('id', $v);
            } else {
                $q->where('task_text', 'like', "%{$v}%");
            }
        });

        // 5. СОРТИРОВКА (Защита "от дурака" - проверяем, что поля разрешены)
        $allowedSorts = ['id', 'complexity'];
        $sortField = in_array($sortField, $allowedSorts) ? $sortField : 'id';
        $sortDir = in_array($sortDir, ['asc', 'desc']) ? $sortDir : 'desc';
        
        $query->orderBy($sortField, $sortDir);

        // 6. ВЫПОЛНЯЕМ ЗАПРОС И ПАГИНИРУЕМ
        // withQueryString() - это та самая магия, которая сохраняет все текущие фильтры в URL при переходе по страницам пагинации!
        $tasks = $query->paginate($perPage)->withQueryString();

        // 7. ДАННЫЕ ДЛЯ ВЫПАДАЮЩИХ СПИСКОВ В ФИЛЬТРЕ
        $topics = Topic::whereNull('parent_id')->orWhere('parent_id', 0)->with('children')->orderBy('sorting_num')->get();
        $sources = Source::whereNull('parent_id')->orWhere('parent_id', 0)->with('children')->orderBy('sorting_num')->get();
        $users = User::orderBy('last_name')->get();

        return view('tasks.index', compact('tasks', 'topics', 'sources', 'users', 'perPage', 'sortField', 'sortDir'));
    }

    // --- ФОРМА СОЗДАНИЯ ---
    public function create()
    {
        $topics = Topic::whereNull('parent_id')->orWhere('parent_id', 0)->with('children')->orderBy('sorting_num')->get();
        $sources = Source::whereNull('parent_id')->orWhere('parent_id', 0)->with('children')->orderBy('sorting_num')->get();
        // В вашей старой базе учителя - это role_id = 2. Для простоты пока берем всех
        $users = User::orderBy('last_name')->get(); 

        return view('tasks.edit', compact('topics', 'sources', 'users'));
    }

    // --- СОХРАНЕНИЕ НОВОЙ ЗАДАЧИ ---
    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $task = Task::create($data);

        // Обрабатываем файлы
        $this->processUploads($request->file('task_images'), $request->input('task_images_scales'), $task->id, 'task');
        $this->processUploads($request->file('solution_images'), $request->input('solution_images_scales'), $task->id, 'author_solution');

        return redirect()->route('tasks.edit', $task->id)->with('success', "Задача №{$task->id} успешно создана!");
    }

    // --- ФОРМА РЕДАКТИРОВАНИЯ ---
    public function edit(Task $task)
    {
        // Жадная загрузка связей картинок
        $task->load(['taskImages', 'solutionImages']);

        $topics = Topic::whereNull('parent_id')->orWhere('parent_id', 0)->with('children')->orderBy('sorting_num')->get();
        $sources = Source::whereNull('parent_id')->orWhere('parent_id', 0)->with('children')->orderBy('sorting_num')->get();
        $users = User::orderBy('last_name')->get();

        return view('tasks.edit', compact('task', 'topics', 'sources', 'users'));
    }

    // --- ОБНОВЛЕНИЕ ЗАДАЧИ ---
    public function update(Request $request, Task $task)
    {
        $data = $request->validate($this->rules());
        $task->update($data);

        // Обрабатываем новые файлы (если они загружены)
        $this->processUploads($request->file('task_images'), $request->input('task_images_scales'), $task->id, 'task');
        $this->processUploads($request->file('solution_images'), $request->input('solution_images_scales'), $task->id, 'author_solution');

        return back()->with('success', 'Изменения сохранены.');
    }

    public function destroy(Task $task)
    {
        // ПРОВЕРКА: Если задача привязана хотя бы к одному варианту - блокируем удаление
        if ($task->variants()->exists()) {
            return back()->with('error', 'Нельзя удалить задачу: она уже используется в вариантах работ.');
        }

        // Физическое удаление картинок
        $attachments = Attachment::where('attachable_id', $task->id)
            ->whereIn('attachable_type', ['task', 'author_solution'])->get();
        
        foreach ($attachments as $attachment) {
            $this->deletePhysicalFile($attachment);
        }

        $task->delete();
        return redirect()->route('tasks.index')->with('success', 'Задача успешно удалена.');
    }

    // --- УДАЛЕНИЕ ОТДЕЛЬНОЙ КАРТИНКИ (AJAX или форма) ---
    public function destroyAttachment(Attachment $attachment)
    {
        $this->deletePhysicalFile($attachment);
        $attachment->delete();
        return back()->with('success', 'Изображение удалено.');
    }

    // --- КОПИРОВАНИЕ ЗАДАЧИ (РЕПЛИКАЦИЯ) ---
    public function copy(Task $task)
    {
        // 1. Копируем саму задачу (без ID и дат)
        $newTask = $task->replicate();
        $newTask->save();

        // 2. Функция для копирования картинок
        $copyImages = function($images, $type) use ($newTask) {
            foreach ($images as $img) {
                // Генерируем новое имя по старому правилу
                $extension = pathinfo($img->file_path, PATHINFO_EXTENSION);
                $newFilename = uniqid('task_img_', true) . '.' . $extension;
                $newPath = 'uploads/' . $newFilename;

                // Физически копируем файл
                if (File::exists(public_path($img->file_path))) {
                    File::copy(public_path($img->file_path), public_path($newPath));
                }

                // Создаем запись в БД
                $newImg = $img->replicate();
                $newImg->attachable_id = $newTask->id;
                $newImg->file_path = $newPath;
                $newImg->save();
            }
        };

        $copyImages($task->taskImages, 'task');
        $copyImages($task->solutionImages, 'author_solution');

        return redirect()->route('tasks.edit', $newTask->id)->with('success', 'Задача успешно скопирована. Вы редактируете новую задачу.');
    }

    // ====== Вспомогательные приватные методы ======

    private function rules()
    {
        return [
            'topic_id' => 'required|integer',
            'source_id' => 'nullable|integer',
            'author_id' => 'required|integer',
            'complexity' => 'required|integer|min:1|max:255',
            'task_text' => 'required|string',
            'answer_numeric' => 'nullable|numeric',
            'answer_units' => 'nullable|string|max:50',
            'advice_text' => 'nullable|string',
            'author_solution_text' => 'nullable|string',
        ];
    }

    private function processUploads($files, $scales, $taskId, $type)
    {
        if (!$files) return;

        foreach ($files as $index => $file) {
            // 1. СНАЧАЛА собираем все данные о файле (пока он во временной папке)
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();
            $extension = $file->getClientOriginalExtension();
            
            // 2. Генерируем безопасное имя
            $safeFilename = uniqid('task_img_', true) . '.' . $extension;
            
            // 3. ПЕРЕМЕЩАЕМ файл в постоянную папку
            $file->move(public_path('uploads'), $safeFilename);

            // 4. Определяем масштаб (30% по умолчанию)
            $scale = isset($scales[$index]) ? (int)$scales[$index] : 30;

            // 5. Записываем информацию в базу данных
            Attachment::create([
                'attachable_id' => $taskId,
                'attachable_type' => $type,
                'uploader_id' => auth()->id(),
                'file_path' => 'uploads/' . $safeFilename,
                'original_filename' => $originalName,
                'mime_type' => $mimeType,
                'file_size_bytes' => $fileSize,
                'scale' => max(1, min(100, $scale))
            ]);
        }
    }

    private function deletePhysicalFile($attachment)
    {
        $filePath = public_path($attachment->file_path);
        if (File::exists($filePath)) {
            File::delete($filePath);
        }
    }
}