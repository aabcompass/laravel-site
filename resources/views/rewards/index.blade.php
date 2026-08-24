<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Каталог наград</h2>
            <a href="{{ route('rewards.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow text-sm">+ Добавить награду</a>
        </div>
    </x-slot>

    <!-- MathJax для отображения символов LaTeX -->
    <script> MathJax = { tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] } }; </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success')) <div class="p-4 bg-green-100 text-green-700 rounded shadow-sm font-bold">{{ session('success') }}</div> @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 w-16 text-center">Визуал</th>
                            <th class="px-6 py-3 w-32">Ключ / Z (A)</th>
                            <th class="px-6 py-3">Название и Символ</th>
                            <th class="px-6 py-3 text-center">Носитель</th>
                            <th class="px-6 py-3 text-center">За ответ</th>
                            <th class="px-6 py-3 text-right">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rewards as $reward)
                            <tr class="bg-white border-b hover:bg-indigo-50 transition">
                                <td class="px-6 py-3 text-center">
                                    @if($reward->svg_content)
                                        <!-- Классы [&>svg]:... заставляют любую вставленную SVG-картинку масштабироваться под размер блока -->
                                        <div class="h-12 w-12 bg-gray-900 rounded inline-flex items-center justify-center shadow-sm [&>svg]:w-full [&>svg]:h-full [&>svg]:object-contain p-1">
                                            {!! $reward->svg_content !!}
                                        </div>
                                    @else
                                        <div class="h-12 w-12 bg-gray-100 rounded inline-flex items-center justify-center text-gray-400 border text-xs">Нет</div>
                                    @endif
                                </td>
                                
                                <td class="px-6 py-3">
                                    <div class="font-bold text-gray-800">{{ $reward->key }}</div>
                                    <div class="text-xs text-gray-500">Z: {{ $reward->z_number ?? '-' }}, A: {{ $reward->a_number ?? '-' }}</div>
                                </td>
                                
                                <td class="px-6 py-3">
                                    <div class="font-bold text-lg text-indigo-700 flex items-center gap-3">
                                        {{ $reward->name }} 
                                        @if($reward->symbol_latex)
                                            <span class="text-gray-900 bg-gray-100 px-2 py-0.5 rounded border text-sm">${!! $reward->symbol_latex !!}$</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500 truncate w-64" title="{{ $reward->public_desc }}">{{ $reward->public_desc ?? 'Описания нет' }}</div>
                                </td>
                                
                                <td class="px-6 py-3 text-center">
                                    <span class="bg-gray-200 text-gray-700 px-2 py-1 rounded text-xs font-bold">{{ $reward->carrier_type ?? 'Виртуальная' }}</span>
                                </td>

                                <td class="px-6 py-3 text-center">
                                    @if($reward->is_for_answer) <span class="text-green-500 font-bold text-lg" title="Можно выдавать на уроке">✓</span> @else <span class="text-gray-300">—</span> @endif
                                </td>
                                
                                <td class="px-6 py-3 text-right space-x-3">
                                    <a href="{{ route('rewards.edit', $reward->id) }}" class="text-blue-600 font-bold hover:underline">Изменить</a>
                                    <form action="{{ route('rewards.destroy', $reward->id) }}" method="POST" class="inline" onsubmit="return confirm('Удалить эту награду?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">Каталог наград пуст. Создайте первую!</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $rewards->links() }}</div>
        </div>
    </div>
</x-app-layout>