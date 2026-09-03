<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Общий реестр выданных наград</h2>
    </x-slot>

    <script> MathJax = { tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] } }; </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>
    <style> mjx-container svg { display: inline; } </style>

    <div class="py-8">
        <div class="max-w-[1920px] mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success')) <div class="p-4 bg-green-100 text-green-700 rounded shadow-sm font-bold">{{ session('success') }}</div> @endif
            @if (session('error')) <div class="p-4 bg-red-100 text-red-700 rounded shadow-sm font-bold">{{ session('error') }}</div> @endif

            <!-- ФИЛЬТРЫ И СОРТИРОВКА -->
            <form method="GET" action="{{ route('rewards.issued') }}" class="bg-white p-4 rounded-lg shadow-sm border flex flex-wrap gap-4 items-end text-sm">
                
                <div class="flex-1 min-w-[150px]">
                    <label class="block font-medium text-gray-700 mb-1">Поиск (Награда / За что)</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Текст..." class="w-full border-gray-300 rounded shadow-sm py-1.5 focus:ring-blue-500">
                </div>

                <div class="flex-1 min-w-[150px]">
                    <label class="block font-medium text-gray-700 mb-1">Ученик</label>
                    <select name="student_id" class="w-full border-gray-300 rounded shadow-sm py-1.5 focus:ring-blue-500">
                        <option value="">-- Все ученики --</option>
                        @foreach($students as $st)
                            <option value="{{ $st->id }}" {{ request('student_id') == $st->id ? 'selected' : '' }}>{{ $st->last_name }} {{ Str::substr($st->first_name, 0, 1) }}.</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 min-w-[150px]">
                    <label class="block font-medium text-gray-700 mb-1">Выдал (Учитель)</label>
                    <select name="teacher_id" class="w-full border-gray-300 rounded shadow-sm py-1.5 focus:ring-blue-500">
                        <option value="">-- Все учителя --</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->last_name }} {{ Str::substr($t->first_name, 0, 1) }}.</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 min-w-[150px]">
                    <label class="block font-medium text-gray-700 mb-1">Награда</label>
                    <select name="reward_id" class="w-full border-gray-300 rounded shadow-sm py-1.5 focus:ring-blue-500">
                        <option value="">-- Все награды --</option>
                        @foreach($rewardsList as $r)
                            <option value="{{ $r->id }}" {{ request('reward_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 min-w-[120px]">
                    <label class="block font-medium text-gray-700 mb-1">Сортировка</label>
                    <select name="sort_by" class="w-full border-gray-300 rounded shadow-sm py-1.5 focus:ring-blue-500">
                        <option value="created_at" {{ $sortField == 'created_at' ? 'selected' : '' }}>По дате</option>
                        <option value="reward_name" {{ $sortField == 'reward_name' ? 'selected' : '' }}>По названию</option>
                        <option value="id" {{ $sortField == 'id' ? 'selected' : '' }}>По ID</option>
                    </select>
                </div>

                <div class="flex-1 min-w-[100px]">
                    <label class="block font-medium text-gray-700 mb-1">Порядок</label>
                    <select name="sort_dir" class="w-full border-gray-300 rounded shadow-sm py-1.5 focus:ring-blue-500">
                        <option value="desc" {{ $sortDir == 'desc' ? 'selected' : '' }}>Убыв.</option>
                        <option value="asc" {{ $sortDir == 'asc' ? 'selected' : '' }}>Возр.</option>
                    </select>
                </div>

                <div class="flex gap-2 pb-0.5">
                    <button type="submit" class="bg-gray-800 text-white rounded px-4 py-1.5 hover:bg-gray-700 shadow transition-colors">Применить</button>
                    <a href="{{ route('rewards.issued') }}" class="bg-gray-200 text-gray-700 rounded px-4 py-1.5 hover:bg-gray-300 shadow transition-colors">Сброс</a>
                </div>
            </form>

            <!-- ТАБЛИЦА -->
            <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-center">ID</th>
                                <th class="px-4 py-3">Дата выдачи</th>
                                <th class="px-4 py-3 w-1/4">Награда</th>
                                <th class="px-4 py-3">За что (Основание)</th>
                                <th class="px-4 py-3">Кому (Ученик)</th>
                                <th class="px-4 py-3">Выдал (Учитель)</th>
                                <th class="px-4 py-3 text-center" title="Перенесена в журнал">Учтено</th>
                                <th class="px-4 py-3 text-center" title="Вручен ли ученику магнитик, брелок и т.п.">Вручен носитель</th>
                                <th class="px-4 py-3 text-right">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($issuedRewards as $sr)
                                @php
                                    $canDelete = $sr->teacher_id === auth()->id() || auth()->user()->hasRole('admin');
                                @endphp
                                <tr class="bg-white border-b hover:bg-indigo-50 transition-colors">
                                    <td class="px-4 py-3 text-center text-gray-400 text-xs font-mono">#{{ $sr->id }}</td>
                                    
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $sr->created_at->format('d.m.Y H:i') }}
                                    </td>
                                    
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 flex-shrink-0 flex items-center justify-center bg-gray-100 rounded border">
                                                @if($sr->reward->svg_content)
                                                    <div class="w-6 h-6 [&>svg]:w-full [&>svg]:h-full">{!! $sr->reward->svg_content !!}</div>
                                                @elseif($sr->reward->symbol_latex)
                                                    <span class="text-xs font-bold text-indigo-700">${!! $sr->reward->symbol_latex !!}$</span>
                                                @endif
                                            </div>
                                            <span class="font-bold text-indigo-700">{{ $sr->reward->name }}</span>
                                        </div>
                                    </td>
                                    
                                    <td class="px-4 py-3 text-xs">{{ $sr->reason ?? '—' }}</td>
                                    
                                    <td class="px-4 py-3">
                                        @if($sr->student)
                                            <span class="font-bold text-gray-900">{{ $sr->student->last_name }} {{ Str::substr($sr->student->first_name, 0, 1) }}.</span>
                                            <div class="text-[10px] text-gray-500">{{ $sr->student->group->name ?? '' }}</div>
                                        @else
                                            <span class="inline-block bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded text-xs font-bold border border-yellow-200">
                                                Не привязан (по QR)
                                            </span>
                                        @endif
                                    </td>
                                    
                                    <td class="px-4 py-3 text-xs italic">{{ $sr->teacher->last_name ?? '—' }}</td>
                                    
                                    <!-- ЖИВАЯ КНОПКА "УЧТЕНО" -->
                                    <td class="px-4 py-3 text-center" x-data="{ 
                                            accounted: {{ $sr->is_accounted ? 'true' : 'false' }}, 
                                            async toggle() {
                                                try {
                                                    let res = await fetch('{{ route('rewards.toggleAccounted', $sr->id) }}', {
                                                        method: 'PATCH',
                                                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                                    });
                                                    let data = await res.json();
                                                    if(data.success) this.accounted = data.is_accounted;
                                                } catch (e) { alert('Ошибка'); }
                                            }
                                        }">
                                        <button @click="toggle()" :class="accounted ? 'text-green-600 bg-green-100 hover:bg-green-200' : 'text-gray-400 bg-gray-100 hover:bg-gray-200'" class="px-2 py-1 rounded text-xs font-bold transition">
                                            <span x-text="accounted ? '✓ Да' : '—'"></span>
                                        </button>
                                    </td>
                                    
                                    <!-- ФИЗИЧЕСКИЙ НОСИТЕЛЬ (Живая кнопка) -->
                                    <td class="px-4 py-3 text-center">
                                        @if($sr->reward->carrier_type)
                                            <div class="flex flex-col items-center gap-1"
                                                x-data="{ 
                                                    handedOver: {{ $sr->is_handed_over ? 'true' : 'false' }}, 
                                                    async toggle() {
                                                        try {
                                                            let res = await fetch('{{ route('rewards.toggleHandedOver', $sr->id) }}', {
                                                                method: 'PATCH',
                                                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                                            });
                                                            let data = await res.json();
                                                            if(data.success) this.handedOver = data.is_handed_over;
                                                        } catch (e) { alert('Ошибка'); }
                                                    }
                                                }">
                                                
                                                <!-- Название носителя (Магнит, брелок) -->
                                                <span class="text-[10px] text-gray-500 uppercase tracking-widest font-bold">{{ $sr->reward->carrier_type }}</span>
                                                
                                                <!-- Кнопка статуса вручения -->
                                                <button @click="toggle()" :class="handedOver ? 'text-green-600 bg-green-100 hover:bg-green-200' : 'text-yellow-600 bg-yellow-100 hover:bg-yellow-200'" class="px-2 py-1 rounded text-xs font-bold transition w-full max-w-[80px]">
                                                    <span x-text="handedOver ? '✓ Вручен' : 'Ожидает'"></span>
                                                </button>
                                            </div>
                                        @else
                                            <div class="text-gray-300 text-xs" title="У этой награды нет физического носителя">Виртуальная</div>
                                        @endif
                                    </td>
                                    
                                    <td class="px-4 py-3 text-right space-x-2">
                                        @if($sr->claim_hash && !$sr->student_id)
                                            <a href="{{ route('rewards.printQr', $sr->id) }}" target="_blank" class="text-indigo-600 font-bold hover:underline text-xs" title="Распечатать заново">🖨 QR</a>
                                        @endif

                                        @if($canDelete)
                                            <form action="{{ route('rewards.journal.destroy', $sr->id) }}" method="POST" class="inline" onsubmit="return confirm('Удалить эту выдачу из базы?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:underline text-xs font-bold">Удалить</button>
                                            </form>
                                        @else
                                            <span class="text-gray-300 text-xs cursor-not-allowed">Удалить</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-6 py-8 text-center text-gray-500">Награды по выбранным фильтрам не найдены.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t bg-gray-50">{{ $issuedRewards->links() }}</div>
            </div>

        </div>
    </div>
</x-app-layout>