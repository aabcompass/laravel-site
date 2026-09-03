<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Награда: {{ $studentReward->reward->name }}</title>
    
    <!-- ПРАВИЛЬНАЯ ИНИЦИАЛИЗАЦИЯ MATHJAX -->
    <script>
        MathJax = { 
            tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] },
            startup: {
                pageReady: () => {
                    return MathJax.startup.defaultPageReady().then(() => {
                        // Как только все формулы отрендерились, вызываем окно печати
                        setTimeout(() => { window.print(); }, 500);
                    });
                }
            }
        }; 
    </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>

    <style>
        body { font-family: sans-serif; margin: 0; padding: 0; background: #f0f0f0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .certificate { background: #fff; width: 21cm; min-height: 15cm; padding: 2cm; box-sizing: border-box; box-shadow: 0 10px 30px rgba(0,0,0,0.2); position: relative; border: 1px solid #ccc; }
        .reason { font-size: 1.5em; text-align: center; color: #555; text-transform: uppercase; letter-spacing: 2px; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 30px; font-weight: bold; }
        .main-content { display: flex; justify-content: space-between; align-items: flex-start; gap: 40px; }
        .details { flex: 1; }
        .reward-name { font-size: 3em; font-weight: 900; margin: 0 0 10px 0; color: #111; }
        .reward-symbol { font-size: 5em; font-weight: bold; color: #4f46e5; margin: 20px 0; line-height: 1; }
        .desc-block { margin-top: 30px; }
        .desc-title { font-size: 0.9em; text-transform: uppercase; color: #888; font-weight: bold; margin-bottom: 5px; }
        .desc-text { font-size: 1.2em; line-height: 1.5; color: #333; margin-bottom: 20px; }
        .qr-block { text-align: center; border: 4px solid #4f46e5; padding: 20px; border-radius: 20px; background: #fff; }
        .qr-hint { font-size: 1.2em; font-weight: bold; color: #4f46e5; margin-top: 15px; max-width: 250px; }
        .teacher-sign { position: absolute; bottom: 2cm; right: 2cm; font-size: 1.2em; color: #555; font-style: italic; }
        
        mjx-container svg { display: inline; }
        
        @media print {
            body { background: #fff; display: block; }
            .certificate { box-shadow: none; border: none; width: 100%; min-height: 100%; padding: 0; }
        }
    </style>
</head>
<body>

    <div class="certificate">
        <div class="reason">ЗА ЗАСЛУГИ: <span style="color: #4f46e5;">{{ $studentReward->reason ?? 'ОТЛИЧНАЯ РАБОТА' }}</span></div>
        
        <div class="main-content">
            <div class="details">
                <h1 class="reward-name">{{ $studentReward->reward->name }}</h1>
                
                @if($studentReward->reward->symbol_latex)
                    <div class="reward-symbol">${!! $studentReward->reward->symbol_latex !!}$</div>
                @elseif($studentReward->reward->svg_content)
                    <div style="width: 200px; height: 200px; margin: 20px 0; [&>svg]:w-full [&>svg]:h-full">{!! $studentReward->reward->svg_content !!}</div>
                @endif

                <div class="desc-block">
                    @if($studentReward->reward->public_desc)
                        <div class="desc-title">За что выдается:</div>
                        <div class="desc-text">{{ $studentReward->reward->public_desc }}</div>
                    @endif

                    @if($studentReward->reward->perks)
                        <div class="desc-title">Преференции (Бонусы):</div>
                        <div class="desc-text"><strong>{{ $studentReward->reward->perks }}</strong></div>
                    @endif
                </div>
            </div>

            <div class="qr-block">
                {!! SimpleSoftwareIO\QrCode\Facades\QrCode::size(250)->generate(route('rewards.claim', $studentReward->claim_hash)) !!}
                <div class="qr-hint">Отсканируй камерой, чтобы забрать награду в инвентарь!</div>
            </div>
        </div>

        <div class="teacher-sign">Выдал(а): {{ $studentReward->teacher->last_name }} {{ Str::substr($studentReward->teacher->first_name, 0, 1) }}.</div>
    </div>

</body>
</html>