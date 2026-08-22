<x-app-layout>
    <x-slot name="header">
        <div class="flex gap-3">
            <a href="{{ route('users.bulkCreate') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow text-sm">
                ⚡ Массовое добавление
            </a>
            <a href="{{ route('users.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow text-sm">
                + Создать пользователя
            </a>
        </div>
    </x-slot>
    

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if (session('success')) <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">{{ session('success') }}</div> @endif
            @if (session('error')) <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">{{ session('error') }}</div> @endif

            <!-- ФИЛЬТРЫ -->
            <form method="GET" action="{{ route('users.index') }}" class="bg-white p-4 rounded-lg shadow-sm border mb-4 flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Поиск (Имя, Email)</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="w-full border-gray-300 rounded text-sm py-1.5">
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Роль</label>
                    <select name="role_id" class="w-full border-gray-300 rounded text-sm py-1.5">
                        <option value="">Все роли</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Группа</label>
                    <select name="group_id" class="w-full border-gray-300 rounded text-sm py-1.5">
                        <option value="">Все группы</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                 <div class="flex gap-2">
                    <button type="submit" class="bg-gray-800 text-white rounded px-4 py-1.5 text-sm hover:bg-gray-700">Найти</button>
                    <a href="{{ route('users.index') }}" class="bg-gray-200 text-gray-700 rounded px-4 py-1.5 text-sm hover:bg-gray-300">Сброс</a>
                    
                    <!-- КНОПКА ПЕЧАТИ QR -->
                    <button type="submit" formaction="{{ route('users.printQr') }}" formtarget="_blank" class="bg-indigo-600 text-white rounded px-4 py-1.5 text-sm hover:bg-indigo-700 shadow-sm flex items-center gap-1">
                        🖨 Печать QR
                    </button>
                </div>
            </form>

            <!-- ТАБЛИЦА -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3">ФИО</th>
                            <th class="px-6 py-3">Email</th>
                            <th class="px-6 py-3">Группа</th>
                            <th class="px-6 py-3">Роли</th>
                            <th class="px-6 py-3 text-right">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4 font-bold text-gray-900">{{ $user->last_name }} {{ $user->first_name }}</td>
                                <td class="px-6 py-4">{{ $user->email }}</td>
                                <td class="px-6 py-4">
                                    <form action="{{ route('users.updateGroup', $user->id) }}" method="POST" class="m-0">
                                        @csrf
                                        @method('PATCH')
                                        <select name="group_id" onchange="this.form.submit()" class="text-xs border-gray-300 rounded py-1 pl-2 pr-6 hover:border-blue-500 focus:ring-blue-500 w-full max-w-[160px] bg-gray-50 cursor-pointer">
                                            <option value="" class="text-gray-400">-- Без группы --</option>
                                            @foreach($groups as $group)
                                                <option value="{{ $group->id }}" {{ $user->group_id == $group->id ? 'selected' : '' }} class="text-black">
                                                    {{ $group->grade ? $group->grade.' кл - ' : '' }}{{ $group->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($user->roles as $role)
                                            <span class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-xs">{{ $role->name }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <!-- Кнопка Входа под юзером (Режим Бога) -->
                                    <a href="/login-as/{{ $user->id }}" class="text-green-600 hover:underline text-xs" title="Войти как этот пользователь">Войти как</a>
                                    
                                    <a href="{{ route('users.edit', $user->id) }}" class="text-blue-500 hover:underline">Изменить</a>
                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Удалить пользователя?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">Пользователи не найдены.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>