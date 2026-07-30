<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600 transition">
                    &larr; К списку задач
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Мой прогресс по темам
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200">
                
                <div class="overflow-x-auto overflow-y-auto max-h-[75vh] relative">
                    <table class="w-full text-sm text-left">
<!-- Добавлены классы: sticky, top-0, z-10 и shadow-sm -->
                        <thead class="text-xs text-gray-600 uppercase bg-gray-100 border-b sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th scope="col" class="px-4 py-3 w-1/3">Тема курса</th>
                                <th scope="col" class="px-4 py-3 text-center" title="Количество задач по этой теме в общей базе">В базе</th>
                                <th scope="col" class="px-4 py-3 text-center" title="Сколько задач задано">Назначено</th>
                                <th scope="col" class="px-4 py-3 text-center" title="Ожидают проверки учителем">На проверке</th>
                                <th scope="col" class="px-4 py-3 text-center" title="Успешно решено">Решено</th>
                                <th scope="col" class="px-4 py-3 text-center bg-green-50" title="Средний балл по решенным">Ср. Оценка</th>
                                <th scope="col" class="px-4 py-3 text-center bg-green-50" title="Средняя сложность решенных задач">Сложн. (Реш)</th>
                                <th scope="col" class="px-4 py-3 text-center bg-yellow-50" title="Средняя сложность нерешенных задач">Сложн. (Нереш)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topicsTree as $topic)
                                @include('assignments.progress-row', ['topic' => $topic, 'level' => 0])
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
            
            <div class="mt-4 text-sm text-gray-500 italic">
                * Сложн. (Нереш) — средняя сложность задач, которые вам назначены, но еще не имеют статуса "Принято" (включая те, что на проверке или на доработке).
            </div>
        </div>
    </div>
</x-app-layout>