<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\AssignmentHistory;
use Illuminate\Http\Request;

class PublicGroupController extends Controller
{
    /**
     * Публичный каталог выданных работ для группы
     */
    public function show($hash)
    {
        // Ищем группу по хэшу
        $group = Group::where('public_hash', $hash)->firstOrFail();

        // Достаем историю выдачи (с пагинацией по 30 шт)
        $groupVariants = AssignmentHistory::where('group_id', $group->id)
            ->with(['variant.work']) 
            ->orderBy('assigned_at', 'desc')
            ->paginate(30);

        return view('public.group_variants', compact('group', 'groupVariants'));
    }
}