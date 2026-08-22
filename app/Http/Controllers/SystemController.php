<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Exception;

class SystemController extends Controller
{
    public function mailTest()
    {
        return view('system.mail-test');
    }

    public function sendMailTest(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $log = "";
        $status = "success";

        try {
            // Пытаемся отправить простейшее текстовое письмо
            Mail::raw('Это тестовое сообщение от нового сайта на Laravel. Если вы его читаете, значит SMTP настроен верно!', function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('Диагностика SMTP Laravel');
            });

            $log = "✅ Письмо успешно отправлено на адрес {$request->email}!\n\nКонфигурация Laravel сработала корректно.";
            
        } catch (Exception $e) {
            $status = "error";
            // Если ошибка, собираем всё, что вернул сервер (текст ошибки + кусок кода, где она произошла)
            $log = "❌ ОШИБКА ОТПРАВКИ:\n\n";
            $log .= $e->getMessage() . "\n\n";
            $log .= "--- Техническая трассировка (для хостинга) ---\n";
            $log .= $e->getTraceAsString();
        }

        return back()->with(compact('log', 'status'));
    }
}