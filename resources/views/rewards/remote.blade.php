<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <!-- Отключаем масштабирование (зум), чтобы приложение ощущалось как нативное -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Пульт: {{ $group->name }}</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    <!-- MathJax -->
    <script> MathJax = { tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] } }; </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>
    
    <style>
        body { background-color: #f3f4f6; -webkit-tap-highlight-color: transparent; }
        mjx-container svg { display: inline; }
        /* Скрываем скроллбар в панели наград для красоты */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="remoteApp()" class="h-screen flex flex-col overflow-hidden text-gray-800">

    <!-- ВСПЛЫВАЮЩЕЕ УВЕДОМЛЕНИЕ (TOAST) И ОТМЕНА -->
    <div x-show="toast.show" x-transition x-cloak class="fixed top-4 left-4 right-4 z-50 flex items-center justify-between bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg">
        <div class="font-bold text-sm" x-html="toast.message"></div>
        <button @click="undoReward()" class="bg-white text-green-700 font-bold px-3 py-1 rounded text-sm shadow active:scale-95 transition">
            ОТМЕНИТЬ
        </button>
    </div>

    <!-- ШАПКА: ВЫБОР ГРУППЫ -->
    <header class="bg-indigo-600 text-white p-3 shadow-md z-10 flex-shrink-0 flex items-center gap-3">
        <select @change="changeGroup($event.target.value)" class="flex-1 bg-indigo-700 border-none text-white text-lg font-bold rounded p-2 focus:ring-0">
            @foreach($allGroups as $g)
                <option value="{{ $g->id }}" {{ $g->id == $group->id ? 'selected' : '' }}>
                    {{ $g->grade ? $g->grade.' кл - ' : '' }}{{ $g->name }}
                </option>
            @endforeach
        </select>
    </header>

    <!-- КНОПКА РУЛЕТКИ -->
    <div class="p-3 flex-shrink-0 z-10 bg-gray-100 shadow-sm border-b">
        <button @click="spinRoulette()" :disabled="rouletteRunning" class="w-full bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-black text-lg py-3 rounded-lg shadow active:scale-95 transition disabled:opacity-50">
            🎲 Выбрать случайно
        </button>
    </div>

    <!-- СПИСОК УЧЕНИКОВ (ПРОКРУЧИВАЕТСЯ) -->
    <main class="flex-1 overflow-y-auto p-3">
        @if($students->isEmpty())
            <div class="text-center text-gray-500 mt-10">В этой группе нет учеников.</div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pb-32">
                <template x-for="student in students" :key="student.id">
                    <button 
                        @click="selectStudent(student.id)"
                        :class="{
                            'bg-yellow-400 text-yellow-900 scale-105 shadow-lg border-yellow-500': highlightedStudentId === student.id && rouletteRunning,
                            'bg-indigo-600 text-white shadow-md border-indigo-700': selectedStudentId === student.id && !rouletteRunning,
                            'bg-white text-gray-700 border-gray-200 shadow-sm hover:bg-gray-50': selectedStudentId !== student.id && (highlightedStudentId !== student.id || !rouletteRunning)
                        }"
                        class="p-3 rounded-xl border text-sm font-bold transition-all duration-100 flex flex-col items-center justify-center text-center h-16"
                    >
                        <span x-text="student.last_name"></span>
                        <span x-text="student.first_name" class="text-xs opacity-80 font-normal"></span>
                    </button>
                </template>
            </div>
        @endif
    </main>

    <!-- ПАНЕЛЬ НАГРАД (ПРИЛИПШАЯ К НИЗУ) -->
    <footer class="fixed bottom-0 w-full bg-white border-t border-gray-200 shadow-[0_-4px_10px_rgba(0,0,0,0.05)] z-20 pb-safe">
        <div class="p-1 bg-gray-50 text-center text-xs text-gray-500 font-bold border-b">
            <span x-show="selectedStudentId" class="text-indigo-600">Выберите награду для выдачи ↓</span>
            <span x-show="!selectedStudentId">Сначала выберите ученика ↑</span>
        </div>
        
        <!-- Горизонтальный скролл для наград -->
        <div class="flex overflow-x-auto p-3 gap-3 no-scrollbar items-center">
            @foreach($rewards as $reward)
                <button 
                    @click="giveReward({{ $reward->id }}, '{{ $reward->name }}')"
                    :class="selectedStudentId ? 'opacity-100 active:scale-90 hover:bg-gray-50' : 'opacity-40 grayscale'"
                    class="flex-shrink-0 flex flex-col items-center justify-center w-20 h-20 bg-white border border-gray-200 rounded-2xl shadow-sm transition-all"
                >
                    <div class="text-lg text-indigo-700 font-black flex items-center justify-center h-10 w-full">
                        @if($reward->symbol_latex)
                            <span>${!! $reward->symbol_latex !!}$</span>
                        @elseif($reward->svg_content)
                            <div class="h-8 w-8 [&>svg]:w-full [&>svg]:h-full">{!! $reward->svg_content !!}</div>
                        @else
                            {{ $reward->key }}
                        @endif
                    </div>
                    <span class="text-[10px] font-bold text-gray-600 mt-1 leading-tight text-center px-1">{{ $reward->name }}</span>
                </button>
            @endforeach
        </div>
    </footer>

    <!-- Логика приложения -->
    <script>
        function remoteApp() {
            return {
                token: '{{ $token }}',
                students: @json($students->map->only(['id', 'first_name', 'last_name'])),
                
                selectedStudentId: null,
                highlightedStudentId: null,
                rouletteRunning: false,
                
                toast: { show: false, message: '', rewardId: null },
                toastTimeout: null,

                // Смена группы (редирект с сохранением токена)
                changeGroup(groupId) {
                    window.location.href = `/class-rewards/${groupId}?token=${this.token}`;
                },

                // Выбор ученика вручную
                selectStudent(id) {
                    if (this.rouletteRunning) return;
                    this.selectedStudentId = id;
                },

                // Рулетка
                spinRoulette() {
                    if (this.rouletteRunning || this.students.length === 0) return;
                    
                    this.rouletteRunning = true;
                    this.selectedStudentId = null;
                    
                    let spins = 0;
                    const maxSpins = 20 + Math.floor(Math.random() * 10); // Рандомное количество прыжков (20-30)
                    
                    // Звук тиканья (опционально, можно раскомментировать, если есть аудиофайл)
                    // const tickSound = new Audio('/tick.mp3');

                    const interval = setInterval(() => {
                        const randomIdx = Math.floor(Math.random() * this.students.length);
                        this.highlightedStudentId = this.students[randomIdx].id;
                        // tickSound.play();
                        
                        spins++;
                        if (spins >= maxSpins) {
                            clearInterval(interval);
                            this.selectedStudentId = this.highlightedStudentId;
                            this.rouletteRunning = false;
                            
                            // Звук победы
                            // new Audio('/tada.mp3').play();
                        }
                    }, 100); // Скорость прыжков
                },

                // Выдача награды
                async giveReward(rewardId, rewardName) {
                    if (!this.selectedStudentId) return;

                    const studentName = this.students.find(s => s.id === this.selectedStudentId).last_name;

                    try {
                        let res = await fetch(`/class-rewards/award`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ student_id: this.selectedStudentId, reward_id: rewardId })
                        });
                        
                        let data = await res.json();
                        
                        if (data.success) {
                            // Показываем уведомление
                            this.showToast(`<b>${studentName}</b> получил(а) <b>${rewardName}</b>!`, data.id);
                            
                            // Сбрасываем выбор
                            this.selectedStudentId = null;
                            this.highlightedStudentId = null;
                        }
                    } catch(e) {
                        alert('Ошибка сети! Награда не выдана.');
                    }
                },

                // Показ уведомления
                showToast(msg, id) {
                    this.toast.message = msg;
                    this.toast.rewardId = id;
                    this.toast.show = true;
                    
                    if(this.toastTimeout) clearTimeout(this.toastTimeout);
                    // Скрываем плашку через 5 секунд
                    this.toastTimeout = setTimeout(() => { this.toast.show = false; }, 5000);
                },

                // Отмена выдачи (Undo)
                async undoReward() {
                    if (!this.toast.rewardId) return;
                    try {
                        let res = await fetch(`/class-rewards/undo/${this.toast.rewardId}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        });
                        if (res.ok) {
                            this.toast.show = false;
                            alert('Выдача награды отменена!');
                        }
                    } catch(e) {
                        alert('Ошибка сети при отмене.');
                    }
                }
            }
        }
    </script>
</body>
</html>