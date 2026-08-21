<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600 transition">&larr; Назад</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $variant->work->title ?? 'Работа' }} <span class="text-gray-400 font-normal">/ {{ $variant->name }}</span>
            </h2>
        </div>
    </x-slot>

    <!-- MathJax -->
    <script> MathJax = { tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] }, svg: { fontCache: 'global' } }; </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>
    <style> mjx-container svg { display: inline; } mjx-container[jax="SVG"][display="true"] { display: block; margin: 1em 0; } </style>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="bg-indigo-50 border border-indigo-200 text-indigo-800 px-6 py-4 rounded-lg shadow-sm font-bold flex justify-between items-center">
                <span>Вариант назначен группе: {{ auth()->user()->group->name ?? '' }}</span>
                <span class="text-sm font-normal">Всего задач: {{ $variantTasks->count() }}</span>
            </div>

            @forelse($variantTasks as $task)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden border border-gray-200">
                    <div class="bg-gray-50 px-6 py-3 border-b flex justify-between items-center flex-wrap gap-4">
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-gray-800">Задача №{{ $task->id }}</span>
                            <span class="text-xs font-bold bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Сложн: {{ $task->complexity }}</span>
                        </div>
                        
                        <!-- БЛОК САМОНАЗНАЧЕНИЯ -->
                        <div>
                            @if(in_array($task->id, $alreadyAssignedTaskIds))
                                <!-- Если уже назначена -->
                                <span class="text-sm font-bold text-green-600 bg-green-50 px-3 py-1 rounded border border-green-200">
                                    ✓ Добавлена в ваши задания
                                </span>
                            @elseif($task->canBeSelfAssigned($task->pivot->is_self_assignable))
                                <!-- Если разрешено брать -->
                                <form action="{{ route('student.selfAssign', [$variant->id, $task->id]) }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-1.5 px-4 rounded shadow transition transform hover:scale-105">
                                        + Взять в проработку
                                    </button>
                                </form>
                            @else
                                <!-- Если запрещено -->
                                <span class="text-sm text-gray-400 italic">Только для чтения</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="p-6 text-gray-800 text-base leading-relaxed">
                        {!! nl2br(e($task->task_text)) !!}

                        @if($task->taskImages->count() > 0)
                            <div class="mt-6 flex flex-wrap gap-4 p-4 bg-gray-50 rounded border">
                                @foreach($task->taskImages as $img)
                                    <div x-data="{ rotate: 0 }" class="relative group/img">
                                        <a href="{{ asset($img->file_path) }}" target="_blank" class="block bg-white p-1 border rounded shadow-sm hover:shadow-md transition">
                                            <img src="{{ asset($img->file_path) }}" :style="`transform: rotate(${rotate}deg); transition: transform 0.3s ease;`" class="object-contain" style="width: {{ $img->scale }}%; min-width: 150px; max-height: 400px;">
                                        </a>
                                        <button @click.prevent="rotate = rotate + 90" class="absolute bottom-2 right-2 bg-gray-800/70 text-white w-8 h-8 rounded-full opacity-0 group-hover/img:opacity-100 hover:bg-gray-900 transition flex items-center justify-center text-lg" title="Повернуть на 90°">⟳</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-white p-8 text-center text-gray-500 rounded-lg border shadow-sm">В этом варианте пока нет задач.</div>
            @endforelse

        </div>
    </div>
</x-app-layout>