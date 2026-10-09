@php
    // Проверяем прямо в БД: кому из этой группы СЕГОДНЯ выдавали награду с галочкой "is_absent"
    $todayAbsents = \App\Models\StudentReward::whereIn('student_id', $students->pluck('id'))
        ->whereDate('created_at', \Carbon\Carbon::today())
        ->whereHas('reward', function($q) {
            $q->where('is_absent', true);
        })
        ->pluck('student_id')
        ->unique()
        ->values()
        ->toArray();
@endphp
<!DOCTYPE html>
<html lang="ru" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="google" content="notranslate">
    <title>Пульт: {{ $group->name }}</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <script> MathJax = { tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] } }; </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>
    
    <style>
        body { background-color: #f3f4f6; -webkit-tap-highlight-color: transparent; }
        mjx-container svg { display: inline; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        [x-cloak] { display: none !important; }
        .name-clamp { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
</head>
<body x-data="remoteApp({{ $group->id }}, {{ json_encode($todayAbsents) }})" class="h-screen flex flex-col overflow-hidden text-gray-800 relative">

    <div x-show="toast.show" x-transition.opacity x-cloak class="fixed top-4 left-4 right-4 z-50 flex items-center justify-between bg-green-600 text-white px-4 py-3 rounded-lg shadow-xl border border-green-500">
        <div class="font-bold text-sm" x-html="toast.message"></div>
        <button @click="undoReward()" class="bg-white text-green-700 font-black px-3 py-1.5 rounded text-sm shadow active:scale-95 transition">ОТМЕНИТЬ</button>
    </div>

    <!-- ШАПКА -->
    <header class="bg-indigo-600 text-white p-3 shadow-md z-10 flex-shrink-0 flex items-center gap-3">
        <select @change="changeGroup($event.target.value)" class="flex-1 bg-indigo-700 border-none text-white text-lg font-bold rounded p-2 focus:ring-0 cursor-pointer">
            @foreach($allGroups as $g)
                <option value="{{ $g->id }}" {{ $g->id == $group->id ? 'selected' : '' }}>
                    {{ $g->grade ? $g->grade.' кл - ' : '' }}{{ $g->name }}
                </option>
            @endforeach
        </select>
    </header>

    <!-- КНОПКА РУЛЕТКИ -->
    <div class="p-3 flex-shrink-0 z-10 bg-gray-100 shadow-sm border-b">
        <button @click="spinRoulette()" :disabled="rouletteRunning" class="w-full bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-black text-lg py-3 rounded-lg shadow active:scale-95 transition disabled:opacity-80 flex justify-center items-center gap-2">
            <span x-show="!rouletteRunning">🎲 Выбрать случайно</span>
            <span x-show="rouletteRunning" x-cloak class="animate-pulse">⏳ Выбираем...</span>
        </button>
    </div>

    <!-- СПИСОК УЧЕНИКОВ -->
    <main class="flex-1 overflow-y-auto p-2 pb-40">
        @if($students->isEmpty())
            <div class="text-center text-gray-500 mt-10 font-bold">В этой группе нет учеников.</div>
        @else
            <div class="grid grid-cols-3 gap-2">
                <template x-for="student in students" :key="student.id">
                    <button 
                        @click="selectStudent(student.id)"
                        :class="{
                            'bg-indigo-600 text-white shadow-[0_0_15px_rgba(79,70,229,0.5)] border-indigo-700 scale-105 z-10 ring-2 ring-indigo-400': selectedStudentId === student.id,
                            'bg-gray-100 text-gray-400 opacity-50': absentIds.includes(student.id),
                            'bg-white text-gray-700 border-gray-200 shadow-sm hover:bg-gray-50': !absentIds.includes(student.id) && selectedStudentId !== student.id,
                            'ring-2 ring-indigo-200': hasAnswered(student.id) && !absentIds.includes(student.id) && selectedStudentId !== student.id
                        }"
                        class="p-2 rounded-lg border flex flex-col items-center justify-center text-center h-16 transition-all duration-200 relative"
                    >
                        <!-- Индикатор "уже отвечал" -->
                        <div x-show="hasAnswered(student.id) && !absentIds.includes(student.id) && selectedStudentId !== student.id" class="absolute top-1 right-1 w-1.5 h-1.5 bg-indigo-300 rounded-full"></div>
                        
                        <!-- Фамилия (Зачеркивается, если отсутствует) -->
                        <span x-text="student.last_name" :class="absentIds.includes(student.id) ? 'line-through' : ''" class="font-bold text-[13px] leading-tight name-clamp w-full"></span>
                        <span x-text="student.first_name" class="text-[11px] opacity-80 font-normal truncate w-full mt-0.5"></span>
                    </button>
                </template>
            </div>
        @endif
    </main>

    <!-- ПАНЕЛЬ НАГРАД (ЦВЕТНЫЕ КНОПКИ) -->
    <footer class="fixed bottom-0 w-full bg-white border-t border-gray-200 shadow-[0_-10px_20px_rgba(0,0,0,0.1)] z-20 pb-safe">
        
        <div class="bg-gray-50 border-b flex items-center justify-between min-h-[40px] px-3">
            <div x-show="!selectedStudentId" class="text-[11px] uppercase tracking-wider text-gray-400 font-black w-full text-center py-2">
                Сначала выберите ученика ↑
            </div>
            <div x-show="selectedStudentId" x-cloak class="flex justify-between items-center w-full py-2">
                <div class="text-sm font-black text-indigo-700 flex items-center gap-2 mx-auto">
                    <span class="animate-pulse">👉</span> 
                    <span x-text="getSelectedStudentName()"></span>
                </div>
            </div>
        </div>
        
        <div class="flex overflow-x-auto p-3 gap-3 no-scrollbar items-center">
            @foreach($rewards as $reward)
                <!-- В функцию giveReward теперь передается флаг is_absent -->
                <button 
                    @click="giveReward({{ $reward->id }}, '{{ addslashes($reward->name) }}', {{ $reward->is_absent ? 'true' : 'false' }})"
                    :disabled="!selectedStudentId"
                    style="background-color: {{ $reward->color ?? '#ffffff' }}"
                    :class="selectedStudentId ? 'opacity-100 active:scale-90 hover:brightness-95' : 'opacity-40 grayscale cursor-not-allowed'"
                    class="flex-shrink-0 flex flex-col items-center justify-center w-20 h-20 border border-black/10 rounded-2xl shadow-sm transition-all"
                >
                    <div class="text-lg font-black flex items-center justify-center h-10 w-full" style="color: {{ ($reward->color && strtolower($reward->color) !== '#ffffff') ? '#ffffff' : '#4338ca' }}">
                        @if($reward->symbol_latex)
                            <span>${!! $reward->symbol_latex !!}$</span>
                        @elseif($reward->svg_content)
                            <div class="h-8 w-8 [&>svg]:w-full [&>svg]:h-full">{!! $reward->svg_content !!}</div>
                        @else
                            {{ $reward->key }}
                        @endif
                    </div>
                    <span class="text-[10px] font-bold mt-1 leading-tight text-center px-1" style="color: {{ ($reward->color && strtolower($reward->color) !== '#ffffff') ? '#ffffff' : '#4b5563' }}">{{ $reward->name }}</span>
                </button>
            @endforeach
        </div>
    </footer>

    <!-- ЧИСТАЯ ЛОГИКА (БЕЗ COOKIES ДЛЯ ОТСУТСТВУЮЩИХ) -->
    <script>
        function remoteApp(groupId, serverAbsents) {
            return {
                groupId: groupId,
                students: @json($students->map->only(['id', 'first_name', 'last_name'])),
                
                selectedStudentId: null,
                rouletteRunning: false,
                
                answeredIds: [], // Оставляем куки только для тех, кто уже отвечал на уроке
                absentIds: serverAbsents, // Берем список отсутствующих прямо из БД
                
                toast: { show: false, message: '', rewardId: null, isAbsent: false, studentId: null },
                toastTimeout: null,

                init() {
                    let ans = localStorage.getItem(`answered_${this.groupId}`);
                    this.answeredIds = ans ? JSON.parse(ans) : [];
                },

                saveAnswered() { localStorage.setItem(`answered_${this.groupId}`, JSON.stringify(this.answeredIds)); },
                hasAnswered(id) { return this.answeredIds.includes(id); },
                changeGroup(newGroupId) { window.location.href = `/class-rewards/${newGroupId}`; },

                getSelectedStudentName() {
                    if (!this.selectedStudentId) return '';
                    let s = this.students.find(x => x.id === this.selectedStudentId);
                    return s ? s.last_name + ' ' + s.first_name : '';
                },

                selectStudent(id) {
                    if (this.rouletteRunning || this.absentIds.includes(id)) return;
                    this.selectedStudentId = id;
                },

                getAvailableStudents() {
                    // Исключаем и ответивших, и отсутствующих
                    let avail = this.students.filter(s => !this.hasAnswered(s.id) && !this.absentIds.includes(s.id));
                    
                    if (avail.length === 0) {
                        this.answeredIds = [];
                        this.saveAnswered();
                        // Если все перебраны, начинаем новый круг (но без отсутствующих)
                        avail = this.students.filter(s => !this.absentIds.includes(s.id));
                    }
                    return avail;
                },

                spinRoulette() {
                    if (this.rouletteRunning || this.students.length === 0) return;
                    
                    let avail = this.getAvailableStudents();
                    if (avail.length === 0) {
                        alert("Некого выбирать. Все ученики отмечены как отсутствующие.");
                        return;
                    }

                    this.rouletteRunning = true;
                    this.selectedStudentId = null;
                    
                    setTimeout(() => {
                        let winner = avail[Math.floor(Math.random() * avail.length)];
                        this.selectedStudentId = winner.id;
                        this.rouletteRunning = false;
                    }, 500);
                },

                // ДОБАВЛЕН ФЛАГ isAbsent
                async giveReward(rewardId, rewardName, isAbsent) {
                    if (!this.selectedStudentId) return;
                    
                    const studentId = this.selectedStudentId; // Запоминаем ID до сброса
                    const studentName = this.getSelectedStudentName();

                    try {
                        let res = await fetch(`/class-rewards/award`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ student_id: studentId, reward_id: rewardId })
                        });
                        
                        if (!res.ok) throw new Error();
                        let data = await res.json();
                        
                        if (data.success) {
                            // Сохраняем данные для функции отмены (undo)
                            this.showToast(`<b>${studentName}</b> получил(а) <b>${rewardName}</b>!`, data.id, isAbsent, studentId);
                            
                            if (isAbsent) {
                                // Если это Вакуум - делаем ученика серым прямо сейчас
                                if (!this.absentIds.includes(studentId)) {
                                    this.absentIds.push(studentId);
                                }
                            } else {
                                // Если обычная награда - помечаем ответившим
                                if (!this.answeredIds.includes(studentId)) {
                                    this.answeredIds.push(studentId);
                                    this.saveAnswered();
                                }
                            }
                            
                            this.selectedStudentId = null;
                        }
                    } catch(e) {
                        alert('Ошибка сети! Награда не выдана.');
                    }
                },

                showToast(msg, rewardId, isAbsent, studentId) {
                    this.toast.message = msg;
                    this.toast.rewardId = rewardId;
                    this.toast.isAbsent = isAbsent;
                    this.toast.studentId = studentId;
                    this.toast.show = true;
                    
                    if(this.toastTimeout) clearTimeout(this.toastTimeout);
                    this.toastTimeout = setTimeout(() => { this.toast.show = false; }, 5000);
                },

                async undoReward() {
                    if (!this.toast.rewardId) return;
                    try {
                        let res = await fetch(`/class-rewards/undo/${this.toast.rewardId}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        });
                        
                        if (res.ok) {
                            this.toast.show = false;
                            
                            // Откатываем визуальные изменения на Пульте
                            if (this.toast.isAbsent) {
                                // Воскрешаем из Вакуума
                                this.absentIds = this.absentIds.filter(id => id !== this.toast.studentId);
                            } else {
                                // Убираем из ответивших
                                this.answeredIds = this.answeredIds.filter(id => id !== this.toast.studentId);
                                this.saveAnswered();
                            }
                            
                            alert('Выдача отменена!');
                        }
                    } catch(e) { alert('Ошибка сети при отмене.'); }
                }
            }
        }
    </script>
</body>
</html>