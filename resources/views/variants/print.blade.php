<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Печать: {{ $variant->name }}</title>
    <!-- MathJax -->
    <script> MathJax = { tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] }, svg: { fontCache: 'global' } }; </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>

    <style>
        body { 
            font-family: 'Times New Roman', Times, serif; 
            color: #000; margin: 0; padding: 0; background: #fff;
            font-size: {{ $variant->print_font_size }}pt; 
            line-height: 1.4;
        }
        
        mjx-container svg { display: inline; } 
        mjx-container[jax="SVG"][display="true"] { display: block; margin: 1em 0; }

        .wrapper { max-width: 21cm; margin: 0 auto; }
        
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 10px; }
        .header h1 { font-size: 1.4em; margin: 0 0 5px 0; }
        .header h2 { font-size: 1.2em; margin: 0; font-weight: normal; }
        
        .meta-info { display: flex; justify-content: space-between; font-weight: bold; margin-bottom: 10px; font-size: 0.9em; }
        .instructions { font-style: italic; margin-bottom: 15px; }

        .task { 
            margin-bottom: 15px; /* Базовый отступ */
            page-break-inside: avoid;
        }
        .task-number { font-weight: bold; margin-right: 5px; }
        
        .task-images { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 5px; }
        .task-images img { max-width: 100%; border: 1px solid #ccc; }

        .teacher-version { background: #f0f0f0; padding: 10px; border-left: 3px solid #000; margin-top: 10px; font-family: sans-serif; font-size: 0.9em; }
        
        /* === ТЕТРАДНАЯ КЛЕТКА === */
        .solution-grid {
            margin-top: 10px;
            margin-bottom: 20px;
            width: 100%;
            /* 1 "строка" = 1 клетка = 5 мм */
            height: {{ $variant->print_spacing_lines * 5 }}mm;
            
            /* Рисуем клетку 5х5 мм */
            background-image: 
                linear-gradient(to right, #9ca3af 1px, transparent 1px),
                linear-gradient(to bottom, #9ca3af 1px, transparent 1px);
            background-size: 5mm 5mm;
            
            /* Замыкаем рамку со всех сторон для красоты */
            border: 1px solid #9ca3af;
            
            /* Принудительная печать фона в современных браузерах */
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        /* Линия отреза */
        .cut-line { 
            border-top: 1px dashed #999; 
            margin: 30px 0; 
            position: relative; 
            page-break-after: always;
        }
        .cut-line::before { content: "✂"; position: absolute; top: -14px; left: -20px; font-size: 20px; color: #666; background: #fff; padding: 0 5px; }

        /* СПЕЦИАЛЬНЫЙ CSS ДЛЯ ПРИНТЕРА */
        @media print {
            @page { margin: 1cm; }
            body { background: transparent; }
            .wrapper { max-width: none; width: 100%; margin: 0; }
            .instance { page-break-inside: avoid; }
            
            @if($variant->print_copies_per_page == 2)
                .print-grid { display: block; }
                .cut-line { page-break-after: auto; }
            @elseif($variant->print_copies_per_page == 4)
                .print-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
                .cut-line { display: none; }
            @endif
        }
    </style>
</head>
<body>

<div class="wrapper print-grid">
    @for ($i = 0; $i < $variant->print_copies_per_page; $i++)
        
        <div class="instance" style="padding: 20px; box-sizing: border-box; overflow: hidden;">
            <!-- Шапка -->
            <div class="header">
                <h1>{{ $variant->work->title }}</h1>
                <h2>{{ $variant->name }}</h2>
            </div>

            <div class="meta-info">
                <div>{{ $group ? 'Группа: ' . $group->name : '' }}</div>
                <div>
                    @if($variant->print_show_name_field)
                        Фамилия Имя: ____________________________
                    @endif
                </div>
                <div>Дата: _____/_____/20___</div>
            </div>

            @if($variant->print_instructions)
                <div class="instructions">{!! nl2br(e($variant->print_instructions)) !!}</div>
            @endif

            <!-- Список задач -->
            @foreach($variantTasks as $task)
                <div class="task">
                    <span class="task-number">
                        {{ $loop->iteration }}.
                        @if($variant->print_show_task_id || $variant->print_show_complexity)
                            (@php
                                $meta = [];
                                if ($variant->print_show_task_id) $meta[] = $task->id;
                                if ($variant->print_show_complexity) $meta[] = 'сложн. ' . $task->complexity;
                                echo implode(', ', $meta);
                            @endphp)
                        @endif
                    </span>
                    <span class="task-text">{!! nl2br(e($task->task_text)) !!}</span>
                    
                    @if($task->taskImages->count() > 0)
                        <div class="task-images">
                            @foreach($task->taskImages as $img)
                                <img src="{{ asset($img->file_path) }}" style="width: {{ $img->scale }}%;">
                            @endforeach
                        </div>
                    @endif

                    <!-- ВЫВОД РЕШЕНИЙ ИЛИ КЛЕТКИ -->
                    @if($showAnswers)
                        <div class="teacher-version">
                            <strong>ОТВЕТ:</strong> {{ $task->answer_numeric }} {{ $task->answer_units }}<br>
                            <strong>Решение:</strong> {!! nl2br(e($task->author_solution_text)) !!}
                        </div>
                        <div style="margin-bottom: {{ $variant->print_spacing_lines * 5 }}mm;"></div>
                    @else
                        @if($variant->print_spacing_lines > 0)
                            <!-- Тот самый блок с сеткой -->
                            <div class="solution-grid"></div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>

        @if ($variant->print_copies_per_page == 2 && $i == 0)
            <div class="cut-line"></div>
        @endif

    @endfor
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof MathJax !== 'undefined') {
            MathJax.startup.promise.then(() => {
                setTimeout(() => { window.print(); }, 500);
            });
        } else {
            window.print();
        }
    });
</script>

</body>
</html>