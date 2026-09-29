<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Мои награды и достижения</h2>
    </x-slot>

    <!-- MathJax для отрисовки LaTeX-символов наград -->
    <script> MathJax = { tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] } }; </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="bg-indigo-600 px-6 py-6 rounded-lg shadow-md mb-6 text-white flex items-center gap-4">
                <div class="text-4xl">🏆</div>
                <div>
                    <h1 class="font-bold text-2xl">Ваша витрина трофеев</h1>
                    <p class="mt-1 text-indigo-200 text-sm">Здесь собраны ваши достижения по месяцам.</p>
                </div>
            </div>

            @if($rewards->isEmpty())
                <div class="bg-white p-12 text-center rounded-lg shadow-sm border border-gray-200">
                    <div class="text-5xl mb-4 grayscale opacity-50">🏅</div>
                    <h3 class="text-xl font-bold text-gray-500">У вас пока нет наград</h3>
                    <p class="text-gray-400 mt-2">Отлично поработайте, чтобы заработать свою первую!</p>
                </div>
            @else
                <!-- Группируем награды по месяцам на лету прямо в шаблоне -->
                @php
                    $groupedRewards = $rewards->groupBy(function($item) {
                        // Символ 'L' вернет именительный падеж месяца (напр., "Сентябрь" вместо "Сентября")
                        return \Illuminate\Support\Str::ucfirst($item->created_at->translatedFormat('M Y'));
                    });
                @endphp

                @foreach($groupedRewards as $month => $monthRewards)
                    <div class="mb-8">
                        <h3 class="text-xl font-black text-gray-800 mb-4 flex items-center gap-3">
                            <span>{{ $month }}</span>
                            <div class="h-px bg-gray-300 flex-1"></div>
                            <span class="text-xs font-bold text-gray-400 bg-gray-100 px-2 py-1 rounded">{{ $monthRewards->count() }} шт.</span>
                        </h3>
                        
                        <!-- Плитка: от 2 колонок на мобилках до 5 на широких экранах -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            @foreach($monthRewards as $sr)
                                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5 flex flex-col items-center justify-center text-center hover:shadow-md transition hover:border-indigo-300 group">
                                    
                                    <!-- Иконка награды -->
                                    <div class="w-20 h-20 mb-3 flex items-center justify-center bg-gradient-to-br from-indigo-50 to-purple-50 rounded-full border border-indigo-100 shadow-inner group-hover:scale-110 transition-transform duration-300">
                                        @if($sr->reward->svg_content)
                                            <div class="w-12 h-12 [&>svg]:w-full [&>svg]:h-full">{!! $sr->reward->svg_content !!}</div>
                                        @elseif($sr->reward->symbol_latex)
                                            <span class="font-bold text-2xl text-indigo-700">${!! $sr->reward->symbol_latex !!}$</span>
                                        @else
                                            <span class="text-sm font-bold text-indigo-700">{{ $sr->reward->key }}</span>
                                        @endif
                                    </div>
                                    
                                    <!-- Основание (За что) -->
                                    <div class="font-bold text-gray-800 text-sm leading-tight mb-3 line-clamp-3" title="{{ $sr->reason }}">
                                        {{ $sr->reason ?? 'Награда за успехи' }}
                                    </div>
                                    
                                    <!-- Дата получения (без времени) -->
                                    <div class="mt-auto text-xs font-medium text-gray-500 bg-gray-50 px-2 py-1 rounded-md border border-gray-100 w-full">
                                        {{ $sr->created_at->format('d.m.Y') }}
                                    </div>
                                    
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <!-- Пагинация -->
                <div class="mt-8">
                    {{ $rewards->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>