<?php

namespace App\Http\Controllers;

use App\Models\StudentReward;
use App\Models\Reward;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StudentRewardController extends Controller
{
    /**
     * Журнал наград (Десктоп)
     */
    public function journal(Request $request)
    {
        $dateFrom = $request->input('date_from', \Carbon\Carbon::now()->subMonth()->format('Y-m-d'));
        $groupId = $request->input('group_id');

        $groups = \App\Models\Group::orderBy('grade')->orderBy('name')->get();
        $availableRewards = \App\Models\Reward::where('requires_registration', true)->orderBy('z_number')->get();

        // Достаем уникальные причины, которые вводил этот учитель (для автозаполнения)
        $teacherReasons = StudentReward::where('teacher_id', auth()->id())
            ->whereNotNull('reason')->where('reason', '!=', '')
            ->distinct()->pluck('reason');

        // Берем последнее введенное описание для этой группы из сессии
        $defaultReason = $groupId ? session('last_reason_group_' . $groupId, '') : '';
        $defaultReward = $groupId ? session('last_reward_group_' . $groupId, '') : '';

        $students = [];
        $rewardsMatrix = [];
        $uniqueColumns = []; // Теперь колонки - это комбинация Дата + Причина

        if ($groupId) {
            $students = User::where('group_id', $groupId)->orderBy('last_name')->get();
            $studentIds = $students->pluck('id')->toArray();

            $studentRewards = StudentReward::with(['reward'])
                ->whereIn('student_id', $studentIds)
                ->whereDate('created_at', '>=', $dateFrom)
                ->orderBy('created_at')
                ->get();

            $columnsRaw = [];
            foreach ($studentRewards as $sr) {
                $dateKey = $sr->created_at->format('Y-m-d');
                $reasonKey = $sr->reason ?? '';
                $colKey = $dateKey . '|' . $reasonKey; // Создаем уникальный ключ колонки

                if (!isset($columnsRaw[$colKey])) {
                    $columnsRaw[$colKey] = ['date' => $dateKey, 'reason' => $sr->reason];
                }
                $rewardsMatrix[$sr->student_id][$colKey][] = $sr;
            }

            // Сортируем колонки: сначала по дате, затем по алфавиту причины
            usort($columnsRaw, function($a, $b) {
                if ($a['date'] == $b['date']) return strcmp($a['reason'], $b['reason']);
                return strcmp($a['date'], $b['date']);
            });
            $uniqueColumns = $columnsRaw;
        }

        return view('rewards.journal', compact(
            'groups', 'groupId', 'dateFrom', 'students', 'uniqueColumns', 'rewardsMatrix', 'availableRewards', 'teacherReasons', 'defaultReason', 'defaultReward'
        ));
    }

    /**
     * Ручное добавление награды через журнал
     */
    public function storeManual(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:Users,id',
            'reward_id' => 'required|exists:Rewards,id',
            'date' => 'required|date',
            'reason' => 'nullable|string|max:150', // <- Валидация причины
        ]);

        StudentReward::create([
            'student_id' => $request->student_id,
            'reward_id' => $request->reward_id,
            'teacher_id' => auth()->id(),
            'reason' => $request->reason, // <- Сохраняем причину
            'is_accounted' => true,
            'is_handed_over' => true,
            'created_at' => \Carbon\Carbon::parse($request->date)->setTime(12, 0, 0),
        ]);

        // Запоминаем эту причину в сессии для данной группы
        $student = User::find($request->student_id);
        session(['last_reason_group_' . $student->group_id => $request->reason]);
        session(['last_reward_group_' . $student->group_id => $request->reward_id]);

        return back()->with('success', 'Награда успешно добавлена ученику.');
    }

    /**
     * AJAX: Переключить флажок "Учтено"
     */
    public function toggleAccounted(StudentReward $studentReward)
    {
        $studentReward->update(['is_accounted' => !$studentReward->is_accounted]);
        return response()->json(['success' => true, 'is_accounted' => $studentReward->is_accounted]);
    }

    // =========================================================================
    // МОБИЛЬНЫЙ ПУЛЬТ УЧИТЕЛЯ (НА УРОКЕ)
    // =========================================================================


    /**
     * Отображение мобильного пульта
     */
    public function remoteShow(Request $request, Group $group)
    {
        // Если передали токен в URL - авторизуем и делаем редирект, чтобы скрыть токен
        if ($token = $request->query('token')) {
            $teacher = User::where('auth_token', $token)->first();
            if ($teacher && $teacher->hasAnyRole(['teacher', 'author', 'admin'])) {
                auth()->login($teacher, true); // true = Запомнить меня навсегда
                // Перенаправляем на чистый URL без токена!
                return redirect()->route('remote.show', $group->id);
            }
        }

        // Если токена нет, проверяем, есть ли у пользователя сессия
        if (!auth()->check() || !auth()->user()->hasAnyRole(['teacher', 'author', 'admin'])) {
            abort(403, 'Нет доступа. Войдите в систему или используйте персональную ссылку с токеном.');
        }

        $students = User::where('group_id', $group->id)->orderBy('last_name')->get();
        $allGroups = Group::orderBy('grade')->orderBy('name')->get();
        $rewards = Reward::where('is_for_answer', true)
                         ->where('requires_registration', true)
                         ->orderBy('z_number')
                         ->get();

        return view('rewards.remote', compact('group', 'students', 'allGroups', 'rewards'));
    }

    /**
     * AJAX: Выдать награду
     */
    public function remoteAward(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:Users,id',
            'reward_id' => 'required|exists:Rewards,id'
        ]);

        $sr = StudentReward::create([
            'student_id' => $request->student_id,
            'reward_id' => $request->reward_id,
            'teacher_id' => auth()->id(),
            'reason' => 'Устный ответ', // <- ДОБАВЛЕНО
            'is_accounted' => false,
            'is_handed_over' => false,
            'created_at' => now()
        ]);

        return response()->json(['success' => true, 'id' => $sr->id]);
    }

    /**
     * AJAX: Отменить выдачу (Undo)
     */
    public function remoteUndo(StudentReward $studentReward)
    {
        // Только тот, кто выдал, может отменить
        if ($studentReward->teacher_id !== auth()->id()) abort(403);
        
        $studentReward->delete();
        return response()->json(['success' => true]);
    }


    /**
     * Удаление выданной награды из Журнала (AJAX)
     */
    public function destroy(StudentReward $studentReward)
    {
        // Проверка прав
        if ($studentReward->teacher_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json(['success' => false, 'message' => 'Нет прав на удаление.'], 403);
        }

        // Удаляем из БД
        $studentReward->delete();

        // Всегда возвращаем успешный JSON (так как кнопка работает только через AJAX)
        return response()->json(['success' => true]);
    }

    // =========================================================================
    // ГЕНЕРАЦИЯ QR-НАГРАД (БЕЗ УЧЕНИКА)
    // =========================================================================

    /**
     * Создание "свободной" награды с хэшем
     */
    public function generateQr(Request $request)
    {
        $request->validate([
            'reward_id' => 'required|exists:Rewards,id',
            'reason' => 'nullable|string|max:150',
        ]);

        $sr = StudentReward::create([
            'student_id' => null, // Ученик пока неизвестен
            'reward_id' => $request->reward_id,
            'teacher_id' => auth()->id(),
            'reason' => $request->reason,
            'is_accounted' => false,
            'is_handed_over' => true, // Выдаем распечатку
            'claim_hash' => \Illuminate\Support\Str::random(40), // Генерируем уникальный хэш
            'created_at' => now(),
        ]);

        // Открываем печатную страницу в новой вкладке
        return redirect()->route('rewards.printQr', $sr->id);
    }


    /**
     * Печатная форма А4 для награды
     */
    public function printQr(StudentReward $studentReward)
    {
        if ($studentReward->teacher_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403);
        }
        return view('rewards.print-qr', compact('studentReward'));
    }

    /**
     * УЧЕНИК СКАНИРУЕТ QR-КОД (Получение награды)
     */
    public function claimQr($hash)
    {
        $sr = StudentReward::where('claim_hash', $hash)->firstOrFail();

        if ($sr->student_id) {
            return redirect()->route('dashboard')->with('error', 'Эта награда уже была кем-то получена!');
        }

        // Привязываем награду к текущему ученику
        $sr->update([
            'student_id' => auth()->id()
        ]);

        return redirect()->route('dashboard')->with('success', "🎉 Поздравляем! Вы получили новую награду: {$sr->reward->name}!");
    }
};