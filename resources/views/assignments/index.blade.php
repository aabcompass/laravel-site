<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Здравствуйте, {{ auth()->user()->first_name }}!
        </h2>
    </x-slot>

    <!-- Инициализация MathJax -->
    <script> MathJax = { tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] }, svg: { fontCache: 'global' } }; </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style> mjx-container svg { display: inline; } mjx-container[jax="SVG"][display="true"] { display: block; margin: 1em 0; } </style>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

    <!-- БЛОК 1: КЛИКАБЕЛЬНЫЕ ПИЛЮЛИ СТАТУСОВ -->
            <div class="bg-white p-4 rounded-lg shadow-sm border flex flex-wrap items-center gap-4">
                <span class="text-sm font-bold text-gray-700">Всего заданий: {{ $totalCount }}</span>
                
                @php
                    $currentStatus = request('status');
                    
                    // ЯВНО прописываем все классы, чтобы Tailwind их увидел при сборке
                    $statusConfig = [
                        'assigned' => ['label' => 'Назначено', 'bg' => 'bg-blue-500', 'hover' => 'hover:bg-blue-600', 'ring' => 'ring-blue-500'],
                        'in_progress' => ['label' => 'В работе', 'bg' => 'bg-indigo-500', 'hover' => 'hover:bg-indigo-600', 'ring' => 'ring-indigo-500'],
                        'submitted' => ['label' => 'На проверке', 'bg' => 'bg-yellow-500', 'hover' => 'hover:bg-yellow-600', 'ring' => 'ring-yellow-500'],
                        'revision_needed' => ['label' => 'На доработку', 'bg' => 'bg-red-500', 'hover' => 'hover:bg-red-600', 'ring' => 'ring-red-500'],
                        'accepted' => ['label' => 'Принято', 'bg' => 'bg-green-500', 'hover' => 'hover:bg-green-600', 'ring' => 'ring-green-500'],
                    ];
                @endphp

                @if($currentStatus)
                    <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="text-sm text-gray-500 hover:text-gray-800 underline">Все статусы</a>
                @endif

                @foreach($statusConfig as $key => $config)
                    @if(isset($statusCounts[$key]) && $statusCounts[$key] > 0)
                        <a href="{{ request()->fullUrlWithQuery(['status' => $currentStatus === $key ? null : $key]) }}" 
                           class="px-4 py-1.5 rounded-full text-sm font-bold text-white transition-all shadow-sm
                                  {{ $config['bg'] }} {{ $config['hover'] }}
                                  {{ $currentStatus === $key ? 'ring-4 ring-offset-2 ' . $config['ring'] : 'opacity-80 hover:opacity-100' }}">
                            {{ $config['label'] }}: {{ $statusCounts[$key] }}
                        </a>
                    @endif
                @endforeach
            </div>

            <!-- НОВЫЙ БЛОК: ВАРИАНТЫ ДЛЯ ГРУППЫ (Табличный вид) -->
            @if(isset($groupVariants) && $groupVariants->count() > 0)
                <div class="bg-white border border-indigo-200 rounded-lg shadow-sm overflow-hidden mb-6">
                    <div class="bg-indigo-50 px-5 py-3 border-b border-indigo-100 flex justify-between items-center">
                        <h3 class="font-bold text-indigo-900 flex items-center gap-2">
                            <span>📚 Работы для вашей группы</span>
                            <span class="bg-indigo-200 text-indigo-800 text-[10px] uppercase px-2 py-0.5 rounded-full tracking-wide">
                                {{ auth()->user()->group->name ?? '' }}
                            </span>
                        </h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-700">
                            <thead class="text-xs uppercase bg-gray-50 border-b text-gray-500">
                                <tr>
                                    <!-- Кликабельный заголовок: Дата -->
                                    <th class="px-5 py-3 w-32 hover:bg-gray-100 transition cursor-pointer" 
                                        onclick="window.location='{{ request()->fullUrlWithQuery(['v_sort' => $vSort == 'date_desc' ? 'date_asc' : 'date_desc']) }}'">
                                        <div class="flex items-center gap-1">
                                            Дата выдачи
                                            @if($vSort == 'date_desc') <span class="text-indigo-500">▼</span> 
                                            @elseif($vSort == 'date_asc') <span class="text-indigo-500">▲</span> @endif
                                        </div>
                                    </th>

                                    <!-- Кликабельный заголовок: Работа -->
                                    <th class="px-5 py-3 hover:bg-gray-100 transition cursor-pointer"
                                        onclick="window.location='{{ request()->fullUrlWithQuery(['v_sort' => $vSort == 'work_asc' ? 'work_desc' : 'work_asc']) }}'">
                                        <div class="flex items-center gap-1">
                                            Название работы (Тема)
                                            @if($vSort == 'work_asc') <span class="text-indigo-500">▲</span> 
                                            @elseif($vSort == 'work_desc') <span class="text-indigo-500">▼</span> @endif
                                        </div>
                                    </th>

                                    <!-- Кликабельный заголовок: Вариант -->
                                    <th class="px-5 py-3 hover:bg-gray-100 transition cursor-pointer"
                                        onclick="window.location='{{ request()->fullUrlWithQuery(['v_sort' => $vSort == 'variant_asc' ? 'variant_desc' : 'variant_asc']) }}'">
                                        <div class="flex items-center gap-1">
                                            Вариант
                                            @if($vSort == 'variant_asc') <span class="text-indigo-500">▲</span> 
                                            @elseif($vSort == 'variant_desc') <span class="text-indigo-500">▼</span> @endif
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupVariants as $history)
                                    <!-- Вся строка кликабельна -->
                                    <tr onclick="window.location='{{ route('student.variants.show', $history->variant->id) }}'" 
                                        class="border-b hover:bg-indigo-50 cursor-pointer transition-colors group">
                                        
                                        <td class="px-5 py-2.5 whitespace-nowrap text-gray-500 group-hover:text-indigo-600 transition">
                                            {{ $history->assigned_at->format('d.m.Y') }}
                                        </td>
                                        
                                        <td class="px-5 py-2.5 font-medium text-gray-800 group-hover:text-indigo-800 transition">
                                            {{ $history->variant->work->title ?? 'Без названия' }}
                                        </td>
                                        
                                        <td class="px-5 py-2.5 text-gray-600 group-hover:text-indigo-600 transition flex items-center justify-between">
                                            <span>{{ $history->variant->name }}</span>
                                            <!-- Маленькая стрелочка при наведении, подсказывающая, что можно кликнуть -->
                                            <span class="opacity-0 group-hover:opacity-100 text-indigo-400 transition">&rarr;</span>
                                        </td>
                                        
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($groupVariants, 'links') && $groupVariants->hasPages())
                        <div class="p-3 border-t bg-gray-50">
                            {{ $groupVariants->links() }}
                        </div>
                    @endif
                </div>
            @endif
            
            <!-- ПОЛКА НАГРАД УЧЕНИКА -->
            @if(isset($myRewards) && $myRewards->count() > 0)
                <div class="bg-gradient-to-br from-indigo-900 to-purple-900 border border-indigo-800 rounded-lg p-5 shadow-lg relative overflow-hidden">
                    <!-- Декоративные лучи -->
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white opacity-5 rounded-full blur-2xl"></div>
                    
                    <h3 class="font-black text-xl text-yellow-400 mb-4 flex items-center gap-2 drop-shadow-md">
                        <span>🏆 Мои трофеи</span>
                        <span class="bg-white/20 text-white text-xs px-2 py-0.5 rounded-full backdrop-blur-sm">{{ $myRewards->count() }} шт.</span>
                    </h3>
                    
                    <div class="flex overflow-x-auto gap-4 pb-2 no-scrollbar">
                        @foreach($myRewards as $sr)
                            <div class="flex-shrink-0 flex flex-col items-center justify-center w-24 h-24 bg-white/10 border border-white/20 rounded-2xl shadow backdrop-blur-sm hover:bg-white/20 transition-all cursor-help group/trophy relative" title="Выдано: {{ $sr->created_at->format('d.m.Y') }} за {{ $sr->reason ?? 'успехи' }}">
                                
                                <div class="text-2xl text-white font-black drop-shadow-lg flex items-center justify-center h-12 w-full">
                                    @if($sr->reward->symbol_latex)
                                        <span>${!! $sr->reward->symbol_latex !!}$</span>
                                    @elseif($sr->reward->svg_content)
                                        <div class="h-10 w-10 [&>svg]:w-full [&>svg]:h-full">{!! $sr->reward->svg_content !!}</div>
                                    @else
                                        {{ $sr->reward->key }}
                                    @endif
                                </div>
                                <span class="text-[10px] font-bold text-indigo-100 mt-1 leading-tight text-center px-1">{{ $sr->reward->name }}</span>

                                <!-- Всплывающая инфа -->
                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-48 bg-white text-gray-900 text-xs rounded p-2 shadow-xl opacity-0 group-hover/trophy:opacity-100 pointer-events-none transition-opacity z-50">
                                    <div class="font-bold text-indigo-600 border-b pb-1 mb-1">{{ $sr->reward->name }}</div>
                                    <div class="text-gray-500 mb-1">За: {{ $sr->reason ?? '—' }}</div>
                                    @if($sr->reward->perks)<div class="font-bold text-green-600 bg-green-50 p-1 rounded">{{ $sr->reward->perks }}</div>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- БЛОК 2: ГРАФИК УСПЕВАЕМОСТИ -->
            @if(!empty($complexityStats['data']))
                <div class="bg-white p-4 rounded-lg shadow-sm border">
                    <div style="height: 200px;"><canvas id="complexityChart"></canvas></div>
                </div>
            @endif

            <!-- БЛОК 3: ФИЛЬТРЫ И СОРТИРОВКА -->
            <form method="GET" action="{{ route('dashboard') }}" class="bg-gray-50 p-4 rounded-lg border flex flex-wrap gap-4 items-end">
                <!-- Сохраняем текущий статус при сортировке -->
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif

                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Выбрать тему</label>
                    <select name="topic_id" class="w-full border-gray-300 rounded shadow-sm text-sm" onchange="this.form.submit()">
                        <option value="">-- Все мои темы --</option>
                        @foreach($topics as $topic)
                            <option value="{{ $topic->id }}" {{ request('topic_id') == $topic->id ? 'selected' : '' }}>
                                {{ $topic->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Сортировка</label>
                    <select name="sort" class="w-full border-gray-300 rounded shadow-sm text-sm" onchange="this.form.submit()">
                        <option value="date_desc" {{ $sort == 'date_desc' ? 'selected' : '' }}>Сначала новые (по дате)</option>
                        <option value="date_asc" {{ $sort == 'date_asc' ? 'selected' : '' }}>Сначала старые (по дате)</option>
                        <option value="complexity_asc" {{ $sort == 'complexity_asc' ? 'selected' : '' }}>Сначала легкие</option>
                        <option value="complexity_desc" {{ $sort == 'complexity_desc' ? 'selected' : '' }}>Сначала сложные</option>
                    </select>
                </div>
            </form>

            <!-- БЛОК 4: СПИСОК ЗАДАЧ -->
            <div class="space-y-4">
                @forelse($assignments as $assignment)
                    @php
                        // Настройки стиля карточки
                        $statusInfo = $statusConfig[$assignment->status] ?? ['label' => 'Неизвестно', 'color' => 'bg-gray-500'];
                        $isRevision = $assignment->status === 'revision_needed';
                    @endphp

                    <div class="bg-white rounded-lg shadow-sm border transition-shadow hover:shadow-md overflow-hidden {{ $isRevision ? 'border-red-400 ring-1 ring-red-400' : 'border-gray-200' }}">
                        
                        <!-- Шапка карточки -->
                        <div class="px-6 py-3 bg-gray-50 border-b flex justify-between items-center flex-wrap gap-4">
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 rounded-full text-xs font-bold text-white {{ $statusInfo['bg'] }}">
                                    {{ $statusInfo['label'] }}
                                </span>
                                
                                @if($assignment->mark_percent !== null)
                                    <span class="font-black text-lg {{ $assignment->mark_percent >= 80 ? 'text-green-600' : ($assignment->mark_percent >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                        Оценка: {{ $assignment->mark_percent }}%
                                    </span>
                                @endif
                            </div>

                            <a href="{{ route('assignments.show', $assignment->id) }}" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-bold py-1.5 px-4 rounded shadow-sm transition-colors">
                                {{ $assignment->status == 'accepted' ? 'Посмотреть решение' : 'Перейти к задаче' }}
                            </a>
                        </div>

                        <!-- Тело карточки -->
                        <div class="p-6">
                            <!-- БЕЙДЖ С ТЕМОЙ -->
                            <div class="mb-3 flex items-center gap-2 text-sm">
                                <span class="font-bold text-gray-800">Задача №{{ $assignment->task_id }}</span>
                                <span class="bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded text-xs font-bold">Сложн: {{ $assignment->task->complexity }}</span>
                                @if($assignment->task->topic)
                                    <span class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs">📂 {{ $assignment->task->topic->name }}</span>
                                @endif
                            </div>

                            <div class="text-gray-700 text-sm line-clamp-3">
                                {!! nl2br(e($assignment->task->task_text)) !!}
                            </div>
                        </div>

                        <!-- Подвал карточки (Даты) -->
                        <div class="px-6 py-2 bg-gray-50 border-t flex justify-between text-xs text-gray-500">
                            <span>Назначено: {{ $assignment->assigned_at->format('d.m.Y H:i') }}</span>
                            @if($assignment->checked_at)
                                <span>Проверено: {{ $assignment->checked_at->format('d.m.Y H:i') }}</span>
                            @elseif($assignment->submitted_at)
                                <span>Отправлено: {{ $assignment->submitted_at->format('d.m.Y H:i') }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center bg-white rounded-lg border text-gray-500">
                        Заданий по выбранным фильтрам не найдено. Отличная работа!
                    </div>
                @endforelse
            </div>

            <!-- ПАГИНАЦИЯ -->
            <div class="mt-6">
                {{ $assignments->links() }}
            </div>

        </div>
    </div>

    <!-- Инициализация графика (если есть данные) -->
    @if(!empty($complexityStats['data']))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('complexityChart');
            new Chart(ctx, { 
                type: 'bar', 
                data: { 
                    labels: {!! json_encode($complexityStats['labels']) !!}, 
                    datasets: [{ 
                        label: 'Решено задач', 
                        data: {!! json_encode($complexityStats['data']) !!}, 
                        backgroundColor: '#3b82f6', 
                        borderRadius: 4
                    }] 
                }, 
                options: { 
                    responsive: true, maintainAspectRatio: false, 
                    plugins: { legend: { display: false }, title: { display: true, text: 'Мой прогресс по сложности (Принятые задачи)' } }, 
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } 
                } 
            });
        });
    </script>
    @endif
</x-app-layout>