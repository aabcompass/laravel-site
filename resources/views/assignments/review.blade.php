<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('tutors.matrix') }}" class="text-gray-500 hover:text-blue-600 transition">&larr; В матрицу</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Проверка: {{ $assignment->student->last_name }} {{ $assignment->student->first_name }} <span class="text-gray-400 font-normal">/ Задача №{{ $assignment->task_id }}</span>
            </h2>
        </div>
    </x-slot>

    <!-- MathJax -->
    <script> MathJax = { tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] }, svg: { fontCache: 'global' } }; </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>
    <style> mjx-container svg { display: inline; } mjx-container[jax="SVG"][display="true"] { display: block; margin: 1em 0; } </style>

    <div class="py-6">
        <div class="max-w-[1920px] mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('error')) <div class="p-4 bg-red-100 text-red-700 rounded-lg shadow-sm font-bold">{{ session('error') }}</div> @endif
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                
                <!-- ЛЕВАЯ КОЛОНКА (ЭТАЛОН) -->
                <div class="space-y-6">
                    <!-- Условие задачи -->
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden border border-gray-200">
                        <div class="bg-gray-50 px-4 py-3 border-b flex justify-between items-center">
                            <h3 class="font-bold text-gray-800">Условие задачи</h3>
                            <span class="text-xs font-bold bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Сложность: {{ $assignment->task->complexity }}</span>
                        </div>
                        <div class="p-4 text-sm text-gray-800 leading-relaxed">
                            {!! nl2br(e($assignment->task->task_text)) !!}

                            @if($assignment->task->taskImages->count() > 0)
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach($assignment->task->taskImages as $img)
                                        <a href="{{ asset($img->file_path) }}" target="_blank">
                                            <img src="{{ asset($img->file_path) }}" class="h-20 object-contain border rounded shadow-sm hover:scale-[2] origin-left transition-transform duration-200 z-10 hover:z-50 relative bg-white">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Эталонное решение -->
                    <div class="bg-purple-50 shadow-sm sm:rounded-lg overflow-hidden border border-purple-200">
                        <div class="bg-purple-100 px-4 py-3 border-b border-purple-200">
                            <h3 class="font-bold text-purple-900">Эталонное решение</h3>
                        </div>
                        <div class="p-4 text-sm text-gray-800 leading-relaxed">
                            @if($assignment->task->answer_numeric !== null)
                                <div class="mb-3 bg-white inline-block px-3 py-1.5 rounded shadow-sm border border-purple-100 text-purple-900 font-bold">
                                    Правильный ответ: {{ $assignment->task->answer_numeric }} {{ $assignment->task->answer_units }}
                                </div>
                            @endif

                            @if($assignment->task->author_solution_text)
                                <div>{!! nl2br(e($assignment->task->author_solution_text)) !!}</div>
                            @else
                                <div class="text-purple-400 italic">Текст решения отсутствует.</div>
                            @endif

                            @if($assignment->task->solutionImages->count() > 0)
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach($assignment->task->solutionImages as $img)
                                        <a href="{{ asset($img->file_path) }}" target="_blank">
                                            <img src="{{ asset($img->file_path) }}" class="h-20 object-contain border rounded shadow-sm hover:scale-[2] origin-left transition-transform duration-200 z-10 hover:z-50 relative bg-white">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- ПРАВАЯ КОЛОНКА (ОТВЕТ УЧЕНИКА И ФОРМА) -->
                <div class="space-y-6">
                    
                    <!-- Решение ученика -->
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden border border-blue-200">
                        <div class="bg-blue-50 px-4 py-3 border-b border-blue-100 flex justify-between items-center">
                            <h3 class="font-bold text-blue-900">Ответ ученика</h3>
                            <span class="text-xs text-blue-700">Статус: {{ $assignment->status }}</span>
                        </div>
                        <div class="p-4 text-sm text-gray-800 leading-relaxed">
                            @if($assignment->answer_numeric !== null)
                                <div class="mb-3 bg-blue-100 inline-block px-3 py-1.5 rounded border border-blue-200 text-blue-900 font-bold">
                                    Ответ ученика: {{ $assignment->answer_numeric }}
                                    @if($assignment->task->answer_numeric !== null)
                                        @if((float)$assignment->answer_numeric == (float)$assignment->task->answer_numeric)
                                            <span class="text-green-600 ml-2">✓ Верно</span>
                                        @else
                                            <span class="text-red-600 ml-2">✗ Ошибка</span>
                                        @endif
                                    @endif
                                </div>
                            @endif

                            @if($assignment->solution_text)
                                <div class="bg-gray-50 p-3 border rounded">{!! nl2br(e($assignment->solution_text)) !!}</div>
                            @else
                                <div class="text-gray-400 italic">Текст решения не отправлен.</div>
                            @endif

                            <!-- Фотографии тетради ученика (С возможностью вращения!) -->
                            @if($assignment->attachments->count() > 0)
                                <h4 class="mt-4 mb-2 font-bold text-gray-700">Прикрепленные файлы:</h4>
                                <div class="flex flex-wrap gap-4">
                                    @foreach($assignment->attachments as $att)
                                        <div x-data="{ rotate: 0 }" class="relative group/img bg-gray-100 p-1 border rounded shadow-sm w-40 flex flex-col items-center">
                                            @if(Str::startsWith($att->mime_type, 'image/'))
                                                <a href="{{ asset($att->file_path) }}" target="_blank" class="w-full h-28 overflow-hidden rounded flex items-center justify-center bg-white">
                                                    <img src="{{ asset($att->file_path) }}" :style="`transform: rotate(${rotate}deg); transition: transform 0.2s;`" class="max-h-full object-contain">
                                                </a>
                                                <button @click.prevent="rotate = rotate + 90" class="absolute bottom-8 right-2 bg-gray-800/70 text-white w-6 h-6 rounded-full opacity-0 group-hover/img:opacity-100 hover:bg-gray-900 transition flex items-center justify-center text-xs" title="Повернуть">⟳</button>
                                            @else
                                                <a href="{{ asset($att->file_path) }}" target="_blank" class="w-full h-28 flex items-center justify-center text-4xl bg-white text-gray-400 hover:text-blue-500 transition">📄</a>
                                            @endif
                                            <span class="text-[10px] text-center mt-1 text-gray-500 truncate w-full">{{ $att->original_filename }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- ПАНЕЛЬ УЧИТЕЛЯ (ФОРМА ПРОВЕРКИ) -->
                    <form action="{{ route('assignments.review.update', $assignment->id) }}" method="POST" class="bg-white shadow-sm sm:rounded-lg overflow-hidden border border-gray-200 sticky top-4">
                        @csrf @method('PUT')
                        <div class="bg-gray-800 px-4 py-3 border-b flex justify-between items-center">
                            <h3 class="font-bold text-white">Вердикт учителя</h3>
                        </div>
                        <div class="p-4 space-y-4">
                            
                            <!-- Комментарий (С предпросмотром формул) -->
                            <div>
                                <label class="block font-bold text-sm text-gray-700 mb-1">Комментарий (можно использовать LaTeX)</label>
                                <textarea id="teacher_comment" name="teacher_comment" rows="4" class="w-full border-gray-300 rounded shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Напишите разбор ошибок...">{{ old('teacher_comment', $assignment->teacher_comment) }}</textarea>
                                <div class="bg-gray-50 border rounded-md p-2 mt-2 text-sm text-gray-800 min-h-[40px]" id="preview_teacher_comment"></div>
                            </div>

                            <!-- Оценка -->
                            <div>
                                <label class="block font-bold text-sm text-gray-700 mb-1">Оценка (%)</label>
                                <input type="number" name="mark_percent" min="0" max="100" value="{{ old('mark_percent', $assignment->mark_percent) }}" class="w-24 border-gray-300 rounded shadow-sm focus:ring-blue-500 text-center font-bold text-lg">
                            </div>

                            <!-- Кнопки действий -->
                            <div class="flex gap-3 pt-2">
                                <button type="submit" name="action_status" value="accepted" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded shadow transition text-center">
                                    ✅ Принять работу
                                </button>
                                <button type="submit" name="action_status" value="revision_needed" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded shadow transition text-center">
                                    🔄 На доработку
                                </button>
                            </div>

                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- Скрипт предпросмотра -->
    <script>
        function debounce(func, wait) { let timeout; return function (...args) { clearTimeout(timeout); timeout = setTimeout(() => func(...args), wait); }; }
        document.addEventListener('DOMContentLoaded', () => {
            const commentInput = document.getElementById('teacher_comment');
            const previewDiv = document.getElementById('preview_teacher_comment');
            
            function renderMath() {
                if (commentInput && previewDiv) {
                    previewDiv.innerHTML = commentInput.value.replace(/\n/g, '<br>');
                    if (typeof MathJax !== 'undefined' && MathJax.typesetPromise) {
                        MathJax.typesetPromise([previewDiv]);
                    }
                }
            }
            
            if (commentInput) {
                renderMath(); // рендер при загрузке (если уже есть текст)
                commentInput.addEventListener('input', debounce(renderMath, 500));
            }
        });
    </script>
</x-app-layout>