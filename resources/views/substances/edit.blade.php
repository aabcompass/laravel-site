<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($substance) ? "Редактирование: {$substance->name}" : 'Новое вещество' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ isset($substance) ? route('substances.update', $substance->id) : route('substances.store') }}" method="POST" class="space-y-6">
                @csrf
                @if(isset($substance)) @method('PUT') @endif

                @if ($errors->any())
                    <div class="p-4 bg-red-100 text-red-700 rounded-lg shadow-sm font-bold">
                        <ul class="list-disc pl-5">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
                    </div>
                @endif

                <!-- ШАПКА: ОСНОВНЫЕ ДАННЫЕ -->
                <div class="bg-white shadow sm:rounded-lg p-6 border">
                    <h3 class="text-lg font-bold border-b pb-2 mb-4">Основные данные</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">Название вещества <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $substance->name ?? '') }}" class="w-full border-gray-300 rounded shadow-sm focus:ring-blue-500" required autofocus>
                        </div>
                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">Агрегатное состояние</label>
                            <select name="state" class="w-full border-gray-300 rounded shadow-sm focus:ring-blue-500">
                                <option value="">-- Не указано --</option>
                                <option value="solid" {{ old('state', $substance->state ?? '') == 'solid' ? 'selected' : '' }}>Твердое тело</option>
                                <option value="liquid" {{ old('state', $substance->state ?? '') == 'liquid' ? 'selected' : '' }}>Жидкость</option>
                                <option value="gas" {{ old('state', $substance->state ?? '') == 'gas' ? 'selected' : '' }}>Газ</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- СЕТКА ФИЗИЧЕСКИХ СВОЙСТВ -->
                <div class="bg-white shadow sm:rounded-lg border overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
                        <h3 class="font-bold text-gray-800">Физические константы</h3>
                        <span class="text-xs text-gray-500">Оставьте поле пустым, если данных нет</span>
                    </div>

                    <table class="w-full text-sm text-left">
                        <thead class="bg-white border-b text-gray-600">
                            <tr>
                                <th class="px-6 py-3 w-1/3">Свойство</th>
                                <th class="px-6 py-3 w-1/3">Числовое значение</th>
                                <th class="px-6 py-3 w-1/3">Примечания (напр. "при 20°C")</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($properties as $prop)
                                @php
                                    // Достаем сохраненное значение (если оно есть)
                                    $savedVal = isset($values[$prop->id]) ? $values[$prop->id]->value : '';
                                    $savedNotes = isset($values[$prop->id]) ? $values[$prop->id]->notes : '';
                                @endphp
                                <tr class="border-b hover:bg-gray-50 transition">
                                    <td class="px-6 py-3">
                                        <div class="font-bold text-gray-800">{{ $prop->name }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            @if($prop->symbol)<span class="bg-gray-200 px-1 rounded">{{ $prop->symbol }}</span>@endif
                                            {{ $prop->units }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-3">
                                        <!-- Используем step="any", чтобы можно было вводить десятичные числа -->
                                        <input type="number" step="any" name="props[{{ $prop->id }}][value]" value="{{ old("props.{$prop->id}.value", $savedVal) }}" class="w-full border-gray-300 rounded shadow-sm focus:ring-blue-500 py-1.5" placeholder="Значение">
                                    </td>
                                    <td class="px-6 py-3">
                                        <input type="text" name="props[{{ $prop->id }}][notes]" value="{{ old("props.{$prop->id}.notes", $savedNotes) }}" class="w-full border-gray-300 rounded shadow-sm focus:ring-blue-500 py-1.5 text-xs" placeholder="Примечания">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center gap-4 bg-white p-4 shadow sm:rounded-lg border">
                    <x-primary-button class="text-lg py-2 px-6">{{ isset($substance) ? 'Сохранить изменения' : 'Добавить вещество' }}</x-primary-button>
                    <a href="{{ route('substances.index') }}" class="text-gray-600 hover:underline">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>