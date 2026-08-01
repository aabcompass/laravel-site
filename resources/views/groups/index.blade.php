<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Учебные группы</h2>
            <a href="{{ route('groups.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow text-sm">
                + Создать группу
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if (session('success')) <div class="mb-4 p-4 bg-green-100 text-green-700 rounded shadow-sm">{{ session('success') }}</div> @endif
            @if (session('error')) <div class="mb-4 p-4 bg-red-100 text-red-700 rounded shadow-sm">{{ session('error') }}</div> @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3">Класс</th>
                            <th class="px-6 py-3">Название</th>
                            <th class="px-6 py-3">Описание</th>
                            <th class="px-6 py-3 text-center">Учеников</th>
                            <th class="px-6 py-3 text-right">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groups as $group)
                            <tr class="bg-white border-b hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-bold text-gray-900">
                                    {{ $group->grade ? $group->grade . ' класс' : '—' }}
                                </td>
                                <td class="px-6 py-4 font-bold text-blue-600">{{ $group->name }}</td>
                                <td class="px-6 py-4">{{ $group->description ?? '—' }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="bg-indigo-100 text-indigo-800 px-2.5 py-0.5 rounded-full font-bold">
                                        {{ $group->students_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <a href="{{ route('groups.edit', $group->id) }}" class="text-blue-500 hover:underline">Изменить</a>
                                    
                                    @if($group->students_count > 0)
                                        <span class="text-gray-400 cursor-not-allowed" title="В группе есть ученики">Удалить</span>
                                    @else
                                        <form action="{{ route('groups.destroy', $group->id) }}" method="POST" class="inline m-0" onsubmit="return confirm('Удалить группу?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:underline">Удалить</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Группы не найдены. Создайте первую!</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>