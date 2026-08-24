<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('works.variants.index', $variant->work_id) }}" class="text-gray-500 hover:text-blue-600 transition">&larr; К списку вариантов</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Настройки печати: {{ $variant->name }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success')) <div class="p-4 bg-green-100 text-green-700 rounded-lg shadow-sm font-bold">{{ session('success') }}</div> @endif
            @if (session('error')) <div class="p-4 bg-red-100 text-red-700 rounded-lg shadow-sm font-bold">{{ session('error') }}</div> @endif

            <form action="{{ route('variants.updatePrintConfig', $variant->id) }}" method="POST" class="bg-white shadow sm:rounded-lg overflow-hidden border">
                @csrf @method('PUT')

                <!-- БЛОК 1: Тексты (Сохраняются в БД) -->
                <div class="p-6 border-b bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Текстовая информация</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">Дополнительный текст (Инструкция для учеников)</label>
                            <p class="text-xs text-gray-500 mb-2">Будет напечатан под заголовком работы, перед списком задач.</p>
                            <textarea name="print_instructions" rows="3" class="w-full border-gray-300 rounded shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Например: В задачах 1-5 выберите один правильный ответ...">{{ old('print_instructions', $variant->print_instructions) }}</textarea>
                        </div>

                        <div>
                            <label class="block font-medium text-sm text-gray-700 mb-1">Личный комментарий учителя</label>
                            <p class="text-xs text-gray-500 mb-2">Этот текст <span class="text-red-600 font-bold">не печатается</span>. Он нужен только вам (например, как подсказка при проверке). При клонировании варианта другим учителем это поле очищается.</p>
                            <textarea name="teacher_comment" rows="2" class="w-full border-yellow-300 bg-yellow-50 rounded shadow-sm focus:ring-yellow-500 focus:border-yellow-500">{{ old('teacher_comment', $variant->teacher_comment) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- БЛОК 2: Настройки бумаги (Сохраняются в БД) -->
                <div class="p-6 border-b">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Параметры страницы</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block font-medium text-sm text-gray-700 mb-1">Размер шрифта текста (pt)</label>
                                <input type="number" name="print_font_size" value="{{ old('print_font_size', $variant->print_font_size) }}" min="8" max="24" class="w-24 border-gray-300 rounded shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label class="block font-medium text-sm text-gray-700 mb-1">Пропуск между задачами (в строках)</label>
                                <input type="number" name="print_spacing_lines" value="{{ old('print_spacing_lines', $variant->print_spacing_lines) }}" min="0" max="10" class="w-24 border-gray-300 rounded shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block font-medium text-sm text-gray-700 mb-1">Размещение на листе А4</label>
                                <select name="print_copies_per_page" class="w-full border-gray-300 rounded shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="1" {{ $variant->print_copies_per_page == 1 ? 'selected' : '' }}>1 экземпляр (Обычная печать)</option>
                                    <option value="2" {{ $variant->print_copies_per_page == 2 ? 'selected' : '' }}>2 экземпляра (Разрезать А4 пополам)</option>
                                    <option value="4" {{ $variant->print_copies_per_page == 4 ? 'selected' : '' }}>4 экземпляра (Шпаргалки)</option>
                                </select>
                            </div>

                            <div>
                                <label class="flex items-center gap-2 cursor-pointer mt-4">
                                    <div>
                                        <label class="flex items-center gap-2 cursor-pointer mt-4">
                                            <input type="checkbox" name="print_show_name_field" value="1" {{ $variant->print_show_name_field ? 'checked' : '' }} class="rounded text-blue-600 focus:ring-blue-500 w-5 h-5">
                                            <span class="font-medium text-sm text-gray-700">Печатать поле "Фамилия Имя: _______________"</span>
                                        </label>
                                    </div>
                                    
                                    <!-- НОВЫЕ ГАЛОЧКИ -->
                                    <div>
                                        <label class="flex items-center gap-2 cursor-pointer mt-2">
                                            <input type="checkbox" name="print_show_task_id" value="1" {{ $variant->print_show_task_id ? 'checked' : '' }} class="rounded text-blue-600 focus:ring-blue-500 w-5 h-5">
                                            <span class="font-medium text-sm text-gray-700">Печатать номер задачи (ID из базы)</span>
                                        </label>
                                    </div>
                                    <div>
                                        <label class="flex items-center gap-2 cursor-pointer mt-2">
                                            <input type="checkbox" name="print_show_complexity" value="1" {{ $variant->print_show_complexity ? 'checked' : '' }} class="rounded text-blue-600 focus:ring-blue-500 w-5 h-5">
                                            <span class="font-medium text-sm text-gray-700">Печатать сложность задачи</span>
                                        </label>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- БЛОК 3: Отправка на принтер -->
                <div class="p-6 bg-gray-50 flex items-center justify-between">
                    <button type="submit" class="text-blue-600 hover:underline text-sm font-bold">
                        💾 Сохранить настройки в базу
                    </button>
                </div>
            </form>

            <!-- ФОРМА ОТПРАВКИ НА ПЕЧАТЬ (Открывается в новой вкладке) -->
            <form action="{{ route('variants.print', $variant->id) }}" method="GET" target="_blank" class="bg-indigo-50 shadow sm:rounded-lg overflow-hidden border border-indigo-200 p-6 flex flex-col md:flex-row items-end gap-4 justify-between">
                
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-indigo-900 mb-2">Генерация документа</h3>
                    <p class="text-sm text-indigo-700 mb-4">Выберите группу (чтобы она напечаталась в шапке) и вид документа.</p>
                    
                    <div class="flex gap-4 items-end">
                        <div class="w-64">
                            <label class="block font-medium text-sm text-indigo-900 mb-1">Для какой группы печатаем?</label>
                            <select name="group_id" class="w-full border-indigo-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">-- Оставить пустым --</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->grade ? $group->grade.' кл - ' : '' }}{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <label class="flex items-center gap-2 cursor-pointer mb-2">
                            <input type="checkbox" name="show_answers" value="1" class="rounded text-indigo-600 focus:ring-indigo-500 w-5 h-5 border-indigo-300">
                            <span class="font-bold text-sm text-indigo-900">Версия для учителя (с ответами)</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded shadow text-lg transition whitespace-nowrap">
                    🖨 Сформировать PDF
                </button>
            </form>

        </div>
    </div>
</x-app-layout>