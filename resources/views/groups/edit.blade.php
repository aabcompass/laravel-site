<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($group) ? "Редактирование группы: {$group->name}" : 'Создание учебной группы' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded shadow-sm"><ul class="list-disc pl-5">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul></div>
                @endif

                <form action="{{ isset($group) ? route('groups.update', $group->id) : route('groups.store') }}" method="POST" class="space-y-4">
                    @csrf
                    @if(isset($group)) @method('PUT') @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">Название группы <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $group->name ?? '') }}" placeholder="Например: 11А ФизМат" class="border-gray-300 rounded-md shadow-sm w-full" required autofocus>
                        </div>
                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">Параллель (Класс)</label>
                            <input type="number" name="grade" value="{{ old('grade', $group->grade ?? '') }}" min="1" max="12" placeholder="Например: 11" class="border-gray-300 rounded-md shadow-sm w-full">
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-700 mb-1">Описание (необязательно)</label>
                        <textarea name="description" rows="3" class="border-gray-300 rounded-md shadow-sm w-full">{{ old('description', $group->description ?? '') }}</textarea>
                    </div>

                    <div class="flex items-center gap-4 mt-6 pt-4 border-t">
                        <x-primary-button>{{ isset($group) ? 'Сохранить изменения' : 'Создать группу' }}</x-primary-button>
                        <a href="{{ route('groups.index') }}" class="text-gray-600 hover:underline">Отмена</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>