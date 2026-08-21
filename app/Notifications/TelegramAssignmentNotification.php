<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;
use NotificationChannels\Telegram\TelegramChannel;

class TelegramAssignmentNotification extends Notification
{
    use Queueable;

    public $assignment;
    public $type;

    /**
     * @param $assignment - Объект назначения
     * @param $type - Тип: 'assigned', 'submitted', 'reviewed'
     */
    public function __construct($assignment, $type)
    {
        $this->assignment = $assignment;
        $this->type = $type;
    }

    public function via($notifiable)
    {
        // Отправляем только если у пользователя привязан Telegram
        return $notifiable->telegram_chat_id ? [TelegramChannel::class] : [];
    }

    public function toTelegram($notifiable)
    {
        // Очищаем текст задачи от LaTeX и HTML для красивого превью
        $taskText = strip_tags($this->assignment->task->task_text);
        $taskPreview = mb_substr($taskText, 0, 50) . '...';

        // Создаем сообщение (используем HTML для форматирования)
        $message = TelegramMessage::create()
            ->to($notifiable->telegram_chat_id)
            ->options(['parse_mode' => 'HTML']);

        if ($this->type === 'assigned') {
            $url = url("/assignments/{$this->assignment->id}");
            $message->content("🔔 <b>Новое задание!</b>\n\nЗадача №{$this->assignment->task_id}: \"{$taskPreview}\"")
                    ->button('Перейти к решению', $url);
        } 
        elseif ($this->type === 'submitted') {
            $url = url("/assignments/review/{$this->assignment->id}");
            $studentName = "{$this->assignment->student->last_name} {$this->assignment->student->first_name}";
            $message->content("📥 <b>Новое решение на проверку</b>\n\nУченик: <b>{$studentName}</b>\nЗадача №{$this->assignment->task_id}: \"{$taskPreview}\"")
                    ->button('Проверить сейчас', $url);
        } 
        elseif ($this->type === 'reviewed') {
            $url = url("/assignments/{$this->assignment->id}");
            $status = $this->assignment->status === 'accepted' ? "✅ <b>Работа принята!</b>" : "🔄 <b>Требуется доработка</b>";
            
            if ($this->assignment->status === 'accepted' && $this->assignment->mark_percent !== null) {
                $status .= "\nОценка: <b>{$this->assignment->mark_percent}%</b>";
            }
            
            $message->content("{$status}\n\nЗадача №{$this->assignment->task_id}: \"{$taskPreview}\"")
                    ->button('Посмотреть детали', $url);
        }

        return $message;
    }
}