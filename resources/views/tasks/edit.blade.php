<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isset($task) ? "Редактирование задачи №{$task->id}" : 'Создание новой задачи' }}
            </h2>
            
            <!-- Кнопка "Скопировать" (только в режиме редактирования) -->
            @if(isset($task))
                <form action="{{ route('tasks.copy', $task->id) }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="bg-gray-800 hover:bg-gray-700 text-white text-sm font-bold py-2 px-4 rounded shadow">
                        Скопировать задачу
                    </button>
                </form>
            @endif
        </div>
    </x-slot>
    
    <style>
        /* Отменяем блочное отображение SVG от Tailwind для формул MathJax */
        mjx-container svg {
            display: inline;
        }
        /* Чуть-чуть выравниваем формулы по тексту */
        mjx-container[jax="SVG"][display="true"] {
            display: block;
            margin: 1em 0;
        }
    </style>
    
    <!-- Подключаем MathJax (один раз на страницу) -->
    <script>
        MathJax = {
            tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] },
            svg: { fontCache: 'global' }
        };
    </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            
            @if (session('success'))
                <div class="mb-6 p-4 bg-green-100 text-green-700 rounded shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-100 text-red-700 rounded shadow-sm">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ isset($task) ? route('tasks.update', $task->id) : route('tasks.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @if(isset($task)) @method('PUT') @endif

                <!-- БЛОК 1: ОСНОВНЫЕ ПАРАМЕТРЫ -->
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold border-b pb-2 mb-4">Основные параметры</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium text-sm text-gray-700">Тема <span class="text-red-500">*</span></label>
                            <select name="topic_id" class="mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                                <option value="">-- Выберите тему --</option>
                                @include('topics.options', ['topics' => $topics, 'level' => 0, 'selectedId' => old('topic_id', $task->topic_id ?? null), 'currentId' => null])
                            </select>
                        </div>
                        
                        <div>
                            <label class="block font-medium text-sm text-gray-700">Источник</label>
                            <select name="source_id" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">-- Не указан --</option>
                                <!-- Используем тот же шаблон options, только передаем источники -->
                                @include('topics.options', ['topics' => $sources, 'level' => 0, 'selectedId' => old('source_id', $task->source_id ?? null), 'currentId' => null])
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Кто добавил <span class="text-red-500">*</span></label>
                            <select name="author_id" class="mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('author_id', $task->author_id ?? auth()->id()) == $user->id ? 'selected' : '' }}>
                                        {{ $user->last_name }} {{ $user->first_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700">Сложность (1-255) <span class="text-red-500">*</span></label>
                            <input type="number" name="complexity" value="{{ old('complexity', $task->complexity ?? 1) }}" min="1" max="255" class="mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                    </div>
                </div>

                <!-- БЛОК 2: УСЛОВИЕ ЗАДАЧИ -->
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold border-b pb-2 mb-4">Условие задачи</h3>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Поле ввода -->
                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">Текст (LaTeX)</label>
                            <textarea id="task_text" name="task_text" rows="8" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="Введите условие...">{{ old('task_text', $task->task_text ?? '') }}</textarea>
                        </div>
                        
                        <!-- Живой предпросмотр -->
                        <div class="bg-gray-50 border rounded-md p-4 flex flex-col">
                            <span class="text-xs text-gray-500 uppercase font-bold mb-2">Предпросмотр</span>
                            <div id="preview_task_text" class="flex-1 overflow-auto text-gray-800" style="min-height: 150px;"></div>
                        </div>
                    </div>

                    <!-- Картинки (Условие) -->
                    <div class="mt-6 border-t pt-4">
                        <label class="block font-medium text-sm text-gray-700 mb-2">Прикрепленные изображения к условию</label>
                        
                        <!-- Существующие картинки (только при редактировании) -->
                        @if(isset($task) && $task->taskImages->count() > 0)
                            <div class="flex flex-wrap gap-4 mb-4 p-4 bg-gray-50 rounded">
                                @foreach($task->taskImages as $img)
                                    <div class="relative bg-white border p-2 rounded shadow-sm flex flex-col items-center" style="width: 150px;">
                                        <img src="{{ asset($img->file_path) }}" class="max-h-24 object-contain mb-2">
                                        <span class="text-xs text-gray-500">Масштаб: {{ $img->scale }}%</span>
                                        
                                        <!-- Кнопка удаления картинки -->
                                        <button type="button" onclick="deleteAttachment({{ $img->id }})" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-700 shadow" title="Удалить">&times;</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Добавление новых -->
                        <div id="task_files_container" class="space-y-2">
                            <div class="flex items-center gap-4 bg-blue-50 p-3 rounded">
                                <input type="file" name="task_images[]" class="text-sm">
                                <label class="text-sm text-gray-600 flex items-center gap-2">
                                    Масштаб (%): <input type="number" name="task_images_scales[]" value="30" min="1" max="100" class="w-20 border-gray-300 rounded p-1">
                                </label>
                            </div>
                        </div>
                        <button type="button" onclick="addFileInput('task_files_container', 'task_images')" class="mt-2 text-sm text-blue-600 hover:underline font-bold">+ Добавить еще картинку</button>
                    </div>
                </div>

                <!-- БЛОК 3: РЕШЕНИЕ И ОТВЕТЫ -->
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold border-b pb-2 mb-4">Ответ и Решение</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block font-medium text-sm text-gray-700">Численный ответ</label>
                            <input type="number" step="any" name="answer_numeric" value="{{ old('answer_numeric', $task->answer_numeric ?? '') }}" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block font-medium text-sm text-gray-700">Единицы измерения</label>
                            <input type="text" name="answer_units" value="{{ old('answer_units', $task->answer_units ?? '') }}" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block font-medium text-sm text-gray-700">Подсказка</label>
                            <textarea name="advice_text" rows="2" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">{{ old('advice_text', $task->advice_text ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">Авторское решение (LaTeX)</label>
                            <textarea id="solution_text" name="author_solution_text" rows="8" class="w-full border-gray-300 rounded-md shadow-sm">{{ old('author_solution_text', $task->author_solution_text ?? '') }}</textarea>
                        </div>
                        <div class="bg-gray-50 border rounded-md p-4 flex flex-col">
                            <span class="text-xs text-gray-500 uppercase font-bold mb-2">Предпросмотр решения</span>
                            <div id="preview_solution_text" class="flex-1 overflow-auto text-gray-800" style="min-height: 150px;"></div>
                        </div>
                    </div>

                    <!-- Картинки (Решение) -->
                    <div class="mt-6 border-t pt-4">
                        <label class="block font-medium text-sm text-gray-700 mb-2">Прикрепленные изображения к решению</label>
                        
                        @if(isset($task) && $task->solutionImages->count() > 0)
                            <div class="flex flex-wrap gap-4 mb-4 p-4 bg-gray-50 rounded">
                                @foreach($task->solutionImages as $img)
                                    <div class="relative bg-white border p-2 rounded shadow-sm flex flex-col items-center" style="width: 150px;">
                                        <img src="{{ asset($img->file_path) }}" class="max-h-24 object-contain mb-2">
                                        <span class="text-xs text-gray-500">Масштаб: {{ $img->scale }}%</span>
                                        <button type="button" onclick="deleteAttachment({{ $img->id }})" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-700 shadow">&times;</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div id="solution_files_container" class="space-y-2">
                            <div class="flex items-center gap-4 bg-purple-50 p-3 rounded">
                                <input type="file" name="solution_images[]" class="text-sm">
                                <label class="text-sm text-gray-600 flex items-center gap-2">
                                    Масштаб (%): <input type="number" name="solution_images_scales[]" value="30" min="1" max="100" class="w-20 border-gray-300 rounded p-1">
                                </label>
                            </div>
                        </div>
                        <button type="button" onclick="addFileInput('solution_files_container', 'solution_images')" class="mt-2 text-sm text-purple-600 hover:underline font-bold">+ Добавить еще картинку</button>
                    </div>
                </div>

                <!-- КНОПКИ СОХРАНЕНИЯ -->
                <div class="flex items-center gap-4 bg-white p-4 shadow sm:rounded-lg">
                    <x-primary-button class="text-lg py-3 px-6">
                        {{ isset($task) ? 'Сохранить изменения' : 'Создать задачу' }}
                    </x-primary-button>
                    <a href="{{ route('tasks.index') }}" class="text-gray-600 hover:underline">Отмена</a>
                </div>

            </form>
        </div>
    </div>

    <!-- Форма-невидимка для удаления картинок через AJAX -->
    <form id="delete-attachment-form" method="POST" style="display: none;">
        @csrf @method('DELETE')
    </form>

    <!-- Скрипты для интерактивности -->
    <script>
        // 1. Функция Debounce (вызывает код только когда пользователь перестал печатать)
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => { clearTimeout(timeout); func(...args); };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // 2. Функция рендеринга MathJax
        function renderMath(sourceId, targetId) {
            const source = document.getElementById(sourceId);
            const target = document.getElementById(targetId);
            if (source && target) {
                // Заменяем переносы строк на <br> для HTML
                target.innerHTML = source.value.replace(/\n/g, '<br>');
                // Просим MathJax обработать формулы в этом div'е
                if (typeof MathJax !== 'undefined' && MathJax.typesetPromise) {
                    MathJax.typesetPromise([target]).catch((err) => console.error(err.message));
                }
            }
        }

        // 3. Вешаем слушатели на текстовые поля (задержка 500мс)
        document.addEventListener('DOMContentLoaded', () => {
            const taskInput = document.getElementById('task_text');
            if (taskInput) {
                // Рендерим сразу при загрузке страницы
                renderMath('task_text', 'preview_task_text');
                // И при каждом вводе текста (с задержкой)
                taskInput.addEventListener('input', debounce(() => renderMath('task_text', 'preview_task_text'), 500));
            }

            const solInput = document.getElementById('solution_text');
            if (solInput) {
                renderMath('solution_text', 'preview_solution_text');
                solInput.addEventListener('input', debounce(() => renderMath('solution_text', 'preview_solution_text'), 500));
            }
        });

        // 4. Добавление новых полей для файлов
        function addFileInput(containerId, inputName) {
            const container = document.getElementById(containerId);
            // Определяем цвет фона в зависимости от того, куда добавляем
            const bgColorClass = inputName === 'task_images' ? 'bg-blue-50' : 'bg-purple-50';
            
            const html = `
                <div class="flex items-center gap-4 ${bgColorClass} p-3 rounded mt-2">
                    <input type="file" name="${inputName}[]" class="text-sm">
                    <label class="text-sm text-gray-600 flex items-center gap-2">
                        Масштаб (%): <input type="number" name="${inputName}_scales[]" value="30" min="1" max="100" class="w-20 border-gray-300 rounded p-1">
                    </label>
                    <button type="button" onclick="this.parentElement.remove()" class="text-red-500 font-bold ml-auto" title="Убрать это поле">&times;</button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        // 5. Удаление существующих картинок (используем форму-невидимку)
        function deleteAttachment(id) {
            if (confirm('Точно удалить это изображение навсегда?')) {
                const form = document.getElementById('delete-attachment-form');
                form.action = `/attachments/${id}`;
                form.submit();
            }
        }
    </script>
</x-app-layout>