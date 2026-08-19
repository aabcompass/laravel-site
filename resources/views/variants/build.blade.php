<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="{{ route('works.variants.index', $variant->work_id) }}" class="text-gray-500 hover:text-blue-600 transition">&larr; К списку вариантов</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Конструктор: {{ $variant->name }} <span class="text-gray-400 text-sm font-normal">v.{{ $variant->version }} (ID: {{ $variant->id }})</span>
                </h2>
            </div>
            <button class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-bold py-1.5 px-4 rounded shadow-sm text-sm transition">
                🖨 Распечатать вариант
            </button>
        </div>
    </x-slot>

    <!-- Инициализация MathJax -->
    <script> MathJax = { tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] }, svg: { fontCache: 'global' } }; </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>
    <style> mjx-container svg { display: inline; } mjx-container[jax="SVG"][display="true"] { display: block; margin: 1em 0; } [x-cloak] { display: none !important; } </style>
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

            @if($isAssigned)
                <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-r flex justify-between items-center shadow-sm">
                    <div>
                        <h3 class="text-red-800 font-bold text-lg">Этот вариант уже выдан ученикам!</h3>
                        <p class="text-red-700 text-sm">Редактирование заблокировано.</p>
                    </div>
                    <form action="{{ route('variants.clone', $variant->id) }}" method="POST">
                        @csrf <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow">Сделать копию для редактирования</button>
                    </form>
                </div>
            @elseif(!$isAuthorOrAdmin)
                <div class="mb-4 bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded shadow-sm">
                    <p class="text-yellow-800 font-bold">Режим просмотра: вы не автор варианта.</p>
                </div>
            @endif

            <!-- ИЗМЕНЕНИЕ 1: md:flex-row заставит колонки стоять рядом почти всегда -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 h-[calc(100vh-180px)]">

                <!-- ЛЕВАЯ КОЛОНКА: БАЗА ЗАДАЧ -->
                <div class="lg:col-span-7 flex flex-col bg-white border rounded-lg shadow-sm overflow-hidden" x-data="{ selectedTasks: [] }">
                    
                    <div class="bg-gray-50 border-b p-4">
                        <h3 class="font-bold text-gray-800 mb-3">База задач</h3>
                        
                        <!-- ИЗМЕНЕНИЕ 2: Добавлены новые фильтры -->
                        <form method="GET" action="{{ route('variants.build', $variant->id) }}" class="flex flex-wrap gap-2 text-sm items-center">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Текст или №..." class="border-gray-300 rounded py-1 px-2 w-32 focus:ring-blue-500">
                            
                            <select name="topic_id" class="border-gray-300 rounded py-1 px-2 w-36 focus:ring-blue-500">
                                <option value="">-- Тема --</option>
                                @include('topics.options', ['topics' => $topics, 'level' => 0, 'selectedId' => request('topic_id'), 'currentId' => null])
                            </select>

                            <select name="source_id" class="border-gray-300 rounded py-1 px-2 w-36 focus:ring-blue-500">
                                <option value="">-- Источник --</option>
                                @include('topics.options', ['topics' => $sources, 'level' => 0, 'selectedId' => request('source_id'), 'currentId' => null])
                            </select>

                            <select name="author_id" class="border-gray-300 rounded py-1 px-2 w-36 focus:ring-blue-500">
                                <option value="">-- Добавил --</option>
                                @foreach($authors as $author)
                                    <option value="{{ $author->id }}" {{ request('author_id') == $author->id ? 'selected' : '' }}>
                                        {{ $author->last_name }} {{ Str::substr($author->first_name, 0, 1) }}.
                                    </option>
                                @endforeach
                            </select>

                            <select name="sort_by" class="border-gray-300 rounded py-1 px-2 w-28 focus:ring-blue-500">
                                <option value="id" {{ $sortField == 'id' ? 'selected' : '' }}>По номеру</option>
                                <option value="complexity" {{ $sortField == 'complexity' ? 'selected' : '' }}>По сложн.</option>
                            </select>

                            <select name="sort_dir" class="border-gray-300 rounded py-1 px-2 focus:ring-blue-500">
                                <option value="desc" {{ request('sort_dir') == 'desc' ? 'selected' : '' }}>Убыв.</option>
                                <option value="asc" {{ request('sort_dir') == 'asc' ? 'selected' : '' }}>Возр.</option>
                            </select>

                            <button type="submit" class="bg-gray-800 text-white rounded px-3 py-1 hover:bg-gray-700">Поиск</button>
                            <a href="{{ route('variants.build', $variant->id) }}" class="bg-gray-200 text-gray-700 rounded px-3 py-1 hover:bg-gray-300">Сброс</a>
                        </form>
                    </div>

                    @if(!$isReadOnly)
                        <div class="bg-blue-50 border-b p-2 flex justify-between items-center h-12">
                            <label class="flex items-center gap-2 cursor-pointer text-sm font-bold text-blue-800 ml-2">
                                <input type="checkbox" @change="$event.target.checked ? selectedTasks = {{ json_encode($libraryTasks->pluck('id')) }} : selectedTasks = []" class="rounded text-blue-600 focus:ring-blue-500 w-5 h-5">
                                Выбрать всё на странице
                            </label>

                            <form x-show="selectedTasks.length > 0" x-cloak action="{{ route('variants.attach', $variant->id) }}" method="POST" class="m-0">
                                @csrf
                                <template x-for="taskId in selectedTasks" :key="taskId">
                                    <input type="hidden" name="task_ids[]" :value="taskId">
                                </template>
                                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-4 rounded-full shadow transition transform hover:scale-105">
                                    Добавить выбранные (<span x-text="selectedTasks.length"></span>) &rarr;
                                </button>
                            </form>
                        </div>
                    @endif

                    <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-100">
                        @forelse ($libraryTasks as $task)
                            <div class="bg-white p-3 rounded border shadow-sm flex gap-3 hover:border-blue-300 transition">
                                @if(!$isReadOnly)
                                    <div class="pt-1">
                                        <input type="checkbox" x-model="selectedTasks" value="{{ $task->id }}" class="rounded text-blue-600 w-5 h-5 cursor-pointer">
                                    </div>
                                @endif
                                
                                <div class="flex-1 text-sm">
                                    <div class="mb-1 flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-gray-800">№{{ $task->id }}</span>
                                        <span class="text-xs font-bold bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded">⭐ {{ $task->complexity }}</span>
                                        <span class="text-xs text-gray-500">📂 {{ $task->topic->name ?? '' }}</span>
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
                    <div class="border-t p-2 bg-white">{{ $libraryTasks->links() }}</div>
                </div>

                <!-- ПРАВАЯ КОЛОНКА: ЗАДАЧИ ВАРИАНТА -->
                <div class="lg:col-span-5 flex flex-col bg-white border rounded-lg shadow-sm overflow-hidden" x-data="{ taskCount: {{ $variantTasks->count() }} }">
                    
                    <div class="bg-indigo-50 border-b p-4 flex justify-between items-center flex-wrap gap-2">
                        <div>
                            <h3 class="font-bold text-indigo-900">Задачи в варианте</h3>
                            <div class="text-xs text-indigo-700 mt-1">Всего задач: <span x-text="taskCount"></span></div>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <!-- Кнопка сортировки по сложности -->
                            @if(!$isReadOnly && $variantTasks->count() > 1)
                                <form action="{{ route('variants.sortComplexity', $variant->id) }}" method="POST" class="m-0" onsubmit="return confirm('Отсортировать задачи от легких к сложным? Текущий порядок будет изменен.')">
                                    @csrf @method('PUT')
                                    <button type="submit" class="bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-100 text-xs font-bold py-1.5 px-3 rounded shadow-sm transition">
                                        Сорт. по сложности
                                    </button>
                                </form>
                            @endif

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
                    </div>

                    <div id="variant-task-list" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
                        @forelse ($variantTasks as $task)
                            <div class="variant-task-item bg-white p-3 rounded border shadow-sm flex gap-3 relative group" data-id="{{ $task->id }}">
                                
                                <!-- ИЗМЕНЕНИЕ 3: Иконка из 6 точек (Grip vertical) -->
                                @if(!$isReadOnly)
                                    <div class="drag-handle cursor-grab pt-1 text-gray-300 hover:text-indigo-500 active:cursor-grabbing">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 5h2v2H9V5zm0 6h2v2H9v-2zm0 6h2v2H9v-2zm4-12h2v2h-2V5zm0 6h2v2h-2v-2zm0 6h2v2h-2v-2z"></path>
                                        </svg>
                                    </div>
                                @endif

                                <div class="flex-1 text-sm pr-6">
                                    <div class="mb-1 flex items-center gap-2">
                                        <!-- ИЗМЕНЕНИЕ 4: Порядковый номер в варианте (1, 2, 3...) -->
                                        <span class="font-black text-indigo-600 text-base border-r-2 border-indigo-200 pr-2 mr-1">{{ $loop->iteration }}.</span>
                                        <span class="font-bold text-gray-800 text-xs">№{{ $task->id }}</span>
                                        <span class="text-[10px] font-bold bg-yellow-100 text-yellow-800 px-1 py-0.5 rounded">⭐{{ $task->complexity }}</span>
                                    </div>
                                    <div class="text-gray-700 line-clamp-3 leading-relaxed mt-1">
                                        {!! nl2br(e($task->task_text)) !!}
                                    </div>
                                    
                                    <!-- ВЫВОД КАРТИНОК В ПРАВОЙ КОЛОНКЕ -->
                                    @if($task->taskImages && $task->taskImages->count() > 0)
                                        <div class="mt-3 flex flex-wrap gap-2 p-2 bg-gray-50 rounded border">
                                            @foreach($task->taskImages as $img)
                                                <img src="{{ asset($img->file_path) }}" style="width: {{ $img->scale }}%" class="object-contain border bg-white rounded shadow-sm max-h-32">
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <!-- ИЗМЕНЕНИЕ 5: Умное AJAX-удаление без перезагрузки -->
                                @if(!$isReadOnly)
                                    <button type="button" 
                                        @click="if(confirm('Убрать из варианта?')) {
                                            fetch('{{ route('variants.detach', [$variant->id, $task->id]) }}', {
                                                method: 'DELETE',
                                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                            }).then(() => { 
                                                $el.closest('.variant-task-item').remove(); 
                                                taskCount--; 
                                            });
                                        }" 
                                        class="absolute top-2 right-2 text-gray-300 hover:bg-red-100 hover:text-red-600 rounded w-6 h-6 flex items-center justify-center font-bold transition-colors" title="Убрать из варианта">
                                        &times;
                                    </button>
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

    @if(!$isReadOnly && $variantTasks->count() > 0)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('variant-task-list');
                var sortable = Sortable.create(el, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'bg-indigo-50',
                    onEnd: function () {
                        const form = document.getElementById('reorder-form');
                        const inputsContainer = document.getElementById('reorder-inputs');
                        inputsContainer.innerHTML = '';
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