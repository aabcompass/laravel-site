<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Сводная матрица индивидуальной работы</h2>
    </x-slot>
    <!-- Подключаем MathJax -->
    <script> MathJax = { tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] }, svg: { fontCache: 'global' } }; </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>
    <style> mjx-container svg { display: inline; } mjx-container[jax="SVG"][display="true"] { display: block; margin: 1em 0; } </style>

    <!-- Подключаем логику Alpine.js для ячеек -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('matrixCell', (taskId, studentId, initialStatus, mark, assignmentId) => ({
                status: initialStatus,
                mark: mark,
                assignmentId: assignmentId,
                loading: false,

                async toggle() {
                    if (this.loading) return;

                    // Если работа отправлена, проверена или на доработке -> Переход на страницу проверки
                    if (['submitted', 'revision_needed', 'accepted'].includes(this.status)) {
                        window.location.href = `/assignments/review/${this.assignmentId}`;
                        return;
                    }

                    this.loading = true;

                    if (!this.status) {
                        // НАЗНАЧАЕМ ЗАДАЧУ
                        let res = await fetch('{{ route('tutors.matrix.assign') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ task_id: taskId, student_id: studentId })
                        });
                        let data = await res.json();
                        if(data.success) {
                            this.status = 'assigned';
                            this.assignmentId = data.assignment_id;
                        } else {
                            alert(data.message);
                        }
                    } else if (this.status === 'assigned') {
                        // СНИМАЕМ НАЗНАЧЕНИЕ
                        if(!confirm('Отменить назначение?')) { this.loading = false; return; }
                        let res = await fetch('{{ route('tutors.matrix.unassign') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ task_id: taskId, student_id: studentId })
                        });
                        let data = await res.json();
                        if(data.success) {
                            this.status = '';
                            this.assignmentId = null;
                        } else {
                            alert(data.message);
                        }
                    }
                    this.loading = false;
                }
            }));
        })
    </script>

    <div class="py-6">
        <div class="max-w-[1920px] mx-auto sm:px-6 lg:px-8">
            
            @if($students->isEmpty())
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6 shadow-sm">
                    <p class="text-yellow-800 font-bold">У вас пока нет прикрепленных учеников.</p>
                    <a href="{{ route('tutors.index') }}" class="text-blue-600 underline">Перейти в раздел "Мои ученики" и добавить.</a>
                </div>
            @else
                
                <!-- ФИЛЬТРЫ -->
                <form method="GET" action="{{ route('tutors.matrix') }}" class="bg-white p-4 rounded-lg shadow-sm border mb-4 flex flex-wrap gap-4 items-end text-sm">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block font-medium text-gray-700 mb-1">Поиск по тексту или №</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="w-full border-gray-300 rounded shadow-sm py-1.5">
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block font-medium text-gray-700 mb-1">Тема</label>
                        <select name="topic_id" class="w-full border-gray-300 rounded shadow-sm py-1.5">
                            <option value="">-- Все темы --</option>
                            @include('topics.options', ['topics' => $topics, 'level' => 0, 'selectedId' => request('topic_id'), 'currentId' => null])
                        </select>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block font-medium text-gray-700 mb-1">Источник</label>
                        <select name="source_id" class="w-full border-gray-300 rounded shadow-sm py-1.5">
                            <option value="">-- Все источники --</option>
                            @include('topics.options', ['topics' => $sources, 'level' => 0, 'selectedId' => request('source_id'), 'currentId' => null])
                        </select>
                    </div>
                    <div class="flex gap-2 pb-0.5">
                        <button type="submit" class="bg-gray-800 text-white rounded px-4 py-1.5 hover:bg-gray-700 shadow">Фильтровать</button>
                        <a href="{{ route('tutors.matrix') }}" class="bg-gray-200 text-gray-700 rounded px-4 py-1.5 hover:bg-gray-300 shadow">Сброс</a>
                    </div>
                </form>

                <!-- МАТРИЦА (ТАБЛИЦА С ЗАКРЕПЛЕННЫМИ ШАПКАМИ) -->
                <div class="bg-white border rounded-lg shadow-sm overflow-x-auto overflow-y-auto max-h-[75vh] relative">
                    <table class="w-full text-xs text-center border-collapse">
                        <thead class="bg-gray-100 text-gray-700 uppercase sticky top-0 z-20 shadow-sm">
                            <tr>
                                <!-- Закрепленный левый верхний угол -->
                                <th class="px-4 py-3 border-r border-b sticky left-0 bg-gray-100 z-30 min-w-[350px] text-left">
                                    Задача \ Ученик
                                </th>
                                <!-- Динамические колонки студентов -->
                                @foreach($students as $student)
                                    <th class="px-2 py-3 border-r border-b min-w-[90px] leading-tight" title="{{ $student->group->name ?? 'Без группы' }}">
                                        {{ $student->last_name }}<br>{{ $student->first_name }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tasks as $task)
                                <tr class="border-b hover:bg-blue-50 transition-colors group">
                                    
                                    <!-- Закрепленная колонка с задачей -->
                                    <td class="px-4 py-2 border-r text-left sticky left-0 bg-white group-hover:bg-blue-50 z-10 shadow-[1px_0_0_0_#e5e7eb]">
                                        <div class="font-bold text-gray-800 mb-1">
                                            <a href="{{ route('tasks.edit', $task->id) }}" target="_blank" class="hover:text-blue-600 hover:underline flex items-center gap-1">
                                                №{{ $task->id }} <span class="bg-yellow-100 text-yellow-800 text-[10px] px-1 rounded">⭐{{ $task->complexity }}</span>
                                            </a>
                                        </div>
                                        
                                        <!-- Вывод текста с LaTeX (без обрезания, либо можете добавить line-clamp-3) -->
                                        <div class="text-gray-700 text-xs leading-relaxed">
                                            {!! nl2br(e($task->task_text)) !!}
                                        </div>

                                        <!-- ВЫВОД МИНИАТЮР КАРТИНОК -->
                                        @if($task->taskImages->count() > 0)
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @foreach($task->taskImages as $img)
                                                    <!-- class="... hover:z-50" нужен, чтобы увеличенная картинка перекрывала соседние ячейки -->
                                                    <a href="{{ asset($img->file_path) }}" target="_blank" class="block relative z-10 hover:z-50">
                                                        <!-- Жестко ограничиваем высоту (h-12 = 48px), убираем style="width:..." -->
                                                        <!-- hover:scale-[3] увеличит картинку в 3 раза при наведении -->
                                                        <img src="{{ asset($img->file_path) }}" class="h-12 w-auto object-contain border bg-white rounded shadow-sm transition-transform duration-200 hover:scale-[3] origin-left">
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif 

                                    </td>

                                    <!-- Ячейки матрицы -->
                                    @foreach($students as $student)
                                        @php
                                            $assign = $matrix[$task->id][$student->id] ?? null;
                                            $status = $assign ? $assign->status : '';
                                            $mark = $assign ? $assign->mark_percent : '';
                                            $assignmentId = $assign ? $assign->id : 'null';
                                        @endphp
                                        
                                        <!-- Подключаем компонент AlpineJS -->
                                        <td class="border-r p-0 relative" x-data="matrixCell({{ $task->id }}, {{ $student->id }}, '{{ $status }}', '{{ $mark }}', {{ $assignmentId }})">
                                            
                                            <!-- Индикатор загрузки (крутилка) -->
                                            <div x-show="loading" class="absolute inset-0 bg-white/70 flex items-center justify-center z-10">
                                                <svg class="animate-spin h-5 w-5 text-blue-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            </div>

                                            <button @click="toggle()" type="button" class="w-full h-full min-h-[40px] px-1 py-2 transition-colors font-bold flex items-center justify-center cursor-pointer"
                                                :class="{
                                                    'hover:bg-gray-100': !status,
                                                    'bg-blue-100 text-blue-800 hover:bg-blue-200': status === 'assigned',
                                                    'bg-indigo-100 text-indigo-800 hover:bg-indigo-200': status === 'in_progress',
                                                    'bg-yellow-200 text-yellow-800 hover:bg-yellow-300 ring-2 ring-inset ring-yellow-400': status === 'submitted',
                                                    'bg-red-100 text-red-800 hover:bg-red-200': status === 'revision_needed',
                                                    'bg-green-100 text-green-800 hover:bg-green-200': status === 'accepted',
                                                }"
                                                :title="
                                                    !status ? 'Кликните, чтобы назначить' :
                                                    status === 'assigned' ? 'Назначено (Клик для отмены)' :
                                                    status === 'submitted' ? 'Ждет проверки! (Кликнуть)' :
                                                    status === 'revision_needed' ? 'На доработке (Кликнуть)' :
                                                    'Проверено (Кликнуть)'
                                                "
                                            >
                                                <!-- Иконки/Текст в зависимости от статуса -->
                                                <span x-show="!status" class="text-gray-200 group-hover:text-gray-300">+</span>
                                                <span x-show="status === 'assigned'">А</span>
                                                <span x-show="status === 'in_progress'">В</span>
                                                <span x-show="status === 'submitted'">Проверить!</span>
                                                <span x-show="status === 'revision_needed'">Дораб.</span>
                                                <span x-show="status === 'accepted'" x-text="mark ? mark + '%' : 'ОК'"></span>
                                            </button>
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($students) + 1 }}" class="p-8 text-center text-gray-500">Задачи по выбранным фильтрам не найдены.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- ПАГИНАЦИЯ -->
                <div class="mt-4">
                    {{ $tasks->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>