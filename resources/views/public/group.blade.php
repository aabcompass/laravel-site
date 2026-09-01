<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Работы группы: {{ $group->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800 antialiased py-8">

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="bg-indigo-600 px-6 py-8 rounded-lg shadow-md mb-6 text-center text-white">
            <h1 class="font-bold text-3xl">Учебная группа {{ $group->name }}</h1>
            <p class="mt-2 text-indigo-200">Здесь собраны все выданные вам варианты работ</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($assignments as $history)
                @if($history->variant->public_hash)
                    <!-- Если хэш есть - выводим кликабельную ссылку -->
                    <a href="{{ route('public.variant', $history->variant->public_hash) }}" class="block bg-white p-5 rounded-lg border border-gray-200 hover:border-indigo-400 hover:shadow-md transition group">
                        <div class="text-xs text-indigo-500 font-bold mb-1 uppercase tracking-wider">
                            {{ $history->variant->work->title ?? 'Без названия' }}
                        </div>
                        <div class="font-bold text-gray-900 text-lg group-hover:text-indigo-600 transition">
                            {{ $history->variant->name }}
                        </div>
                        <div class="mt-4 text-xs text-gray-400 font-medium border-t border-gray-100 pt-2 flex justify-between">
                            <span>Выдано</span>
                            <span>{{ $history->assigned_at->format('d.m.Y') }}</span>
                        </div>
                    </a>
                @else
                    <!-- Если хэша нет - выводим некликабельную серую карточку (защита от ошибок) -->
                    <div class="block bg-gray-50 p-5 rounded-lg border border-gray-200 opacity-75">
                        <div class="text-xs text-gray-500 font-bold mb-1 uppercase tracking-wider">
                            {{ $history->variant->work->title ?? 'Без названия' }}
                        </div>
                        <div class="font-bold text-gray-700 text-lg">
                            {{ $history->variant->name }}
                        </div>
                        <div class="mt-4 text-xs text-red-400 font-medium border-t border-gray-200 pt-2 flex justify-between">
                            <span>Вариант не опубликован</span>
                            <span>{{ $history->assigned_at->format('d.m.Y') }}</span>
                        </div>
                    </div>
                @endif
            @empty
                <div class="col-span-full bg-white p-8 text-center text-gray-500 rounded-lg border shadow-sm">
                    Этой группе пока не выдано ни одной работы.
                </div>
            @endforelse
        </div>

    </div>
</body>
</html>