<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="{{ route('works.variants.index', $variant->work_id) }}" class="text-gray-500 hover:text-blue-600 transition">&larr; К списку вариантов</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Конструктор: {{ $variant->name }} <span class="text-gray-400 text-sm font-normal">v.{{ $variant->version }} (ID: {{ $variant->id }})</span>
                </h2>
            </div>
            
            <!-- Место под кнопку печати (пункт 4 из вашего ТЗ) -->
            <button class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-bold py-1.5 px-4 rounded shadow-sm text-sm transition">
                🖨 Распечатать вариант
            </button>
        </div>
    </x-slot>

    <!-- Инициализация MathJax -->
    <script> MathJax = { tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] }, svg: { fontCache: 'global' } }; </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>
    <style> mjx-container svg { display: inline; } mjx-container[jax="SVG"][display="true"] { display: block; margin: 1em 0; } </style>
    <!-- Библиотека для перетаскивания (Drag & Drop) -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    @php
        $isAssigned = $variant->isAssigned();
        $isAuthorOrAdmin = $variant->author_id === auth()->id() || auth()->user()->hasRole('admin');
        $isReadOnly = $isAssigned || !$isAuthorOrAdmin;
    @endphp

    <div class="py-6">
        <div class="max-w-[1920px] mx-auto sm:px-6 lg:px-8">
            
            @if (session('success')) <div class="mb-4 p-4 bg-green-100 text-green-700 rounded shadow-sm font-bold">{{ session('success') }}</div> @endif
            @if (session('error')) <div class="mb-4 p-4 bg-red-100 text-red-700 rounded shadow-sm font-bold">{{ session('error') }}</div> @endif

            <!-- ПРЕДУПРЕЖДЕНИЕ О БЛОКИРОВКЕ -->
            @if($isAssigned)
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r flex justify-between items-center shadow-sm">
                    <div>
                        <h3 class="text-red-800 font-bold text-lg">Этот вариант уже выдан ученикам!</h3>
                        <p class="text-red-700 text-sm">Редактирование (добавление, удаление, сортировка) заблокировано, чтобы не нарушить оценки учеников.</p>
                    </div>
                    <form action="{{ route('variants.clone', $variant->id) }}" method="POST">
                        @csrf <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow">Сделать копию для редактирования</button>
                    </form>
                </div>
            @elseif(!$isAuthorOrAdmin)
                <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded shadow-sm">
                    <p class="text-yellow-800 font-bold">Вы находитесь в режиме просмотра, так как не являетесь автором этого варианта.</p>
                </div>
            @endif

            <!-- ДВУХКОЛОНОЧНАЯ СТРУКТУРА -->
            <!-- calc(100vh - 200px) заставляет колонки занимать весь экран по высоте и скроллиться внутри -->
            <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-200px)]">

                <!-- ЛЕВАЯ КОЛОНКА: БАЗА ЗАДАЧ -->
                <div class="w-full lg:w-3/5 flex flex-col bg-white border rounded-lg shadow-sm overflow-hidden" x-data="{ selectedTasks: [] }">
                    
                    <!-- Шапка левой колонки (Фильтры) -->
                    <div class="bg-gray-50 border-b p-4">
                        <h3 class="font-bold text-gray-800 mb-3">База задач</h3>
                        
                        <form method="GET" action="{{ route('variants.build', $variant->id) }}" class="flex flex-wrap gap-2 text-sm">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Текст или №..." class="border-gray-300 rounded py-1 px-2 w-32 focus:ring-blue-500">
                            
                            <select name="topic_id" class="border-gray-300 rounded py-1 px-2 w-48 focus:ring-blue-500">
                                <option value="">-- Все темы --</option>
                                @include('topics.options', ['topics' => $topics, 'level' => 0, 'selectedId' => request('topic_id'), 'currentId' => null])
                            </select>

                            <select name="sort_by" class="border-gray-300 rounded py-1 px-2 focus:ring-blue-500">
                                <option value="id" {{ $sortField == 'id' ? 'selected' : '' }}>По номеру</option>
                                <option value="complexity" {{ $sortField == 'complexity' ? 'selected' : '' }}>По сложности</option>
                            </select>

                            <select name="sort_dir" class="border-gray-300 rounded py-1 px-2 focus:ring-blue-500">
                                <option value="desc" {{ request('sort_dir') == 'desc' ? 'selected' : '' }}>Убыв.</option>
                                <option value="asc" {{ request('sort_dir') == 'asc' ? 'selected' : '' }}>Возр.</option>
                            </select>

                            <button type="submit" class="bg-gray-800 text-white rounded px-3 py-1 hover:bg-gray-700">Поиск</button>
                            <a href="{{ route('variants.build', $variant->id) }}" class="bg-gray-200 text-gray-700 rounded px-3 py-1 hover:bg-gray-300">Сброс</a>
                        </form>
                    </div>

                    <!-- Панель массовых действий (Появляется при выборе чекбоксов) -->
                    @if(!$isReadOnly)
                        <div class="bg-blue-50 border-b p-2 flex justify-between items-center h-12">
                            <!-- Кнопка "Выбрать всё" на текущей странице -->
                            <label class="flex items-center gap-2 cursor-pointer text-sm font-bold text-blue-800 ml-2">
                                <input type="checkbox" @change="$event.target.checked ? selectedTasks = {{ json_encode($libraryTasks->pluck('id')) }} : selectedTasks = []" class="rounded text-blue-600 focus:ring-blue-500 w-5 h-5">
                                Выбрать всё на странице
                            </label>

                            <!-- Плавающая кнопка добавления -->
                            <form x-show="selectedTasks.length > 0" x-cloak action="{{ route('variants.attach', $variant->id) }}" method="POST" class="m-0">
                                @csrf
                                <!-- Генерируем скрытые инпуты для каждого выбранного ID -->
                                <template x-for="taskId in selectedTasks" :key="taskId">
                                    <input type="hidden" name="task_ids[]" :value="taskId">
                                </template>
                                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-4 rounded-full shadow transition transform hover:scale-105">
                                    Добавить выбранные (<span x-text="selectedTasks.length"></span>) &rarr;
                                </button>
                            </form>
                        </div>
                    @endif

                    <!-- Список задач из базы (Скроллится) -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-100">
                        @forelse ($libraryTasks as $task)
                            <div class="bg-white p-3 rounded border shadow-sm flex gap-3 hover:border-blue-300 transition">
                                <!-- Чекбокс -->
                                @if(!$isReadOnly)
                                    <div class="pt-1">
                                        <input type="checkbox" x-model="selectedTasks" value="{{ $task->id }}" class="rounded text-blue-600 w-5 h-5 cursor-pointer">
                                    </div>
                                @endif
                                
                                <div class="flex-1 text-sm">
                                    <div class="mb-1 flex items-center gap-2">
                                        <span class="font-bold text-gray-800">№{{ $task->id }}</span>
                                        <span class="text-xs font-bold bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded">⭐ {{ $task->complexity }}</span>
                                        <span class="text-xs text-gray-500">{{ $task->topic->name ?? '' }}</span>
                                    </div>
                                    <div class="text-gray-700 line-clamp-3 leading-relaxed">
                                        {!! nl2br(e($task->task_text)) !!}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-gray-500 py-8">Задачи не найдены.</div>
                        @endforelse
                    </div>

                    <!-- Пагинация -->
                    <div class="border-t p-2 bg-white">
                        {{ $libraryTasks->links() }}
                    </div>
                </div>

                <!-- ПРАВАЯ КОЛОНКА: ЗАДАЧИ ВАРИАНТА -->
                <div class="w-full lg:w-2/5 flex flex-col bg-white border rounded-lg shadow-sm overflow-hidden">
                    
                    <div class="bg-indigo-50 border-b p-4 flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-indigo-900">Задачи в варианте</h3>
                            <div class="text-xs text-indigo-700 mt-1">Всего задач: {{ $variantTasks->count() }}</div>
                        </div>
                        
                        <!-- Форма сохранения порядка (показывается только после Drag&Drop) -->
                        @if(!$isReadOnly)
                            <form id="reorder-form" action="{{ route('variants.reorder', $variant->id) }}" method="POST" class="hidden m-0">
                                @csrf @method('PUT')
                                <div id="reorder-inputs"></div>
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-1.5 px-3 rounded shadow animate-pulse">
                                    Сохранить порядок
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- Список добавленных задач (Скроллится) -->
                    <!-- id="variant-task-list" нужен для Sortable.js -->
                    <div id="variant-task-list" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
                        @forelse ($variantTasks as $task)
                            <div class="variant-task-item bg-white p-3 rounded border shadow-sm flex gap-3 relative group" data-id="{{ $task->id }}">
                                
                                <!-- Иконка перетаскивания -->
                                @if(!$isReadOnly)
                                    <div class="drag-handle cursor-grab pt-1 text-gray-400 hover:text-indigo-500 active:cursor-grabbing">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                                    </div>
                                @endif

                                <div class="flex-1 text-sm">
                                    <div class="mb-1 flex items-center gap-2">
                                        <span class="font-bold text-gray-800">№{{ $task->id }}</span>
                                        <span class="text-xs font-bold bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded">⭐ {{ $task->complexity }}</span>
                                    </div>
                                    <div class="text-gray-700 line-clamp-2 leading-relaxed">
                                        {!! nl2br(e($task->task_text)) !!}
                                    </div>
                                </div>

                                <!-- Кнопка удаления из варианта -->
                                @if(!$isReadOnly)
                                    <form action="{{ route('variants.detach', [$variant->id, $task->id]) }}" method="POST" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="bg-red-100 text-red-600 hover:bg-red-500 hover:text-white rounded w-6 h-6 flex items-center justify-center font-bold" title="Убрать из варианта">&times;</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <div class="text-center text-gray-400 py-8 italic border-2 border-dashed border-gray-200 rounded p-4">
                                Вариант пока пуст.<br>Выберите задачи в левой колонке.
                            </div>
                        @endforelse
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- Скрипт инициализации Drag & Drop -->
    @if(!$isReadOnly && $variantTasks->count() > 0)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('variant-task-list');
                var sortable = Sortable.create(el, {
                    handle: '.drag-handle', // Перетаскивать можно только за иконку
                    animation: 150,
                    ghostClass: 'bg-indigo-100', // Цвет плашки во время перетаскивания
                    onEnd: function () {
                        // Как только мы бросили элемент - показываем кнопку "Сохранить порядок"
                        const form = document.getElementById('reorder-form');
                        const inputsContainer = document.getElementById('reorder-inputs');
                        
                        // Очищаем старые инпуты
                        inputsContainer.innerHTML = '';
                        
                        // Собираем новый порядок ID
                        const items = el.querySelectorAll('.variant-task-item');
                        items.forEach(item => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'task_order[]';
                            input.value = item.dataset.id;
                            inputsContainer.appendChild(input);
                        });

                        form.classList.remove('hidden');
                    },
                });
            });
        </script>
    @endif
</x-app-layout>