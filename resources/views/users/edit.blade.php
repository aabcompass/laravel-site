<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($user) ? "Редактирование пользователя: {$user->last_name} {$user->first_name}" : 'Новый пользователь' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 border">
                
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded shadow-sm"><ul class="list-disc pl-5">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul></div>
                @endif
                @if (session('error')) <div class="mb-4 p-4 bg-red-100 text-red-700 rounded shadow-sm">{{ session('error') }}</div> @endif

                <form action="{{ isset($user) ? route('users.update', $user->id) : route('users.store') }}" method="POST" class="space-y-6">
                    @csrf
                    @if(isset($user)) @method('PUT') @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">Фамилия <span class="text-red-500">*</span></label>
                            <input type="text" name="last_name" value="{{ old('last_name', $user->last_name ?? '') }}" class="border-gray-300 rounded w-full" required>
                        </div>
                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">Имя <span class="text-red-500">*</span></label>
                            <input type="text" name="first_name" value="{{ old('first_name', $user->first_name ?? '') }}" class="border-gray-300 rounded w-full" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="border-gray-300 rounded w-full" required>
                        </div>
                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">Пароль {{ isset($user) ? '(оставьте пустым, чтобы не менять)' : '*' }}</label>
                            <input type="text" name="password" class="border-gray-300 rounded w-full" {{ isset($user) ? '' : 'required' }}>
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-700 mb-1">Учебная группа</label>
                        <select name="group_id" class="border-gray-300 rounded w-full">
                            <option value="">-- Без группы --</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" {{ old('group_id', $user->group_id ?? '') == $group->id ? 'selected' : '' }}>
                                    {{ $group->grade ? $group->grade.' класс - ' : '' }}{{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="bg-gray-50 p-4 rounded border">
                        <label class="block font-bold text-sm text-gray-700 mb-3">Роли пользователя <span class="text-red-500">*</span></label>
                        <div class="flex flex-wrap gap-4">
                            @foreach($roles as $role)
                                <label class="flex items-center gap-2 cursor-pointer bg-white px-3 py-2 border rounded shadow-sm hover:border-blue-500">
                                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" 
                                        class="rounded text-blue-600"
                                        {{ (is_array(old('roles', $userRoleIds ?? [])) && in_array($role->id, old('roles', $userRoleIds ?? []))) ? 'checked' : '' }}>
                                    <span class="text-sm font-medium">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t">
                        <x-primary-button>{{ isset($user) ? 'Сохранить изменения' : 'Создать пользователя' }}</x-primary-button>
                        <a href="{{ route('users.index') }}" class="text-gray-600 hover:underline">Отмена</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>