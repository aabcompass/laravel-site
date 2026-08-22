<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Массовое добавление учеников
            </h2>
            <a href="{{ route('users.index') }}" class="text-gray-500 hover:text-blue-600 transition text-sm font-bold">&larr; К списку пользователей</a>
        </div>
    </x-slot>

    <!-- Alpine.js логика обработки текста на лету -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('bulkImport', () => ({
                rawText: '',
                groupId: '',
                students: [],

                // Функция разбирает текст при каждом нажатии клавиши
                parseText() {
                    let lines = this.rawText.split('\n');
                    this.students = [];
                    
                    lines.forEach(line => {
                        // Убираем лишние пробелы по краям и двойные пробелы внутри
                        line = line.trim().replace(/\s+/g, ' ');
                        
                        if (line.length === 0) return; // Пропускаем пустые строки
                        
                        let parts = line.split(' ');
                        
                        this.students.push({
                            lastName: parts[0] || '',
                            firstName: parts[1] || '',
                            // Запоминаем оригинал на случай, если там больше двух слов, чтобы подсветить желтым
                            original: line,
                            hasExtraWords: parts.length > 2,
                            isInvalid: parts.length < 2
                        });
                    });
                }
            }));
        });
    </script>

    <div class="py-12" x-data="bulkImport()">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg overflow-hidden border">
                
                <form action="{{ route('users.bulkStore') }}" method="POST" class="flex flex-col lg:flex-row h-full min-h-[600px]">
                    @csrf

                    <!-- ЛЕВАЯ КОЛОНКА: ВВОД ДАННЫХ -->
                    <div class="w-full lg:w-1/3 bg-gray-50 p-6 border-r flex flex-col gap-6">
                        
                        <div>
                            <label class="block font-bold text-sm text-gray-700 mb-2">1. Выберите группу <span class="text-red-500">*</span></label>
                            <select name="group_id" x-model="groupId" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                <option value="">-- Обязательно выберите группу --</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->grade ? $group->grade.' кл - ' : '' }}{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex-1 flex flex-col">
                            <label class="block font-bold text-sm text-gray-700 mb-2">2. Вставьте список (Фамилия Имя)</label>
                            <p class="text-xs text-gray-500 mb-2">Каждый ученик с новой строки. Если будет отчество — оно отбросится.</p>
                            
                            <textarea 
                                x-model="rawText" 
                                @input="parseText()"
                                class="w-full flex-1 border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm leading-relaxed whitespace-pre" 
                                placeholder="Иванов Иван&#10;Петров Петр&#10;Сидоров Алексей"
                                required
                            ></textarea>
                        </div>

                        <button type="submit" :disabled="!groupId || students.length === 0" class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 text-white font-bold py-3 px-4 rounded shadow transition text-center w-full">
                            Добавить в базу (<span x-text="students.length"></span>)
                        </button>

                    </div>

                    <!-- ПРАВАЯ КОЛОНКА: ПРЕДПРОСМОТР (ТАБЛИЦА) -->
                    <div class="w-full lg:w-2/3 p-6 flex flex-col">
                        <h3 class="font-bold text-lg text-gray-800 mb-4 border-b pb-2">3. Контрольная таблица (Предпросмотр)</h3>
                        
                        <div x-show="students.length === 0" class="flex-1 flex items-center justify-center text-gray-400 italic border-2 border-dashed rounded-lg">
                            Вставьте текст слева, чтобы увидеть таблицу предпросмотра
                        </div>

                        <div x-show="students.length > 0" x-cloak class="flex-1 overflow-y-auto border rounded shadow-sm bg-white">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-indigo-50 text-indigo-900 sticky top-0 shadow-sm">
                                    <tr>
                                        <th class="px-4 py-2 w-12 text-center">№</th>
                                        <th class="px-4 py-2 w-1/3">Фамилия</th>
                                        <th class="px-4 py-2 w-1/3">Имя</th>
                                        <th class="px-4 py-2">Статус обработки</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(student, index) in students" :key="index">
                                        <tr class="border-b hover:bg-gray-50" :class="{ 'bg-red-50': student.isInvalid }">
                                            <td class="px-4 py-2 text-center text-gray-500" x-text="index + 1"></td>
                                            
                                            <!-- Скрытые инпуты, которые отправятся на сервер -->
                                            <td class="px-4 py-2 font-bold text-gray-800">
                                                <input type="hidden" :name="`students[${index}][last_name]`" :value="student.lastName">
                                                <span x-text="student.lastName"></span>
                                            </td>
                                            
                                            <td class="px-4 py-2 text-gray-800">
                                                <input type="hidden" :name="`students[${index}][first_name]`" :value="student.firstName">
                                                <span x-text="student.firstName"></span>
                                            </td>
                                            
                                            <td class="px-4 py-2 text-xs">
                                                <template x-if="student.isInvalid">
                                                    <span class="text-red-600 font-bold">⚠️ Ошибка: нет имени</span>
                                                </template>
                                                <template x-if="student.hasExtraWords">
                                                    <span class="text-yellow-600 font-bold" :title="student.original">⚠️ Отчество отброшено</span>
                                                </template>
                                                <template x-if="!student.isInvalid && !student.hasExtraWords">
                                                    <span class="text-green-600 font-bold">✓ Отлично</span>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        
                        <div x-show="students.length > 0" x-cloak class="mt-4 text-xs text-gray-500">
                            * Ученикам будет автоматически назначена роль <strong>advanced_student</strong>, сгенерирован случайный email, пароль и токен для входа по QR-коду. Если ученик с такими же ФИО уже есть в выбранной группе, он будет пропущен (защита от дублей).
                        </div>
                    </div>

                </form>

            </div>
        </div>
    </div>
</x-app-layout>