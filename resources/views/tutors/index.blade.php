<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $isAdmin ? 'Управление индивидуальной работой (Все учителя)' : 'Мои индивидуальные ученики' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success')) <div class="p-4 bg-green-100 text-green-700 rounded-lg shadow-sm font-bold">{{ session('success') }}</div> @endif
            @if (session('error')) <div class="p-4 bg-red-100 text-red-700 rounded-lg shadow-sm font-bold">{{ session('error') }}</div> @endif

            <!-- Сетка карточек преподавателей -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                @foreach ($teachers as $teacher)
                    <!-- ИСПРАВЛЕНИЕ 1: Убрали overflow-hidden -->
                    <div class="bg-white border rounded-lg shadow-sm flex flex-col">
                        
                        <!-- ИСПРАВЛЕНИЕ 2: Добавили rounded-t-lg (закругление шапки) -->
                        <div class="bg-indigo-50 border-b px-4 py-3 flex justify-between items-center rounded-t-lg">
                            <h3 class="font-bold text-indigo-900">
                                {{ $teacher->last_name }} {{ $teacher->first_name }}
                            </h3>
                            <span class="bg-indigo-200 text-indigo-800 text-xs px-2 py-1 rounded-full font-bold">
                                Учеников: {{ $teacher->tutoredStudents->count() }}
                            </span>
                        </div>

                        <div class="flex-1 p-4 bg-gray-50 overflow-y-auto max-h-96 space-y-2">
                            @forelse ($teacher->tutoredStudents as $student)
                                <div class="bg-white border rounded p-2 flex justify-between items-center shadow-sm hover:border-indigo-300 transition group">
                                    <div>
                                        <div class="font-bold text-sm text-gray-800">{{ $student->last_name }} {{ $student->first_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $student->group->name ?? 'Без группы' }}</div>
                                    </div>
                                    
                                    <form action="{{ route('tutors.destroy') }}" method="POST" class="m-0" onsubmit="return confirm('Удалить ученика из списка подопечных?')">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <button type="submit" class="text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-full w-8 h-8 flex items-center justify-center transition" title="Удалить">&times;</button>
                                    </form>
                                </div>
                            @empty
                                <div class="text-center text-gray-400 text-sm italic py-4">Нет прикрепленных учеников.</div>
                            @endforelse
                        </div>

                        <!-- ИСПРАВЛЕНИЕ 3: Добавили rounded-b-lg (закругление подвала) -->
                        <div class="p-4 border-t bg-white rounded-b-lg" x-data="studentAutocomplete({{ $teacher->id }})">
                            <form action="{{ route('tutors.store') }}" method="POST" class="flex flex-col gap-2 relative">
                                @csrf
                                <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
                                <input type="hidden" name="student_id" x-model="selectedStudentId" required>

                                <label class="text-xs font-bold text-gray-600 uppercase">Добавить ученика</label>
                                
                                <!-- ИСПРАВЛЕНИЕ 4: Перенесли @click.away на обертку -->
                                <div class="relative" @click.away="isOpen = false">
                                    <input 
                                        type="text" 
                                        x-model="search" 
                                        @focus="isOpen = true" 
                                        @input="selectedStudentId = null; isOpen = true"
                                        placeholder="Начните вводить фамилию..." 
                                        class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                    >
                                    
                                    <ul x-show="isOpen && search.length > 0" x-cloak class="absolute z-50 w-full bg-white border rounded shadow-xl max-h-48 overflow-y-auto mt-1">
                                        <template x-for="student in filteredStudents" :key="student.id">
                                            <!-- ИСПРАВЛЕНИЕ 5: Используем mousedown.prevent для защиты от потери фокуса -->
                                            <li @mousedown.prevent="selectStudent(student)" class="px-3 py-2 text-sm hover:bg-indigo-50 cursor-pointer border-b last:border-b-0">
                                                <span class="font-bold" x-text="student.name"></span>
                                                <span class="text-xs text-gray-500 ml-1" x-text="student.group"></span>
                                            </li>
                                        </template>
                                        <li x-show="filteredStudents.length === 0" class="px-3 py-2 text-sm text-gray-500 italic">
                                            Не найдено
                                        </li>
                                    </ul>
                                </div>

                                <button type="submit" :disabled="!selectedStudentId" class="mt-1 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 text-white font-bold py-2 px-4 rounded text-sm transition">
                                    Прикрепить
                                </button>
                            </form>
                        </div>

                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Скрипт для живого поиска на Alpine.js -->
    <script>
        // Подготавливаем общий список всех учеников из контроллера
        const allStudentsData = @json($allStudents->map(function($s) {
            return [
                'id' => $s->id, 
                'name' => $s->last_name . ' ' . $s->first_name,
                'group' => $s->group->name ?? 'Без группы'
            ];
        }));

        function studentAutocomplete(teacherId) {
            return {
                isOpen: false,
                search: '',
                selectedStudentId: null,
                students: allStudentsData,
                
                get filteredStudents() {
                    if (this.search === '') return [];
                    const searchLower = this.search.toLowerCase();
                    return this.students.filter(s => s.name.toLowerCase().includes(searchLower));
                },
                
                selectStudent(student) {
                    this.search = student.name;
                    this.selectedStudentId = student.id;
                    this.isOpen = false;
                }
            }
        }
    </script>
</x-app-layout>