<!DOCTYPE html>
<html lang="ru" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <title>Работы группы: {{ $group->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 antialiased py-8">

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="bg-indigo-600 px-6 py-8 rounded-xl shadow-md mb-6 text-center text-white">
            <h1 class="font-bold text-3xl">Группа {{ $group->name }}</h1>
            <p class="mt-2 text-indigo-200">Ваш список учебных работ и заданий</p>
        </div>

        <!-- ОДНА КОЛОНКА ДЛЯ КАРТОЧЕК -->
        <div class="grid grid-cols-1 gap-5">
            @forelse($assignments as $history)
                @php
                    // Определяем целевую дату (срок, а если его нет - дату выдачи)
                    $targetDate = $history->due_date ?: $history->assigned_at;
                    
                    // Считаем, что срок прошел, если конец этого дня уже в прошлом
                    $isExpired = $targetDate->copy()->endOfDay()->isPast();
                    
                    // Цвета в зависимости от статуса
                    $bgClass = $isExpired ? 'bg-gray-50 border-gray-300 opacity-80' : 'bg-blue-50 border-blue-300';
                    $titleColor = $isExpired ? 'text-gray-500' : 'text-blue-600';
                @endphp

                @if($history->variant->public_hash)
                    <a href="{{ route('public.variant', $history->variant->public_hash) }}" class="block {{ $bgClass }} p-6 rounded-xl border hover:shadow-md transition group">
                        
                        <!-- ШАПКА КАРТОЧКИ: ТЕМА И СРОК -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-3 border-b border-black/5 pb-3">
                            <div class="text-xs font-bold uppercase tracking-wider {{ $titleColor }}">
                                {{ $history->variant->work->title ?? 'Без названия' }}
                            </div>
                            
                            @if($history->due_date)
                                <div class="text-xs font-bold {{ $isExpired ? 'text-gray-600 bg-gray-200' : 'text-blue-800 bg-blue-200' }} px-3 py-1.5 rounded-md shadow-sm">
                                    {{ $isExpired ? 'Срок истек:' : 'Срок сдачи:' }} до {{ $history->due_date->format('d.m.Y') }}
                                </div>
                            @else
                                <div class="text-xs font-bold text-gray-500 bg-white px-3 py-1.5 rounded-md border shadow-sm">
                                    Выдано: {{ $history->assigned_at->format('d.m.Y') }}
                                </div>
                            @endif
                        </div>
                        
                        <!-- НАЗВАНИЕ ВАРИАНТА -->
                        <div class="font-black {{ $isExpired ? 'text-gray-700' : 'text-gray-900' }} text-xl group-hover:text-indigo-600 transition">
                            {{ $history->variant->name }}
                        </div>
                        
                        <!-- КОММЕНТАРИЙ УЧИТЕЛЯ (НОМЕРА ЗАДАЧ) -->
                        @if($history->public_comment)
                            <div class="mt-4 text-sm {{ $isExpired ? 'text-gray-600 bg-white/50' : 'text-gray-800 bg-white/80' }} p-4 rounded-lg shadow-sm border border-black/5">
                                <strong class="{{ $isExpired ? 'text-gray-700' : 'text-gray-900' }}">Задание:</strong> {!! nl2br(e($history->public_comment)) !!}
                            </div>
                        @endif

                    </a>
                @else
                    <!-- Если хэша нет (не опубликовано) -->
                    <div class="block bg-gray-50 p-6 rounded-xl border border-gray-200 opacity-60">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-3 border-b border-gray-200 pb-3">
                            <div class="text-xs text-gray-500 font-bold uppercase tracking-wider">
                                {{ $history->variant->work->title ?? 'Без названия' }}
                            </div>
                            <div class="text-xs font-bold text-red-500 bg-white px-3 py-1.5 rounded-md border shadow-sm">
                                Вариант не опубликован
                            </div>
                        </div>
                        
                        <div class="font-bold text-gray-700 text-xl">
                            {{ $history->variant->name }}
                        </div>
                        
                        @if($history->public_comment)
                            <div class="mt-4 text-sm text-gray-600 bg-white/50 p-4 rounded-lg">
                                <strong>Задание:</strong> {!! nl2br(e($history->public_comment)) !!}
                            </div>
                        @endif
                    </div>
                @endif
            @empty
                <div class="col-span-full bg-white p-12 text-center text-gray-500 rounded-xl border shadow-sm">
                    Этой группе пока не выдано ни одной работы.
                </div>
            @endforelse
        </div>

    </div>
</body>
</html>