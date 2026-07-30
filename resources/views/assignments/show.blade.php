<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <!-- Пока ведем на главную, позже сделаем страницу "Мои задания" -->
            <a href="/dashboard" class="text-gray-500 hover:text-blue-600 transition">
                &larr; Назад
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Решение задачи №{{ $assignment->task_id }}
            </h2>
        </div>
    </x-slot>

    <!-- Инициализация MathJax -->
    <script>
        MathJax = { tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] }, svg: { fontCache: 'global' } };
    </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>
    <style> mjx-container svg { display: inline; } mjx-container[jax="SVG"][display="true"] { display: block; margin: 1em 0; } </style>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Флэш-сообщения -->
            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-700 rounded-lg shadow-sm">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 bg-red-100 text-red-700 rounded-lg shadow-sm">{{ session('error') }}</div>
            @endif

            <!-- БЛОК 1: УСЛОВИЕ ЗАДАЧИ -->
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden border border-gray-200">
                <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
                    <h3 class="font-bold text-lg text-gray-800">Условие задачи</h3>
                    <span class="text-xs font-bold bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Сложность: {{ $assignment->task->complexity }}</span>
                </div>
                <div class="p-6 text-gray-800 text-base leading-relaxed">
                    {!! nl2br(e($assignment->task->task_text)) !!}

                    @if($assignment->task->taskImages->count() > 0)
                        <div class="mt-6 flex flex-wrap gap-4">
                            @foreach($assignment->task->taskImages as $img)
                                <img src="{{ asset($img->file_path) }}" alt="Рисунок к задаче" style="width: {{ $img->scale }}%" class="rounded border shadow-sm object-contain">
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- БЛОК 2: ОТЗЫВ УЧИТЕЛЯ (Показываем только если работа проверена) -->
            @if(in_array($assignment->status, ['revision_needed', 'accepted']))
                <div class="shadow-sm sm:rounded-lg overflow-hidden border-l-4 {{ $assignment->status == 'accepted' ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50' }}">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-bold text-lg {{ $assignment->status == 'accepted' ? 'text-green-800' : 'text-red-800' }}">Комментарий учителя</h3>
                            @if($assignment->mark_percent !== null)
                                <span class="text-xl font-black {{ $assignment->status == 'accepted' ? 'text-green-700' : 'text-red-700' }}">Оценка: {{ $assignment->mark_percent }}%</span>
                            @endif
                        </div>
                        <p class="text-gray-800 italic">{!! nl2br(e($assignment->teacher_comment ?? 'Комментарий отсутствует.')) !!}</p>
                        
                        @if($assignment->reviewer)
                            <div class="mt-4 text-right text-sm text-gray-500">
                                Проверил(а): {{ $assignment->reviewer->last_name }} {{ $assignment->reviewer->first_name }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- БЛОК 3: АВТОРСКОЕ РЕШЕНИЕ (Только если работа Принята) -->
            @if($assignment->status == 'accepted')
                <div class="bg-purple-50 shadow-sm sm:rounded-lg overflow-hidden border-l-4 border-purple-500">
                    <div class="p-6">
                        <h3 class="font-bold text-lg text-purple-800 mb-4">Эталонное решение</h3>
                        
                        @if($assignment->task->answer_numeric !== null)
                            <div class="mb-4 bg-white inline-block px-4 py-2 rounded shadow-sm border border-purple-100 text-purple-900 font-bold">
                                Правильный ответ: {{ $assignment->task->answer_numeric }} {{ $assignment->task->answer_units }}
                            </div>
                        @endif

                        <div class="text-gray-800">
                            {!! nl2br(e($assignment->task->author_solution_text)) !!}
                        </div>

                        @if($assignment->task->solutionImages->count() > 0)
                            <div class="mt-4 flex flex-wrap gap-4">
                                @foreach($assignment->task->solutionImages as $img)
                                    <img src="{{ asset($img->file_path) }}" style="width: {{ $img->scale }}%" class="rounded border shadow-sm object-contain">
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- БЛОК 4: РАБОЧАЯ ОБЛАСТЬ УЧЕНИКА -->
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden border border-gray-200">
                <div class="bg-blue-50 px-6 py-4 border-b">
                    <h3 class="font-bold text-lg text-blue-800">Ваше решение</h3>
                </div>
                
                <div class="p-6">
                    <!-- ЕСЛИ МОЖНО РЕДАКТИРОВАТЬ -->
                    @if(in_array($assignment->status, ['assigned', 'revision_needed']))
                        
                        <form action="{{ route('assignments.submit', $assignment->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                            @csrf

                            <!-- Численный ответ (если требуется задачей) -->
                            @if($assignment->task->answer_numeric !== null)
                                <div>
                                    <label class="block font-medium text-sm text-gray-700 mb-1">Числовой ответ</label>
                                    <div class="flex items-center gap-3">
                                        <input type="number" step="any" name="answer_numeric" value="{{ old('answer_numeric', $assignment->answer_numeric) }}" class="border-gray-300 rounded-md shadow-sm w-48 focus:ring-blue-500 focus:border-blue-500">
                                        @if($assignment->task->answer_units)
                                            <span class="text-gray-600 font-bold">{{ $assignment->task->answer_units }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Текст решения с предпросмотром -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <label class="block font-medium text-sm text-gray-700 mb-1">Текст решения</label>
                                    <textarea id="solution_text" name="solution_text" rows="8" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Напишите решение здесь (можно использовать $ формулы $)...">{{ old('solution_text', $assignment->solution_text) }}</textarea>
                                </div>
                                <div class="bg-gray-50 border rounded-md p-4 flex flex-col">
                                    <span class="text-xs text-gray-500 uppercase font-bold mb-2">Предпросмотр формул</span>
                                    <div id="preview_solution_text" class="flex-1 overflow-auto text-gray-800"></div>
                                </div>
                            </div>

                            <!-- Существующие картинки -->
                            @if($assignment->attachments->count() > 0)
                                <div>
                                    <label class="block font-medium text-sm text-gray-700 mb-2">Загруженные файлы</label>
                                    <div class="flex flex-wrap gap-4 p-4 bg-gray-50 rounded border">
                                        @foreach($assignment->attachments as $att)
                                            <div class="relative bg-white border p-2 rounded shadow-sm w-32 flex flex-col items-center">
                                                @if(Str::startsWith($att->mime_type, 'image/'))
                                                    <img src="{{ asset($att->file_path) }}" class="h-20 object-cover mb-2 rounded">
                                                @else
                                                    <div class="h-20 flex items-center justify-center text-4xl mb-2 text-gray-400">📄</div>
                                                @endif
                                                <span class="text-xs text-gray-500 truncate w-full text-center" title="{{ $att->original_filename }}">{{ $att->original_filename }}</span>
                                                
                                                <!-- Кнопка удаления файла -->
                                                <button type="button" onclick="deleteAttachment({{ $att->id }})" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-700 shadow">&times;</button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Загрузка новых картинок (Alpine.js) -->
                            <div x-data="fileUploadComponent()" class="space-y-4">
                                <div>
                                    <label class="block font-medium text-sm text-gray-700 mb-2">Добавить фото решения</label>
                                    <!-- Скрытый инпут -->
                                    <input type="file" name="solution_files[]" id="file_input" multiple class="hidden" @change="handleFiles" accept="image/*,.pdf">
                                    
                                    <!-- Красивая кнопка-область -->
                                    <div @click="document.getElementById('file_input').click()" @dragover.prevent="dragover = true" @dragleave.prevent="dragover = false" @drop.prevent="handleDrop" :class="dragover ? 'bg-blue-100 border-blue-500' : 'bg-gray-50 border-gray-300'" class="border-2 border-dashed rounded-lg p-6 text-center cursor-pointer hover:bg-gray-100 transition">
                                        <div class="text-blue-500 font-bold text-lg mb-1">+ Нажмите или перетащите файлы сюда</div>
                                        <div class="text-xs text-gray-500">JPG, PNG, PDF (До 10 МБ)</div>
                                    </div>
                                </div>

                                <!-- Предпросмотр выбранных файлов перед отправкой -->
                                <template x-if="files.length > 0">
                                    <div class="p-4 border rounded-lg bg-blue-50">
                                        <h4 class="text-sm font-bold text-blue-800 mb-3">Готовы к загрузке:</h4>
                                        <div class="flex flex-wrap gap-4">
                                            <template x-for="(file, index) in files" :key="index">
                                                <div class="relative bg-white border p-2 rounded shadow-sm w-24 flex flex-col items-center">
                                                    <template x-if="file.isImage">
                                                        <img :src="file.preview" class="h-16 object-cover rounded mb-1">
                                                    </template>
                                                    <template x-if="!file.isImage">
                                                        <div class="h-16 flex items-center justify-center text-3xl mb-1 text-gray-400">📄</div>
                                                    </template>
                                                    <span class="text-xs text-gray-500 truncate w-full text-center" x-text="file.name"></span>
                                                    <button type="button" @click="removeFile(index)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center hover:bg-red-700 shadow text-xs">&times;</button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="pt-4 border-t">
                                <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded shadow text-lg transition">
                                    {{ $assignment->status == 'revision_needed' ? 'Отправить исправленное решение' : 'Отправить на проверку' }}
                                </button>
                            </div>
                        </form>

                    <!-- ЕСЛИ РАБОТА УЖЕ ОТПРАВЛЕНА / ПРИНЯТА (Только просмотр) -->
                    @else
                        @if($assignment->status == 'submitted')
                            <div class="flex justify-between items-center mb-6 bg-yellow-50 p-4 border border-yellow-200 rounded-lg">
                                <div class="text-yellow-800">
                                    <span class="font-bold block">Работа ожидает проверки учителем.</span>
                                    <span class="text-sm">Отправлено: {{ $assignment->submitted_at->format('d.m.Y H:i') }}</span>
                                </div>
                                <form action="{{ route('assignments.recall', $assignment->id) }}" method="POST" onsubmit="return confirm('Вы уверены, что хотите вернуть работу на доработку?')">
                                    @csrf
                                    <button type="submit" class="bg-white border border-yellow-400 text-yellow-700 hover:bg-yellow-100 px-4 py-2 rounded text-sm font-bold shadow-sm transition">
                                        Отозвать работу
                                    </button>
                                </form>
                            </div>
                        @endif

                        @if($assignment->answer_numeric !== null)
                            <div class="mb-4 text-gray-800">
                                <span class="font-bold">Ваш ответ:</span> {{ $assignment->answer_numeric }} {{ $assignment->task->answer_units }}
                            </div>
                        @endif

                        <div class="text-gray-800 bg-gray-50 p-4 rounded border">
                            {!! nl2br(e($assignment->solution_text ?? 'Текст не введен.')) !!}
                        </div>

                        @if($assignment->attachments->count() > 0)
                            <div class="mt-4 flex flex-wrap gap-4">
                                @foreach($assignment->attachments as $att)
                                    <a href="{{ asset($att->file_path) }}" target="_blank" class="block relative bg-white border p-2 rounded shadow-sm hover:shadow transition">
                                        @if(Str::startsWith($att->mime_type, 'image/'))
                                            <img src="{{ asset($att->file_path) }}" class="h-32 object-contain rounded">
                                        @else
                                            <div class="h-32 w-24 flex items-center justify-center text-4xl text-gray-400">📄</div>
                                        @endif
                                        <div class="text-xs text-center mt-2 text-gray-600 truncate w-full max-w-[120px]">{{ $att->original_filename }}</div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
            </div>

        </div>
    </div>

    <!-- Форма-невидимка для удаления загруженных картинок (AJAX/POST) -->
    <form id="delete-attachment-form" method="POST" class="hidden">
        @csrf @method('DELETE')
    </form>

    <!-- Скрипты (MathJax и AlpineJS) -->
    <script>
        // Удаление файла с сервера
        function deleteAttachment(attachmentId) {
            if (confirm('Точно удалить этот файл? Действие нельзя отменить.')) {
                const form = document.getElementById('delete-attachment-form');
                form.action = `/assignments/{{ $assignment->id }}/attachments/${attachmentId}`;
                form.submit();
            }
        }

        // Рендеринг MathJax (Debounce)
        function debounce(func, wait) {
            let timeout;
            return function (...args) { clearTimeout(timeout); timeout = setTimeout(() => func(...args), wait); };
        }
        function renderMath(sourceId, targetId) {
            const source = document.getElementById(sourceId);
            const target = document.getElementById(targetId);
            if (source && target) {
                target.innerHTML = source.value.replace(/\n/g, '<br>');
                if (typeof MathJax !== 'undefined' && MathJax.typesetPromise) MathJax.typesetPromise([target]);
            }
        }
        document.addEventListener('DOMContentLoaded', () => {
            const solInput = document.getElementById('solution_text');
            if (solInput) {
                renderMath('solution_text', 'preview_solution_text');
                solInput.addEventListener('input', debounce(() => renderMath('solution_text', 'preview_solution_text'), 500));
            }
        });

        // Alpine.js Компонент для красивого drag&drop загрузчика
        function fileUploadComponent() {
            return {
                dragover: false,
                files: [],
                handleDrop(event) {
                    this.dragover = false;
                    const droppedFiles = event.dataTransfer.files;
                    document.getElementById('file_input').files = droppedFiles; // Передаем файлы в реальный инпут
                    this.processFiles(droppedFiles);
                },
                handleFiles(event) {
                    this.processFiles(event.target.files);
                },
                processFiles(fileList) {
                    this.files = [];
                    for (let i = 0; i < fileList.length; i++) {
                        let file = fileList[i];
                        let isImage = file.type.startsWith('image/');
                        let fileObj = { name: file.name, isImage: isImage, preview: null };
                        
                        if (isImage) {
                            let reader = new FileReader();
                            reader.onload = (e) => { fileObj.preview = e.target.result; };
                            reader.readAsDataURL(file);
                        }
                        this.files.push(fileObj);
                    }
                },
                removeFile(index) {
                    // При удалении нам нужно создать новый объект FileList (это хак JS)
                    const dt = new DataTransfer();
                    const input = document.getElementById('file_input');
                    const currentFiles = input.files;
                    for (let i = 0; i < currentFiles.length; i++) {
                        if (i !== index) dt.items.add(currentFiles[i]);
                    }
                    input.files = dt.files;
                    this.files.splice(index, 1);
                }
            }
        }
    </script>
</x-app-layout>