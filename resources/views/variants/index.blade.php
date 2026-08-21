<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('works.index') }}" class="text-gray-500 hover:text-blue-600 transition">&larr; К списку работ</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $work->title }} <span class="text-gray-400 text-sm font-normal ml-2">(Управление вариантами)</span>
            </h2>
        </div>
    </x-slot>

    <!-- ГЛОБАЛЬНОЕ СОСТОЯНИЕ ALPINE ДЛЯ МОДАЛКИ -->
    <div x-data="{ assignModalOpen: false, currentVariantId: null, currentVariantName: '' }" class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success')) <div class="p-4 bg-green-100 text-green-700 rounded-lg shadow-sm font-bold">{{ session('success') }}</div> @endif
            @if (session('error')) <div class="p-4 bg-red-100 text-red-700 rounded-lg shadow-sm font-bold">{{ session('error') }}</div> @endif

            <div class="bg-white p-4 rounded-lg shadow-sm border flex flex-wrap justify-between items-center gap-4">
                <form action="{{ route('works.variants.store', $work->id) }}" method="POST" class="flex items-center gap-3">
                    @csrf
                    <input type="text" name="name" placeholder="Название (напр. Вариант 1)" class="border-gray-300 rounded text-sm py-2 w-64 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm shadow transition">Создать пустой вариант</button>
                </form>

                <form method="GET" class="flex items-center gap-2">
                    <label class="flex items-center cursor-pointer text-sm text-gray-700 hover:text-blue-600">
                        <input type="checkbox" name="show_archived" value="1" onchange="this.form.submit()" class="rounded text-blue-600 mr-2" {{ $showArchived ? 'checked' : '' }}>
                        Показывать архивные
                    </label>
                </form>
            </div>

            <!-- ТАБЛИЦА ВАРИАНТОВ -->
            <div class="bg-white shadow-sm sm:rounded-lg border">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 w-16 text-center">ID</th>
                            <th class="px-6 py-3 w-1/4">Название и Версия</th>
                            <th class="px-6 py-3 text-center">Задач</th>
                            <th class="px-6 py-3">Автор</th>
                            <th class="px-6 py-3 w-1/3">Выдано группам (Статус)</th>
                            <th class="px-6 py-3 text-right">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($variants as $variant)
                            @php
                                $isAssigned = $variant->assignments->count() > 0;
                                $isAuthorOrAdmin = $variant->author_id === auth()->id() || auth()->user()->hasRole('admin');
                            @endphp
                            <tr x-data="{ isEditingName: false }" class="bg-white border-b transition-colors {{ $variant->is_archived ? 'bg-gray-100 opacity-75' : 'hover:bg-blue-50' }}">
                                
                                <td class="px-6 py-4 text-center font-mono text-xs text-gray-400">#{{ $variant->id }}</td>
                                
                                <td class="px-6 py-4">
                                    <div x-show="!isEditingName" class="font-bold text-gray-900 text-base flex items-center gap-2">
                                        {{ $variant->name }}
                                        <span class="bg-gray-200 text-gray-600 text-[10px] px-1.5 py-0.5 rounded uppercase tracking-wide">v.{{ $variant->version }}</span>
                                    </div>
                                    @if($variant->is_archived)
                                        <div x-show="!isEditingName" class="text-xs text-red-500 font-bold uppercase mt-1">В архиве</div>
                                    @endif

                                    @if($isAuthorOrAdmin)
                                        <form x-show="isEditingName" x-cloak action="{{ route('variants.updateName', $variant->id) }}" method="POST" class="flex items-center gap-2 m-0" @click.away="isEditingName = false">
                                            @csrf @method('PATCH')
                                            <input type="text" name="name" value="{{ $variant->name }}" class="border-gray-300 rounded text-sm py-1 px-2 shadow-sm focus:ring-blue-500 focus:border-blue-500 w-full" autofocus required>
                                            <button type="submit" class="bg-green-500 text-white p-1.5 rounded hover:bg-green-600 transition" title="Сохранить">✓</button>
                                            <button type="button" @click="isEditingName = false" class="bg-gray-300 text-gray-700 p-1.5 rounded hover:bg-gray-400 transition" title="Отмена">✕</button>
                                        </form>
                                    @endif
                                </td>
                                
                                <td class="px-6 py-4 text-center">
                                    <span class="{{ $variant->tasks_count > 0 ? 'text-blue-600 font-bold' : 'text-gray-400' }}">{{ $variant->tasks_count }}</span>
                                </td>

                                <td class="px-6 py-4 italic text-xs">
                                    {{ $variant->author->first_name ?? '' }} {{ $variant->author->last_name ?? '—' }}
                                </td>

                                <!-- СТАТУС ВЫДАЧИ -->
                                <td class="px-6 py-4">
                                    @if($isAssigned)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($variant->assignments as $assignment)
                                                <div class="bg-green-100 border border-green-200 text-green-800 text-xs pl-2 pr-1 py-1 rounded flex items-center gap-2" title="Выдано: {{ $assignment->assigned_at->format('d.m.Y H:i') }}">
                                                    <span class="font-bold">{{ $assignment->group->name ?? 'Группа удалена' }}</span>
                                                    <span class="opacity-75">{{ $assignment->assigned_at->format('d.m.y') }}</span>
                                                    
                                                    <!-- КРЕСТИК ДЛЯ ОТЗЫВА -->
                                                    @if($isAuthorOrAdmin)
                                                        <form action="{{ route('variants.revoke', $assignment->id) }}" method="POST" class="m-0" onsubmit="return confirm('Отменить выдачу варианта этой группе?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="text-green-600 hover:bg-green-200 hover:text-green-900 rounded-full w-4 h-4 flex items-center justify-center transition">&times;</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded border">Черновик (не выдан)</span>
                                    @endif
                                </td>

                                <!-- ДЕЙСТВИЯ (ИСПРАВЛЕНО ДЛЯ FIREFOX) -->
                                <td class="px-6 py-4 text-right">
                                    <!-- click.away перенесен на родительский DIV -->
                                    <div x-data="{ open: false }" @click.away="open = false" class="relative inline-block text-left">
                                        
                                        <button @click="open = !open" class="text-gray-500 hover:text-gray-800 p-2 font-bold focus:outline-none">⋮</button>
                                        
                                        <div x-show="open" x-cloak class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-xl bg-white border z-50 text-left overflow-hidden">
                                            
                                            <a href="{{ route('variants.build', $variant->id) }}" class="block px-4 py-2 text-sm text-blue-600 font-bold hover:bg-blue-50">⚙ Наполнить задачами</a>
                                            
                                            @if($isAuthorOrAdmin)
                                                <button @click="isEditingName = true; open = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">✏ Переименовать</button>
                                            @endif

                                            <!-- ВЫЗОВ МОДАЛКИ -->
                                            @if($isAuthorOrAdmin)
                                                <button @click="assignModalOpen = true; currentVariantId = {{ $variant->id }}; currentVariantName = '{{ $variant->name }}'; open = false" class="w-full text-left px-4 py-2 text-sm text-green-600 font-bold hover:bg-green-50 border-t">🎓 Выдать группе</button>
                                            @endif

                                            <a href="{{ route('variants.printConfig', $variant->id) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 border-t border-b">🖨 Печать</a>

                                            <form action="{{ route('variants.clone', $variant->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">⧉ Клонировать (Себе)</button>
                                            </form>

                                            @if($variant->public_hash)
                                                <a href="{{ route('public.variant', $variant->public_hash) }}" target="_blank" class="block px-4 py-2 text-sm text-indigo-600 font-bold hover:bg-indigo-50">🔗 Публичная ссылка</a>
                                            @endif


                                            @if($isAuthorOrAdmin)
                                                <form action="{{ route('variants.archive', $variant->id) }}" method="POST">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm {{ $variant->is_archived ? 'text-green-600 hover:bg-green-50' : 'text-yellow-600 hover:bg-yellow-50' }}">
                                                        {{ $variant->is_archived ? '⤴ Разархивировать' : '⤵ В архив' }}
                                                    </button>
                                                </form>

                                                <!-- УДАЛЕНИЕ ВАРИАНТА -->
                                                @if($isAssigned)
                                                    <div class="px-4 py-2 text-sm text-gray-400 cursor-not-allowed bg-gray-50 border-t" title="Нельзя удалить: вариант уже выдан">🗑 Удалить</div>
                                                @else
                                                    <form action="{{ route('variants.destroy', $variant->id) }}" method="POST" onsubmit="return confirm('Точно удалить этот вариант? Действие необратимо.')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 border-t">🗑 Удалить</button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">В этой работе пока нет вариантов.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        <!-- МОДАЛЬНОЕ ОКНО ВЫДАЧИ -->
        <div x-show="assignModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div @click.away="assignModalOpen = false" class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                    <h3 class="font-bold text-lg text-gray-800">Выдать вариант группе</h3>
                    <button @click="assignModalOpen = false" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
                </div>
                
                <form :action="`/variants/${currentVariantId}/assign`" method="POST" class="p-6">
                    @csrf
                    <p class="mb-4 text-sm text-gray-600">Выберите учебную группу, которая должна получить доступ к варианту <strong x-text="currentVariantName" class="text-gray-900"></strong>.</p>
                    
                    <select name="group_id" class="w-full border-gray-300 rounded shadow-sm focus:ring-green-500 focus:border-green-500 mb-6" required>
                        <option value="">-- Выберите группу --</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->grade ? $group->grade.' кл - ' : '' }}{{ $group->name }}</option>
                        @endforeach
                    </select>

                    <div class="flex justify-end gap-3">
                        <button type="button" @click="assignModalOpen = false" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition">Отмена</button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white font-bold rounded hover:bg-green-700 shadow transition">Выдать доступ</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>