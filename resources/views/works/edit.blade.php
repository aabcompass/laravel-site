<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($work) ? "Редактирование работы: {$work->title}" : 'Создание новой работы' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border">
                
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded shadow-sm">
                        <ul class="list-disc pl-5 font-bold">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
                    </div>
                @endif
                @if (session('error')) <div class="mb-4 p-4 bg-red-100 text-red-700 rounded shadow-sm font-bold">{{ session('error') }}</div> @endif

                <form action="{{ isset($work) ? route('works.update', $work->id) : route('works.store') }}" method="POST" class="space-y-6">
                    @csrf
                    @if(isset($work)) @method('PUT') @endif

                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 mb-6">
                        <p class="text-sm text-blue-800">
                            <strong>Работа (Урок)</strong> — это просто папка, которая объединяет варианты по одной теме. Внутри нее вы создадите Варианты, наполните их задачами и выдадите ученикам.
                        </p>
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-700 mb-1">Название работы <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $work->title ?? '') }}" placeholder="Например: Самостоятельная работа по Кинематике" class="border-gray-300 rounded-md shadow-sm w-full focus:ring-blue-500 focus:border-blue-500" required autofocus>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">Тема (из справочника) <span class="text-red-500">*</span></label>
                            <select name="topic_id" class="border-gray-300 rounded-md shadow-sm w-full focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="">-- Выберите тему --</option>
                                @include('topics.options', ['topics' => $topics, 'level' => 0, 'selectedId' => old('topic_id', $work->topic_id ?? null), 'currentId' => null])
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">Для какого класса</label>
                            <select name="grade" class="border-gray-300 rounded-md shadow-sm w-full focus:ring-blue-500 focus:border-blue-500">
                                <option value="">-- Любой --</option>
                                @for($i=7; $i<=11; $i++)
                                    <option value="{{ $i }}" {{ old('grade', $work->grade ?? '') == $i ? 'selected' : '' }}>{{ $i }} класс</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-sm text-gray-700 mb-1">Краткое описание (необязательно)</label>
                        <textarea name="description" rows="3" class="border-gray-300 rounded-md shadow-sm w-full focus:ring-blue-500 focus:border-blue-500">{{ old('description', $work->description ?? '') }}</textarea>
                    </div>

                    <div class="flex items-center gap-4 mt-6 pt-6 border-t">
                        <x-primary-button class="py-2.5 px-6 text-sm">
                            {{ isset($work) ? 'Сохранить изменения' : 'Создать работу' }}
                        </x-primary-button>
                        <a href="{{ route('works.index') }}" class="text-gray-600 hover:underline text-sm">Отмена</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>