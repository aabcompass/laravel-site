<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Учебные работы
            </h2>
            <a href="{{ route('works.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow text-sm transition">
                + Создать работу (папку)
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success')) <div class="p-4 bg-green-100 text-green-700 rounded-lg shadow-sm font-bold">{{ session('success') }}</div> @endif
            @if (session('error')) <div class="p-4 bg-red-100 text-red-700 rounded-lg shadow-sm font-bold">{{ session('error') }}</div> @endif

            <!-- ФИЛЬТРЫ -->
            <form method="GET" action="{{ route('works.index') }}" class="bg-white p-4 rounded-lg shadow-sm border flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Поиск по названию</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Например: Кинематика..." class="w-full border-gray-300 rounded text-sm py-1.5 shadow-sm">
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Класс</label>
                    <select name="grade" class="w-full border-gray-300 rounded text-sm py-1.5 shadow-sm">
                        <option value="">-- Все --</option>
                        @for($i=7; $i<=11; $i++)
                            <option value="{{ $i }}" {{ request('grade') == $i ? 'selected' : '' }}>{{ $i }} класс</option>
                        @endfor
                    </select>
                </div>
                <div class="flex-1 min-w-[250px]">
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Тема</label>
                    <select name="topic_id" class="w-full border-gray-300 rounded text-sm py-1.5 shadow-sm">
                        <option value="">-- Все темы --</option>
                        @include('topics.options', ['topics' => $topics, 'level' => 0, 'selectedId' => request('topic_id'), 'currentId' => null])
                    </select>
                </div>
                <div class="flex gap-2 pb-0.5">
                    <button type="submit" class="bg-gray-800 text-white rounded px-4 py-1.5 text-sm hover:bg-gray-700 shadow transition">Поиск</button>
                    <a href="{{ route('works.index') }}" class="bg-gray-200 text-gray-700 rounded px-4 py-1.5 text-sm hover:bg-gray-300 shadow transition">Сброс</a>
                </div>
            </form>

            <!-- СПИСОК РАБОТ -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3">Класс / Тема</th>
                            <th class="px-6 py-3 w-1/3">Название работы</th>
                            <th class="px-6 py-3">Автор</th>
                            <th class="px-6 py-3 text-center">Вариантов</th>
                            <th class="px-6 py-3 text-right">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($works as $work)
                            @php
                                $canEdit = $work->author_id === auth()->id() || auth()->user()->hasRole('admin');
                                $canDelete = $canEdit && $work->variants_count === 0;
                            @endphp
                            <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    @if($work->grade)
                                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded font-bold mb-1">{{ $work->grade }} класс</span><br>
                                    @endif
                                    <span class="text-xs text-gray-500">{{ $work->topic->name ?? '—' }}</span>
                                </td>
                                
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900 text-base mb-1">{{ $work->title }}</div>
                                    @if($work->description)
                                        <div class="text-xs text-gray-500 truncate" title="{{ $work->description }}">{{ $work->description }}</div>
                                    @endif
                                </td>
                                
                                <td class="px-6 py-4 italic text-xs">
                                    {{ $work->author->first_name ?? '' }} {{ $work->author->last_name ?? '—' }}
                                </td>
                                
                                <td class="px-6 py-4 text-center">
                                    <span class="{{ $work->variants_count > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }} px-2.5 py-0.5 rounded-full font-bold text-xs">
                                        {{ $work->variants_count }}
                                    </span>
                                </td>
                                
                                <td class="px-6 py-4 text-right space-x-3">
                                    <!-- Кнопка перехода к вариантам (будущий контроллер) -->
                                    <a href="#" class="text-indigo-600 font-bold hover:underline">Конструктор &rarr;</a>
                                    
                                    @if($canEdit)
                                        <a href="{{ route('works.edit', $work->id) }}" class="text-gray-500 hover:text-gray-800 hover:underline">Изменить</a>
                                    @else
                                        <span class="text-gray-300 cursor-not-allowed" title="Только автор может изменить">Изменить</span>
                                    @endif

                                    @if($canDelete)
                                        <form action="{{ route('works.destroy', $work->id) }}" method="POST" class="inline m-0" onsubmit="return confirm('Удалить работу?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:underline">Удалить</button>
                                        </form>
                                    @else
                                        <span class="text-gray-300 cursor-not-allowed" title="{{ $canEdit ? 'Нельзя удалить: внутри есть варианты' : 'Только автор может удалить' }}">Удалить</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Работы не найдены. Создайте первую!</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $works->links() }}</div>

        </div>
    </div>
</x-app-layout>