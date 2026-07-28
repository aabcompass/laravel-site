<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Редактирование источника: {{ $source->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <!-- Показ ошибок валидации -->
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                @if (session('error'))
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">{{ session('error') }}</div>
                @endif

                <form action="{{ route('sources.update', $source->id) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT') <!-- Laravel перехватит POST и поймет, что это UPDATE -->

                    <div>
                        <label class="block font-medium text-sm text-gray-700">Название</label>
                        <input type="text" name="name" value="{{ old('name', $source->name) }}" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" required>
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-700">Описание</label>
                        <textarea name="description" rows="3" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1">{{ old('description', $source->description) }}</textarea>
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-700">Родительский источник</label>
                        <select name="parent_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1">
                            <option value="0">--- Верхний уровень ---</option>
                            @include('sources.options', ['sources' => $allSources, 'level' => 0, 'selectedId' => old('parent_id', $source->parent_id), 'currentId' => $source->id])
                        </select>
                    </div>

                    <div class="flex items-center gap-4 mt-6">
                        <!-- Используем готовый встроенный компонент кнопки Laravel Breeze -->
                        <x-primary-button>
                            Сохранить изменения
                        </x-primary-button>
                        
                        <a href="{{ route('sources.index') }}" class="text-gray-600 hover:underline">Отмена</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>