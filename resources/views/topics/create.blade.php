<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Создание новой темы
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('topics.store') }}" method="POST" class="space-y-4">
                    @csrf <!-- Защитный токен обязателен для всех POST форм -->

                    <div>
                        <label class="block font-medium text-sm text-gray-700">Название</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1" required autofocus>
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-700">Описание</label>
                        <textarea name="description" rows="3" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1">{{ old('description') }}</textarea>
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-700">Родительская тема</label>
                        <select name="parent_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full mt-1">
                            <option value="0">--- Верхний уровень ---</option>
                            @include('topics.options', ['topics' => $allTopics, 'level' => 0, 'selectedId' => old('parent_id', $selectedParentId), 'currentId' => null])
                        </select>
                    </div>

                    <div class="flex items-center gap-4 mt-6">
                        <x-primary-button>Создать тему</x-primary-button>
                        <a href="{{ route('topics.index') }}" class="text-gray-600 hover:underline">Отмена</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>