<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Печать QR-кодов для входа</title>
    <style>
        body { font-family: sans-serif; margin: 0; padding: 0; background: #fff; color: #000; }
        /* Сетка для печати: 3 карточки в ряд, чтобы влезло на А4 */
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; }
        
        .card { 
            border: 1px dashed #ccc; /* Пунктирная линия для ножниц */
            padding: 20px; 
            text-align: center;
            page-break-inside: avoid; /* Не разрывать карточку между листами */
        }
        
        .name { font-size: 16px; font-weight: bold; margin-bottom: 5px; }
        .group { font-size: 12px; color: #555; margin-bottom: 15px; }
        .qr-wrapper { margin: 0 auto; display: inline-block; padding: 10px; border: 1px solid #eee; border-radius: 8px; }
        .link { font-size: 9px; margin-top: 15px; word-break: break-all; color: #666; font-family: monospace; }
        
        /* Скрываем всё лишнее при печати */
        @media print {
            body { padding: 0cm; }
            .no-print { display: none; }
        }
        .print-btn { display: block; width: 200px; margin: 20px auto; padding: 10px; background: #4f46e5; color: white; text-align: center; text-decoration: none; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>

    <button onclick="window.print()" class="print-btn no-print">🖨 Отправить на принтер</button>

    <div class="grid">
        @foreach($users as $user)
            <div class="card">
                <div class="name">{{ $user->last_name }} {{ $user->first_name }}</div>
                <div class="group">{{ $user->group->name ?? 'Группа не назначена' }}</div>
                
                <div class="qr-wrapper">
                    {!! SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)->generate(url('/my_assignments.php?token=' . $user->auth_token)) !!}
                </div>

                <div class="link">
                    Сканируйте код камерой<br>
                    {{ url('/my_assignments.php?token=' . $user->auth_token) }}
                </div>
            </div>
        @endforeach
    </div>

    <script>
        // Автоматически открываем окно печати через полсекунды
        setTimeout(() => { window.print(); }, 500);
    </script>
</body>
</html>