<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                База задач (Найдено: {{ $tasks->total() }})
            </h2>
            <a href="{{ route('tasks.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded shadow">
                + Создать задачу
            </a>
        </div>
    </x-slot>

    <!-- Инициализация MathJax -->
    <script>
        MathJax = { tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] }, svg: { fontCache: 'global' } };
    </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>
    
    <style> 
        mjx-container svg { display: inline; } 
        mjx-container[jax="SVG"][display="true"] { display: block; margin: 1em 0; }
        /* Прячем элементы до загрузки Alpine.js, чтобы они не моргали */
        [x-cloak] { display: none !important; }
    </style>

    <div class="py-6">
        <div class="max-w-[1920px] mx-auto sm:px-6 lg:px-8"> <!-- Расширили контейнер для Full-HD -->

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded shadow-sm">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded shadow-sm">{{ session('error') }}</div>
            @endif

            <!-- БЛОК 1: ФИЛЬТРЫ В ОДНУ СТРОКУ (Flexbox) -->
            <form method="GET" action="{{ route('tasks.index') }}" class="bg-white p-4 rounded-lg shadow-sm border mb-4">
                <div class="flex flex-wrap items-end gap-3 text-sm">
                    
                    <div class="flex-1 min-w-[150px]">
                        <label class="block font-medium text-gray-700 mb-1">Поиск</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Текст или №..." class="w-full border-gray-300 rounded text-sm py-1.5">
                    </div>

                    <div class="flex-1 min-w-[150px]">
                        <label class="block font-medium text-gray-700 mb-1">Тема</label>
                        <select name="topic_id" class="w-full border-gray-300 rounded text-sm py-1.5">
                            <option value="">-- Все темы --</option>
                            @include('topics.options', ['topics' => $topics, 'level' => 0, 'selectedId' => request('topic_id'), 'currentId' => null])
                        </select>
                    </div>

                    <div class="flex-1 min-w-[150px]">
                        <label class="block font-medium text-gray-700 mb-1">Источник</label>
                        <select name="source_id" class="w-full border-gray-300 rounded text-sm py-1.5">
                            <option value="">-- Все источники --</option>
                            @include('topics.options', ['topics' => $sources, 'level' => 0, 'selectedId' => request('source_id'), 'currentId' => null])
                        </select>
                    </div>

                    <div class="flex-1 min-w-[120px]">
                        <label class="block font-medium text-gray-700 mb-1">Добавил</label>
                        <select name="author_id" class="w-full border-gray-300 rounded text-sm py-1.5">
                            <option value="">-- Все --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('author_id') == $user->id ? 'selected' : '' }}>{{ $user->last_name }} {{ Str::substr($user->first_name, 0, 1) }}.</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex-1 min-w-[140px]">
                        <label class="block font-medium text-gray-700 mb-1">Сортировка</label>
                        <select name="sort_by" class="w-full border-gray-300 rounded text-sm py-1.5">
                            <option value="id" {{ $sortField == 'id' ? 'selected' : '' }}>По номеру</option>
                            <option value="complexity" {{ $sortField == 'complexity' ? 'selected' : '' }}>По сложности</option>
                        </select>
                    </div>

                    <div class="flex-1 min-w-[120px]">
                        <label class="block font-medium text-gray-700 mb-1">Порядок</label>
                        <select name="sort_dir" class="w-full border-gray-300 rounded text-sm py-1.5">
                            <option value="desc" {{ $sortDir == 'desc' ? 'selected' : '' }}>С конца (Убыв.)</option>
                            <option value="asc" {{ $sortDir == 'asc' ? 'selected' : '' }}>С начала (Возр.)</option>
                        </select>
                    </div>

                    <div class="w-20">
                        <label class="block font-medium text-gray-700 mb-1">Кол-во</label>
                        <select name="per_page" class="w-full border-gray-300 rounded text-sm py-1.5">
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                            <option value="200" {{ $perPage == 200 ? 'selected' : '' }}>200</option>
                        </select>
                    </div>

                    <div class="flex gap-2 pb-0.5">
                        <button type="submit" class="bg-gray-800 text-white rounded px-4 py-1.5 text-sm hover:bg-gray-700 transition">Поиск</button>
                        <a href="{{ route('tasks.index') }}" class="bg-gray-200 text-gray-700 rounded px-4 py-1.5 text-sm hover:bg-gray-300 transition">Сброс</a>
                    </div>
                </div>
            </form>

            <!-- БЛОК 2: АКТИВНЫЕ ПЕРЕКЛЮЧАТЕЛИ ВИДА (Alpine.js) -->
            <div x-data="{
                    layout: localStorage.getItem('task_layout') || 'grid',
                    showImages: localStorage.getItem('task_images') !== 'false',
                    fullText: localStorage.getItem('task_text') === 'true',
                    updateStorage(key, value) { localStorage.setItem(key, value); }
                }">
                
                <div class="flex flex-wrap items-center gap-6 mb-4 p-3 bg-white rounded-lg shadow-sm border text-sm text-gray-700">
                    <div class="flex items-center gap-2 border-r pr-6">
                        <span class="font-bold">Вид:</span>
                        <button @click="layout = 'grid'; updateStorage('task_layout', 'grid')" :class="layout == 'grid' ? 'text-blue-600 font-bold' : 'text-gray-500 hover:text-blue-500'">Плитка</button>
                        <span>|</span>
                        <button @click="layout = 'list'; updateStorage('task_layout', 'list')" :class="layout == 'list' ? 'text-blue-600 font-bold' : 'text-gray-500 hover:text-blue-500'">Список</button>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                        <input type="checkbox" x-model="fullText" @change="updateStorage('task_text', fullText)" class="rounded text-blue-600">
                        Развернуть текст
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                        <input type="checkbox" x-model="showImages" @change="updateStorage('task_images', showImages)" class="rounded text-blue-600">
                        Показывать картинки
                    </label>
                </div>

                <!-- БЛОК 3: ВЫВОД ЗАДАЧ -->
                <!-- 5 колонок для 2xl (Full HD), 4 для xl, 3 для lg, 2 для md -->
                <div :class="layout === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4' : 'flex flex-col space-y-3'">
                    @forelse ($tasks as $task)
                        <div class="bg-white rounded-lg shadow border border-gray-200 flex flex-col relative hover:border-blue-400 transition-colors group/card">
                            
                            <!-- Шапка карточки (Супер компактная) -->
                            <div class="flex justify-between items-center p-2.5 border-b bg-gray-50 rounded-t-lg">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-gray-800 text-sm">№{{ $task->id }}</span>
                                    
                                    <span class="text-xs font-bold bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded" title="Сложность">
                                        ⭐ {{ $task->complexity }}
                                    </span>
                                    
                                    <span class="text-xs font-bold px-1.5 py-0.5 rounded {{ $task->variants_count > 0 ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-200 text-gray-500' }}" title="Использований в работах">
                                        В работах: {{ $task->variants_count }}
                                    </span>

                                    <!-- ИНФОРМАЦИОННЫЙ POPUP (На Alpine.js) -->
                                    <div x-data="{ tooltip: false }" class="relative flex items-center">
                                        <button @mouseenter="tooltip = true" @mouseleave="tooltip = false" type="button" class="text-blue-500 hover:bg-blue-100 rounded-full w-5 h-5 flex items-center justify-center cursor-help transition text-xs border border-blue-200">
                                            i
                                        </button>

                                        <!-- Само окно попапа -->
                                        <div x-show="tooltip" x-cloak class="absolute z-50 bg-gray-800 text-white text-xs rounded p-3 w-56 top-full mt-2 left-0 shadow-xl pointer-events-none">
                                            <div class="mb-1"><span class="text-gray-400">Тема:</span> {{ $task->topic->name ?? '—' }}</div>
                                            <div class="mb-1"><span class="text-gray-400">Источник:</span> {{ $task->source->name ?? '—' }}</div>
                                            <div><span class="text-gray-400">Добавил:</span> {{ $task->author->first_name ?? '—' }} {{ $task->author->last_name ?? '' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- КОНТЕКСТНОЕ МЕНЮ (Три точки) -->
                                <div x-data="{ open: false }" class="relative">
                                    <button @click="open = !open" @click.away="open = false" class="text-gray-500 hover:bg-gray-300 rounded w-6 h-6 flex items-center justify-center font-bold transition pb-1">...</button>
                                    
                                    <div x-show="open" x-cloak class="absolute right-0 mt-1 w-36 bg-white rounded shadow-xl border z-30 overflow-hidden">
                                        <a href="{{ route('tasks.edit', $task->id) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">✎ Изменить</a>
                                        
                                        @if($task->variants_count > 0)
                                            <div class="px-4 py-2 text-sm text-gray-400 cursor-not-allowed bg-gray-50" title="Нельзя удалить: используется в работах">🗑 Удалить</div>
                                        @else
                                            <form action="{{ route('tasks.destroy', $task->id) }}" method="POST" onsubmit="return confirm('Точно удалить задачу №{{ $task->id }}? Это действие необратимо.')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">🗑 Удалить</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Тело карточки (Текст и картинки) -->
                            <div class="p-3 flex-1 text-sm">
                                <div class="text-gray-800" :class="fullText ? '' : 'line-clamp-4'">
                                    {!! nl2br(e($task->task_text)) !!}
                                </div>

                                @if($task->taskImages->count() > 0)
                                    <!-- Важно: x-show="showImages" управляет видимостью -->
                                    <div x-show="showImages" x-cloak class="mt-3 flex flex-wrap gap-2 p-2 bg-gray-50 rounded border">
                                        @foreach($task->taskImages as $img)
                                            <img src="{{ asset($img->file_path) }}" style="width: {{ $img->scale }}%" class="object-contain border bg-white rounded shadow-sm max-h-40">
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                        </div>
                    @empty
                        <div class="col-span-full p-8 text-center bg-white rounded-lg border text-gray-500">
                            Задачи по вашим критериям не найдены. Попробуйте изменить фильтры.
                        </div>
                    @endforelse
                </div>

                <!-- БЛОК 4: ПАГИНАЦИЯ -->
                <div class="mt-6">
                    {{ $tasks->links() }}
                </div>

            </div> <!-- Конец x-data -->

        </div>
    </div>
</x-app-layout>