<?php

namespace App\Http\Controllers;

use App\Models\WorkVariant;
use App\Models\Group;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    /**
     * Публичная страница конкретного Варианта
     */
    public function showVariant($hash)
    {
        // Ищем вариант по хэшу. Если не найден - отдаем 404
        $variant = \App\Models\WorkVariant::where('public_hash', $hash)->firstOrFail();
        
        // Подгружаем задачи и картинки
        $variantTasks = $variant->tasks()->with('taskImages')->get();

        // Для печатного шаблона требуются дополнительные переменные:
        $showAnswers = false; // Публичная ссылка всегда без ответов (для безопасности)
        $group = null;        // Без привязки к конкретной группе в шапке

        // Отдаем шаблон печати!
        return view('variants.print', compact('variant', 'variantTasks', 'showAnswers', 'group'));
    }

    /**
     * Публичная страница Группы (Список всех выданных вариантов)
     */
    public function showGroup($hash)
    {
        $group = Group::where('public_hash', $hash)->firstOrFail();

        // Достаем историю выдачи (от новых к старым)
        $assignments = \App\Models\AssignmentHistory::where('group_id', $group->id)
            ->with(['variant.work'])
            ->orderBy('assigned_at', 'desc')
            ->get();

        return view('public.group', compact('group', 'assignments'));
    }
}