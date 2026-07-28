<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Структура источников
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <a href="{{ route('sources.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            + Создать корневой источник
                        </a>
                    </div>
                    @if (session('success'))
                        <div class="mb-6 p-4 bg-green-100 text-green-700 border border-green-200 rounded">
                            {{ session('success') }}
                        </div>
                    @endif
                    <!-- Рисуем дерево тем с помощью Tailwind CSS -->
                    <ul class="space-y-2">
                        @foreach ($sources as $source)
                            <!-- Передаем самую верхнюю тему с уровнем 0 -->
                            @include('sources.item', ['source' => $source, 'level' => 0])
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>