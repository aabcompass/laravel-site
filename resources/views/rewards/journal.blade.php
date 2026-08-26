<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Журнал наград</h2>
    </x-slot>

    <!-- Подключаем MathJax -->
    <script> MathJax = { tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] }, svg: { fontCache: 'global' } }; </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>
    <style> mjx-container svg { display: inline; } [x-cloak] { display: none !important; } </style>

    <div class="py-6" x-data="{ modalOpen: false, modalStudentId: null, modalStudentName: '' }">
        <div class="max-w-[1920px] mx-auto sm:px-6 lg:px-8">
            
            @if (session('success')) <div class="mb-4 p-4 bg-green-100 text-green-700 rounded shadow-sm font-bold">{{ session('success') }}</div> @endif

            <!-- ФИЛЬТРЫ -->
            <form method="GET" action="{{ route('rewards.journal') }}" class="bg-white p-4 rounded-lg shadow-sm border mb-4 flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block font-medium text-sm text-gray-700 mb-1">Группа</label>
                    <select name="group_id" class="w-full border-gray-300 rounded shadow-sm py-1.5 focus:ring-blue-500" required onchange="this.form.submit()">
                        <option value="">-- Выберите группу --</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ $groupId == $group->id ? 'selected' : '' }}>
                                {{ $group->grade ? $group->grade.' кл - ' : '' }}{{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 min-w-[200px]">
                    <label class="block font-medium text-sm text-gray-700 mb-1">Показывать начиная с даты:</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full border-gray-300 rounded shadow-sm py-1.5 focus:ring-blue-500" required onchange="this.form.submit()">
                </div>
                
                <div>
                    <button type="submit" class="bg-gray-800 text-white rounded px-4 py-1.5 hover:bg-gray-700 shadow">Применить</button>
                </div>
            </form>

            @if($groupId)
                <div class="mb-4 flex justify-end">
                    <a href="{{ route('remote.gateway') }}" target="_blank" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition transform hover:scale-105 flex items-center gap-2">
                        <span class="text-xl">📱</span> Открыть пульт для этой группы
                    </a>
                </div>
            @endif

            @if($groupId)
                <div class="bg-white border rounded-lg shadow-sm overflow-x-auto max-h-[75vh] relative">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead class="bg-gray-100 text-gray-700 sticky top-0 z-20 shadow-sm border-b">
                            <tr>
                                <th class="px-4 py-3 border-r sticky left-0 bg-gray-100 z-30 min-w-[250px]">Ученик</th>
                                @forelse($uniqueDates as $date)
                                    <th class="px-3 py-3 border-r text-center min-w-[120px]">
                                        {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}
                                    </th>
                                @empty
                                    <th class="px-3 py-3 text-center text-gray-400 font-normal">За выбранный период наград нет</th>
                                @endforelse
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                <tr class="border-b hover:bg-blue-50 group">
                                    
                                    <!-- Ячейка ученика (С кнопкой плюсика) -->
                                    <td class="px-4 py-3 border-r sticky left-0 bg-white group-hover:bg-blue-50 z-10 shadow-[1px_0_0_0_#e5e7eb] flex justify-between items-center">
                                        <span class="font-bold text-gray-800">{{ $student->last_name }} {{ $student->first_name }}</span>
                                        <button type="button" @click="modalStudentId = {{ $student->id }}; modalStudentName = '{{ $student->last_name }} {{ $student->first_name }}'; modalOpen = true" 
                                                class="opacity-0 group-hover:opacity-100 text-blue-600 hover:bg-blue-200 bg-blue-100 rounded px-2 py-0.5 text-xs font-bold transition">
                                            + Наградить
                                        </button>
                                    </td>

                                    <!-- Колонки дат -->
                                    @foreach($uniqueDates as $date)
                                        <td class="px-2 py-2 border-r text-center align-top">
                                            @if(isset($rewardsMatrix[$student->id][$date]))
                                                <div class="flex flex-wrap gap-2 justify-center">
                                                    @foreach($rewardsMatrix[$student->id][$date] as $sr)
                                                        <!-- Ячейка награды на Alpine.js -->
                                                        <!-- Ячейка награды на Alpine.js -->
                                                        <!-- Добавили group/reward для отслеживания наведения мыши -->
                                                        <div x-data="{ 
                                                                accounted: {{ $sr->is_accounted ? 'true' : 'false' }}, 
                                                                loading: false,
                                                                async toggle() {
                                                                    if(this.loading) return;
                                                                    this.loading = true;
                                                                    try {
                                                                        let res = await fetch('{{ route('rewards.toggleAccounted', $sr->id) }}', {
                                                                            method: 'PATCH',
                                                                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                                                        });
                                                                        let data = await res.json();
                                                                        if(data.success) this.accounted = data.is_accounted;
                                                                    } catch (e) { alert('Ошибка'); }
                                                                    this.loading = false;
                                                                }
                                                            }" 
                                                            @click="toggle()"
                                                            class="relative cursor-pointer transition-all duration-200 border rounded shadow-sm p-1.5 flex flex-col items-center justify-center min-w-[50px] group/reward"
                                                            :class="accounted ? 'bg-gray-50 border-gray-200 opacity-60' : 'bg-white border-blue-300 ring-2 ring-blue-100 hover:scale-110'"
                                                            title="{{ $sr->reward->name }} (Выдал: {{ $sr->teacher->last_name }})">
                                                            
                                                            <!-- Красная точка, если НЕ учтено -->
                                                            <div x-show="!accounted" class="absolute -top-1.5 -right-1.5 w-3 h-3 bg-red-500 rounded-full shadow border-2 border-white z-10"></div>
                                                            
                                                            <!-- КРЕСТИК УДАЛЕНИЯ -->
                                                            @if($sr->teacher_id === auth()->id() || auth()->user()->hasRole('admin'))
                                                                <form action="{{ route('rewards.journal.destroy', $sr->id) }}" method="POST" class="absolute -top-2 -left-2 m-0 opacity-0 group-hover/reward:opacity-100 transition z-20">
                                                                    @csrf @method('DELETE')
                                                                    <!-- @click.stop предотвращает срабатывание функции toggle() при клике на крестик -->
                                                                    <button type="button" @click.stop="if(confirm('Точно удалить эту награду у ученика?')) $el.closest('form').submit()" class="bg-gray-800 hover:bg-red-600 text-white rounded-full w-4 h-4 flex items-center justify-center text-[10px] shadow border border-white">&times;</button>
                                                                </form>
                                                            @endif

                                                            @if($sr->reward->svg_content)
                                                                <div class="h-8 w-8 [&>svg]:w-full [&>svg]:h-full [&>svg]:object-contain">{!! $sr->reward->svg_content !!}</div>
                                                            @elseif($sr->reward->symbol_latex)
                                                                <span class="font-bold text-sm text-indigo-700">${!! $sr->reward->symbol_latex !!}$</span>
                                                            @else
                                                                <span class="text-xs font-bold">{{ $sr->reward->key }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- МОДАЛЬНОЕ ОКНО "РУЧНОЕ НАГРАЖДЕНИЕ" -->
        <div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div @click.away="modalOpen = false" class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                    <h3 class="font-bold text-lg text-gray-800">Наградить ученика</h3>
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
                </div>
                
                <form action="{{ route('rewards.storeManual') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="student_id" x-model="modalStudentId">
                    
                    <div class="text-sm text-gray-600">
                        Ученик: <strong class="text-gray-900" x-text="modalStudentName"></strong>
                    </div>

                    <div>
                        <label class="block font-bold text-sm text-gray-700 mb-1">Дата выдачи</label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}" class="w-full border-gray-300 rounded shadow-sm focus:ring-blue-500" required>
                    </div>

                    <div>
                        <label class="block font-bold text-sm text-gray-700 mb-1">Выберите награду</label>
                        <select name="reward_id" class="w-full border-gray-300 rounded shadow-sm focus:ring-blue-500" required>
                            <option value="">-- Выберите из списка --</option>
                            @foreach($availableRewards as $r)
                                <option value="{{ $r->id }}">Z:{{ $r->z_number }} - {{ $r->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Здесь показаны только те награды, которые требуют обязательной регистрации в базе.</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t mt-6">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Отмена</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-bold rounded hover:bg-blue-700 shadow">Выдать награду</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>