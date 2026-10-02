<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Журнал наград</h2>
    </x-slot>

    <script> MathJax = { tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] }, svg: { fontCache: 'global' } }; </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>
    <style> mjx-container svg { display: inline; } [x-cloak] { display: none !important; } </style>

    <!-- ГЛОБАЛЬНЫЙ КОНТЕЙНЕР ALPINE.JS -->
    <div class="py-6" x-data="{ 
            modalOpen: false, 
            modalStudentId: null, 
            modalStudentName: '', 
            currentDate: '{{ date('Y-m-d') }}',
            currentReason: '{{ $defaultReason }}', 
            currentReward: '{{ $defaultReward }}', 
            qrModalOpen: false,

            // Состояние для редактирования колонки
            editReasonModalOpen: false,
            editDate: '',
            editOldReason: '',
            editNewReason: '',

            openAwardModal(id, name, date = null, reason = null) {
                this.modalStudentId = id;
                this.modalStudentName = name;
                if (date) this.currentDate = date;
                if (reason !== null) this.currentReason = reason;
                this.modalOpen = true;
            },

            openEditReasonModal(date, oldReason) {
                this.editDate = date;
                this.editOldReason = oldReason;
                this.editNewReason = oldReason;
                this.editReasonModalOpen = true;
            }
        }">
        
        <div class="max-w-[1920px] mx-auto sm:px-6 lg:px-8">
            
            @if (session('success')) <div class="mb-4 p-4 bg-green-100 text-green-700 rounded shadow-sm font-bold">{{ session('success') }}</div> @endif
            @if (session('error')) <div class="mb-4 p-4 bg-red-100 text-red-700 rounded shadow-sm font-bold">{{ session('error') }}</div> @endif

            <!-- ФИЛЬТРЫ И КНОПКИ ГРУПП -->
            <form method="GET" action="{{ route('rewards.journal') }}" class="bg-white p-5 rounded-lg shadow-sm border mb-4 space-y-5">
                
                <!-- Блок кнопок выбора группы -->
                <div>
                    <label class="block font-bold text-sm text-gray-800 mb-3">Выберите группу:</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($groups as $group)
                            <label class="relative cursor-pointer">
                                <input type="radio" name="group_id" value="{{ $group->id }}" class="hidden" onchange="this.form.submit()" {{ $groupId == $group->id ? 'checked' : '' }}>
                                <div class="px-4 py-2 text-sm rounded-md transition-all shadow-sm border 
                                    {{ $groupId == $group->id 
                                        ? 'bg-indigo-600 border-indigo-700 text-white font-bold ring-2 ring-indigo-300 ring-offset-1' 
                                        : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50 hover:text-gray-900' 
                                    }}">
                                    {{ $group->grade ? $group->grade.' кл - ' : '' }}{{ $group->name }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Нижняя панель с датой и кнопками -->
                <div class="flex flex-wrap items-end gap-4 border-t border-gray-100 pt-4">
                    <div class="min-w-[200px]">
                        <label class="block font-medium text-sm text-gray-700 mb-1">Показывать начиная с даты:</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full border-gray-300 rounded shadow-sm py-1.5 focus:ring-blue-500" required onchange="this.form.submit()">
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="bg-gray-800 text-white rounded px-4 py-1.5 hover:bg-gray-700 shadow transition text-sm font-bold">Обновить</button>
                        
                        <!-- КНОПКА ВЫЗОВА МОДАЛКИ QR -->
                        <button type="button" @click="qrModalOpen = true" class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded px-4 py-1.5 hover:from-purple-600 hover:to-indigo-700 shadow font-bold flex items-center gap-1 transition text-sm">
                            🎁 Создать QR-награду
                        </button>
                    </div>
                </div>
            </form>

            @if($groupId)
                <!-- ТАБЛИЦА С ЗАКРЕПЛЕННОЙ КОЛОНКОЙ И ШАПКОЙ -->
                <div class="bg-white border rounded-lg shadow-sm overflow-x-auto overflow-y-auto max-h-[75vh] relative">
                    <table class="w-full text-sm text-left border-collapse min-w-max">
                        
                        <!-- ШАПКА -->
                        <!-- z-40 держит шапку поверх содержимого -->
                        <thead class="bg-gray-100 text-gray-700 sticky top-0 z-40 shadow-sm border-b">
                            <tr>
                                <!-- z-50 держит левый верхний угол поверх всего! -->
                                <th class="px-4 py-3 border-r sticky left-0 top-0 bg-gray-100 z-50 min-w-[250px] align-middle shadow-[1px_0_0_0_#e5e7eb]">
                                    Ученик
                                </th>
                                
                                @forelse($uniqueColumns as $col)
                                    <!-- Ширина увеличена до w-14 для двух строк -->
                                    <th class="px-2 py-3 border-r align-bottom w-14 hover:bg-gray-200 transition-colors group/th">
                                        <div class="flex items-end justify-center h-40 w-full pb-2">
                                            <!-- Два div'a внутри vertical-rl создают две параллельные линии текста -->
                                            <div class="[writing-mode:vertical-rl] rotate-180 text-left">
                                                
                                                <!-- Строка 1: Дата -->
                                                <div class="font-bold text-gray-900 whitespace-nowrap tracking-wider leading-tight">
                                                    {{ \Carbon\Carbon::parse($col['date'])->format('d.m.Y') }}
                                                </div>
                                                
                                                <!-- Строка 2: Причина и карандашик -->
                                                <div class="text-[10px] font-normal text-gray-500 whitespace-nowrap flex items-center gap-1 cursor-pointer hover:text-blue-600 leading-tight" 
                                                      @click="openEditReasonModal('{{ $col['date'] }}', '{{ addslashes($col['reason']) }}')" title="Изменить причину для колонки">
                                                    {{ $col['reason'] ?: 'Без описания' }}
                                                    <!-- Иконка карандаша (появляется при наведении) -->
                                                    <svg class="w-3 h-3 opacity-0 group-hover/th:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                </div>

                                            </div>
                                        </div>
                                    </th>
                                @empty
                                    <th class="px-3 py-3 text-center text-gray-400 font-normal align-middle h-40">За период наград нет</th>
                                @endforelse
                            </tr>
                        </thead>
                        
                        <!-- ТЕЛО ТАБЛИЦЫ -->
                        <tbody>
                            @foreach($students as $student)
                                <tr class="border-b hover:bg-blue-50 group">
                                    
                                    <!-- ЗАКРЕПЛЕННАЯ КОЛОНКА ИМЕНИ -->
                                    <td class="px-4 py-3 border-r sticky left-0 bg-white group-hover:bg-blue-50 z-30 shadow-[1px_0_0_0_#e5e7eb] flex justify-between items-center">
                                        <span class="font-bold text-gray-800">{{ $student->last_name }} {{ $student->first_name }}</span>
                                        <button type="button" @click="openAwardModal({{ $student->id }}, '{{ addslashes($student->last_name . ' ' . $student->first_name) }}')" 
                                                class="opacity-0 group-hover:opacity-100 text-blue-600 hover:bg-blue-200 bg-blue-100 rounded px-2 py-0.5 text-xs font-bold transition">
                                            + Награда
                                        </button>
                                    </td>

                                    <!-- ЯЧЕЙКИ МАТРИЦЫ (Кликабельные) -->
                                    @foreach($uniqueColumns as $col)
                                        @php $colKey = $col['date'] . '|' . ($col['reason'] ?? ''); @endphp
                                        <td class="px-1 py-1 border-r text-center align-top cursor-pointer hover:bg-blue-100 transition-colors min-w-[50px]"
                                            @click="openAwardModal({{ $student->id }}, '{{ addslashes($student->last_name . ' ' . $student->first_name) }}', '{{ $col['date'] }}', '{{ addslashes($col['reason']) }}')">
                                            
                                            <div class="flex flex-col gap-1 items-center justify-center min-h-[40px]">
                                                @if(isset($rewardsMatrix[$student->id][$colKey]))
                                                    @foreach($rewardsMatrix[$student->id][$colKey] as $sr)
                                                        <!-- Важно: @click.stop предотвращает всплытие клика (модалка не откроется) -->
                                                        <div x-data="{ 
                                                                accounted: {{ $sr->is_accounted ? 'true' : 'false' }}, 
                                                                loading: false, deleted: false,
                                                                async toggle() {
                                                                    if(this.loading || this.deleted) return;
                                                                    this.loading = true;
                                                                    try {
                                                                        let res = await fetch('{{ route('rewards.toggleAccounted', $sr->id) }}', {
                                                                            method: 'PATCH',
                                                                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                                                        });
                                                                        let data = await res.json();
                                                                        if(data.success) this.accounted = data.is_accounted;
                                                                    } catch (e) { alert('Ошибка соединения'); }
                                                                    this.loading = false;
                                                                },
                                                                async removeReward() {
                                                                    if(!confirm('Точно удалить эту награду у ученика?')) return;
                                                                    this.loading = true;
                                                                    try {
                                                                        let res = await fetch('{{ route('rewards.journal.destroy', $sr->id) }}', {
                                                                            method: 'DELETE',
                                                                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                                                        });
                                                                        if(res.ok) { this.deleted = true; } else { alert('Ошибка при удалении'); }
                                                                    } catch (e) { alert('Ошибка соединения'); }
                                                                    this.loading = false;
                                                                }
                                                            }" 
                                                            x-show="!deleted" x-transition.opacity @click.stop="toggle()"
                                                            class="relative cursor-pointer transition-all duration-200 border rounded shadow-sm p-1 flex flex-col items-center justify-center w-full max-w-[45px] group/reward"
                                                            :class="accounted ? 'bg-gray-50 border-gray-200 opacity-60' : 'bg-white border-blue-300 ring-2 ring-blue-100 hover:scale-110'"
                                                            title="{{ $sr->reward->name }} (Выдал: {{ $sr->teacher->last_name }})">
                                                            
                                                            <div x-show="loading" class="absolute inset-0 bg-white/50 rounded flex items-center justify-center z-30"><svg class="animate-spin h-3 w-3 text-blue-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div>
                                                            <div x-show="!accounted" class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full shadow border border-white z-10"></div>
                                                            
                                                            @if($sr->teacher_id === auth()->id() || auth()->user()->hasRole('admin'))
                                                                <button type="button" @click.stop="removeReward()" class="absolute -top-1.5 -left-1.5 m-0 opacity-0 group-hover/reward:opacity-100 transition z-20 bg-gray-800 hover:bg-red-600 text-white rounded-full w-4 h-4 flex items-center justify-center text-[9px] shadow border border-white">&times;</button>
                                                            @endif

                                                            @if($sr->reward->svg_content)
                                                                <div class="h-6 w-6 [&>svg]:w-full [&>svg]:h-full [&>svg]:object-contain">{!! $sr->reward->svg_content !!}</div>
                                                            @elseif($sr->reward->symbol_latex)
                                                                <span class="font-bold text-[10px] text-indigo-700">${!! $sr->reward->symbol_latex !!}$</span>
                                                            @else
                                                                <span class="text-[9px] font-bold">{{ $sr->reward->key }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- МОДАЛКА 1: РУЧНОЕ НАГРАЖДЕНИЕ -->
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div @click.away="modalOpen = false" class="bg-white rounded-lg shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                    <h3 class="font-bold text-lg text-gray-800">Наградить ученика</h3>
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
                </div>
                <form action="{{ route('rewards.storeManual') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="student_id" x-model="modalStudentId">
                    <input type="hidden" name="reward_id" x-model="currentReward" required>

                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">Ученик: <strong class="text-indigo-700 text-lg" x-text="modalStudentName"></strong></div>
                        <input type="date" name="date" x-model="currentDate" class="border-gray-300 rounded shadow-sm focus:ring-blue-500 text-sm font-bold" required>
                    </div>
                    
                    <div>
                        <label class="block font-bold text-sm text-gray-700 mb-1">За что выдается</label>
                        <input type="text" name="reason" x-model="currentReason" list="reasons-list" maxlength="150" class="w-full border-gray-300 rounded shadow-sm focus:ring-blue-500 text-sm" placeholder="Например: За устный ответ">
                        <datalist id="reasons-list">
                            @foreach($teacherReasons as $reason) <option value="{{ $reason }}"> @endforeach
                        </datalist>
                    </div>
                    
                    <div>
                        <label class="block font-bold text-sm text-gray-700 mb-2">Выберите награду <span x-show="!currentReward" class="text-red-500 text-xs font-normal ml-2">(необходимо выбрать)</span></label>
                        
                        <!-- ГАЛЕРЕЯ НАГРАД ВМЕСТО ВЫПАДАЮЩЕГО СПИСКА -->
                        <div class="flex overflow-x-auto p-2 gap-3 no-scrollbar items-center bg-gray-50 border rounded-lg shadow-inner border-gray-200">
                            @foreach($availableRewards as $r)
                                <button 
                                    type="button"
                                    @click="currentReward = {{ $r->id }}"
                                    :class="currentReward == {{ $r->id }} ? 'ring-2 ring-indigo-500 bg-white scale-105 shadow-md' : 'opacity-60 bg-transparent hover:opacity-100 hover:bg-white'"
                                    class="flex-shrink-0 flex flex-col items-center justify-center w-16 h-16 border border-gray-200 rounded-xl transition-all"
                                >
                                    <div class="text-lg text-indigo-700 font-black flex items-center justify-center h-8 w-full">
                                        @if($r->svg_content)
                                            <div class="h-6 w-6 [&>svg]:w-full [&>svg]:h-full">{!! $r->svg_content !!}</div>
                                        @elseif($r->symbol_latex)
                                            <span>${!! $r->symbol_latex !!}$</span>
                                        @else
                                            {{ $r->key }}
                                        @endif
                                    </div>
                                    <span class="text-[9px] font-bold text-gray-600 mt-1 leading-tight text-center px-1">{{ $r->name }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3 pt-4 border-t mt-6">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition">Отмена</button>
                        <button type="submit" :disabled="!currentReward" class="px-4 py-2 bg-blue-600 text-white font-bold rounded hover:bg-blue-700 shadow disabled:bg-gray-400 disabled:cursor-not-allowed transition">Выдать награду</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- МОДАЛКА 2: МАССОВОЕ ИЗМЕНЕНИЕ ПРИЧИНЫ -->
        <div x-show="editReasonModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div @click.away="editReasonModalOpen = false" class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                    <h3 class="font-bold text-lg text-gray-800">Изменить основание в колонке</h3>
                    <button @click="editReasonModalOpen = false" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
                </div>
                <form action="{{ route('rewards.bulkReason') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    @method('PATCH')
                    <!-- Скрытые поля -->
                    <input type="hidden" name="group_id" value="{{ $groupId }}">
                    <input type="hidden" name="date" x-model="editDate">
                    <input type="hidden" name="old_reason" x-model="editOldReason">
                    
                    <p class="text-sm text-gray-600">Это переименует основание у <strong class="text-gray-900">всех</strong> наград в этой колонке за <span x-text="editDate.split('-').reverse().join('.')"></span>.</p>
                    
                    <div>
                        <label class="block font-bold text-sm text-gray-700 mb-1">Новое основание</label>
                        <input type="text" name="new_reason" x-model="editNewReason" list="reasons-list" maxlength="150" class="w-full border-gray-300 rounded shadow-sm focus:ring-blue-500 text-sm" placeholder="Введите новую причину">
                    </div>
                    
                    <div class="flex justify-end gap-3 pt-4 border-t mt-6">
                        <button type="button" @click="editReasonModalOpen = false" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Отмена</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-bold rounded hover:bg-blue-700 shadow">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- МОДАЛКА 3: ГЕНЕРАЦИЯ QR -->
        <div x-show="qrModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div @click.away="qrModalOpen = false" class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b bg-indigo-50 flex justify-between items-center">
                    <h3 class="font-bold text-lg text-indigo-900">🎁 Выпустить QR-награду</h3>
                    <button @click="qrModalOpen = false" class="text-indigo-400 hover:text-indigo-600 font-bold text-xl">&times;</button>
                </div>
                <form action="{{ route('rewards.generateQr') }}" method="POST" target="_blank" class="p-6 space-y-4">
                    @csrf
                    <p class="text-sm text-gray-600 mb-4">Будет сгенерирована безымянная награда и открыта страница А4 для печати. Ученик получит её, когда просканирует код.</p>
                    <div>
                        <label class="block font-bold text-sm text-gray-700 mb-1">За что выдается</label>
                        <input type="text" name="reason" x-model="currentReason" list="reasons-list" maxlength="150" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block font-bold text-sm text-gray-700 mb-1">Выберите награду</label>
                        <select name="reward_id" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500" required>
                            <option value="">-- Выберите из списка --</option>
                            @foreach($availableRewards as $r) <option value="{{ $r->id }}">Z:{{ $r->z_number }} - {{ $r->name }}</option> @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t mt-6">
                        <button type="button" @click="qrModalOpen = false" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Отмена</button>
                        <button type="submit" @click="setTimeout(() => qrModalOpen = false, 500)" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded hover:bg-indigo-700 shadow">Печатать</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>