<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                База задач (Найдено: {{ $tasks->total() }})
            </h2>
            <a href="{{ route('tasks.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
                + Создать задачу
            </a>
        </div>
    </x-slot>

    <!-- Инициализация MathJax -->
    <script>
        MathJax = { tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] }, svg: { fontCache: 'global' } };
    </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>
    <style> mjx-container svg { display: inline; } mjx-container[jax="SVG"][display="true"] { display: block; margin: 1em 0; } </style>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Флэш-сообщения -->
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded shadow-sm">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded shadow-sm">{{ session('error') }}</div>
            @endif

            <!-- БЛОК 1: ФИЛЬТРЫ, ПОИСК И СОРТИРОВКА (Отправка на сервер) -->
            <form method="GET" action="{{ route('tasks.index') }}" class="bg-white p-6 rounded-lg shadow-sm border mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Поиск (Текст или №)</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Что ищем?..." class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Тема</label>
                        <select name="topic_id" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">-- Все темы --</option>
                            @include('topics.options', ['topics' => $topics, 'level' => 0, 'selectedId' => request('topic_id'), 'currentId' => null])
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Источник</label>
                        <select name="source_id" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">-- Все источники --</option>
                            @include('topics.options', ['topics' => $sources, 'level' => 0, 'selectedId' => request('source_id'), 'currentId' => null])
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Кто добавил</label>
                        <select name="author_id" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">-- Все --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('author_id') == $user->id ? 'selected' : '' }}>{{ $user->last_name }} {{ $user->first_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Сортировать по</label>
                        <select name="sort_by" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            <option value="id" {{ $sortField == 'id' ? 'selected' : '' }}>Номеру (Дате добавления)</option>
                            <option value="complexity" {{ $sortField == 'complexity' ? 'selected' : '' }}>Сложности</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Порядок</label>
                        <select name="sort_dir" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            <option value="desc" {{ $sortDir == 'desc' ? 'selected' : '' }}>С конца (Сначала новые/сложные)</option>
                            <option value="asc" {{ $sortDir == 'asc' ? 'selected' : '' }}>С начала</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">На странице</label>
                        <select name="per_page" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 задач</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 задач</option>
                            <option value="200" {{ $perPage == 200 ? 'selected' : '' }}>200 задач</option>
                        </select>
                    </div>

                    <div class="flex space-x-2">
                        <button type="submit" class="w-full bg-gray-800 text-white rounded-md py-2 shadow hover:bg-gray-700">Применить</button>
                        <a href="{{ route('tasks.index') }}" class="w-full text-center bg-gray-200 text-gray-700 rounded-md py-2 shadow hover:bg-gray-300">Сброс</a>
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
                
                <!-- Панель управления отображением -->
                <div class="flex flex-wrap items-center gap-6 mb-4 p-4 bg-white rounded-lg shadow-sm border text-sm text-gray-700">
                    <div class="flex items-center gap-2 border-r pr-6">
                        <span class="font-bold">Вид:</span>
                        <button @click="layout = 'grid'; updateStorage('task_layout', 'grid')" :class="layout == 'grid' ? 'text-blue-600 font-bold' : 'text-gray-500 hover:text-blue-500'">Плитка</button>
                        <span>|</span>
                        <button @click="layout = 'list'; updateStorage('task_layout', 'list')" :class="layout == 'list' ? 'text-blue-600 font-bold' : 'text-gray-500 hover:text-blue-500'">Список</button>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                        <input type="checkbox" x-model="fullText" @change="updateStorage('task_text', fullText)" class="rounded text-blue-600">
                        Показывать текст полностью
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600">
                        <input type="checkbox" x-model="showImages" @change="updateStorage('task_images', showImages)" class="rounded text-blue-600">
                        Отображать картинки
                    </label>
                </div>

                <!-- БЛОК 3: ВЫВОД ЗАДАЧ -->
                <div :class="layout === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6' : 'flex flex-col space-y-4'">
                    @forelse ($tasks as $task)
                        <div class="bg-white rounded-lg shadow border border-gray-200 flex flex-col relative hover:border-blue-300 transition-colors">
                            
                            <!-- Шапка карточки -->
                            <div class="flex justify-between items-center p-4 border-b bg-gray-50 rounded-t-lg">
                                <div class="flex items-center gap-3">
                                    <span class="font-bold text-lg text-gray-800">№{{ $task->id }}</span>
                                    <span class="text-xs font-semibold bg-gray-200 text-gray-700 px-2 py-1 rounded">Сложн: {{ $task->complexity }}</span>

                                    <!-- ИНФОРМАЦИОННЫЙ POPUP (Tooltip) -->
                                    <div class="relative group cursor-help">
                                        <span class="text-blue-500 text-lg">ⓘ</span>
                                        <div class="absolute z-20 hidden group-hover:block bg-gray-800 text-white text-xs rounded p-3 w-64 bottom-full mb-2 left-1/2 transform -translate-x-1/2 shadow-xl">
                                            <div class="mb-1"><span class="text-gray-400">Тема:</span> {{ $task->topic->name ?? '—' }}</div>
                                            <div><span class="text-gray-400">Источник:</span> {{ $task->source->name ?? '—' }}</div>
                                            <!-- Треугольничек внизу попапа -->
                                            <div class="absolute top-full left-1/2 -ml-2 border-8 border-transparent border-t-gray-800"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- КОНТЕКСТНОЕ МЕНЮ (Три точки) -->
                                <div x-data="{ open: false }" class="relative">
                                    <button @click="open = !open" @click.away="open = false" class="text-gray-500 hover:bg-gray-300 rounded-full w-8 h-8 flex items-center justify-center font-bold text-xl transition pb-2">...</button>
                                    
                                    <div x-show="open" x-cloak class="absolute right-0 mt-1 w-36 bg-white rounded shadow-xl border z-30 overflow-hidden">
                                        <a href="{{ route('tasks.edit', $task->id) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">✎ Редактировать</a>
                                        
                                        <!-- Проверка: можно ли удалять -->
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
                            <div class="p-4 flex-1">
                                <!-- Текст: класс line-clamp-3 обрезает текст до 3 строк, если fullText = false -->
                                <div class="text-sm text-gray-800" :class="fullText ? '' : 'line-clamp-3'">
                                    {!! nl2br(e($task->task_text)) !!}
                                </div>

                                <!-- Картинки: показываются только если галочка showImages включена -->
                                @if($task->taskImages->count() > 0)
                                    <div x-show="showImages" class="mt-4 flex flex-wrap gap-2 p-2 bg-gray-50 rounded border">
                                        @foreach($task->taskImages as $img)
                                            <img src="{{ asset($img->file_path) }}" style="width: {{ $img->scale }}%" class="object-contain border bg-white rounded shadow-sm max-h-48">
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <!-- Подвал карточки -->
                            <div class="px-4 py-3 border-t bg-gray-50 flex justify-between items-center text-xs rounded-b-lg">
                                <!-- Красивый бейджик использований -->
                                <span class="{{ $task->variants_count > 0 ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-200 text-gray-600' }} px-2 py-1 rounded-full font-bold">
                                    В работах: {{ $task->variants_count }}
                                </span>
                                
                                <span class="text-gray-500 italic">Добавил(а): {{ $task->author->first_name ?? '—' }}</span>
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