<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Вещества и их свойства</h2>
            <a href="{{ route('substances.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow text-sm">+ Добавить вещество</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success')) <div class="p-4 bg-green-100 text-green-700 rounded shadow-sm font-bold">{{ session('success') }}</div> @endif

            <form method="GET" action="{{ route('substances.index') }}" class="bg-white p-4 rounded-lg shadow-sm border flex items-end gap-4 text-sm">
                <div class="flex-1 max-w-md">
                    <label class="block font-medium text-gray-700 mb-1">Поиск по названию</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="w-full border-gray-300 rounded py-1.5 focus:ring-blue-500">
                </div>
                <div class="flex gap-2 pb-0.5">
                    <button type="submit" class="bg-gray-800 text-white rounded px-4 py-1.5 hover:bg-gray-700 shadow">Найти</button>
                    <a href="{{ route('substances.index') }}" class="bg-gray-200 text-gray-700 rounded px-4 py-1.5 hover:bg-gray-300">Сброс</a>
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 w-1/2">Название вещества</th>
                            <th class="px-6 py-3">Состояние</th>
                            <th class="px-6 py-3 text-center">Заполнено свойств</th>
                            <th class="px-6 py-3 text-right">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($substances as $substance)
                            <tr class="bg-white border-b hover:bg-blue-50 transition">
                                <td class="px-6 py-3 font-bold text-gray-900">{{ $substance->name }}</td>
                                <td class="px-6 py-3">
                                    @if($substance->state == 'solid') <span class="text-gray-600">Твердое</span>
                                    @elseif($substance->state == 'liquid') <span class="text-blue-600">Жидкое</span>
                                    @elseif($substance->state == 'gas') <span class="text-gray-400">Газ</span>
                                    @else — @endif
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs font-bold">{{ $substance->property_values_count }}</span>
                                </td>
                                <td class="px-6 py-3 text-right space-x-3">
                                    <a href="{{ route('substances.edit', $substance->id) }}" class="text-blue-600 hover:underline font-bold">Изменить</a>
                                    <form action="{{ route('substances.destroy', $substance->id) }}" method="POST" class="inline" onsubmit="return confirm('Точно удалить это вещество? Все его свойства тоже удалятся.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">Вещества не найдены.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $substances->links() }}</div>
        </div>
    </div>
</x-app-layout>