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
        // По умолчанию берем дату ровно месяц назад
        $dateFrom = $request->input('date_from', Carbon::now()->subMonth()->format('Y-m-d'));
        $groupId = $request->input('group_id');

        $groups = Group::orderBy('grade')->orderBy('name')->get();
        // Награды для ручного добавления (только те, что требуют регистрации)
        $availableRewards = Reward::where('requires_registration', true)->orderBy('z_number')->get();

        $students = [];
        $rewardsMatrix = [];
        $uniqueDates = [];

        if ($groupId) {
            $students = User::where('group_id', $groupId)->orderBy('last_name')->get();
            $studentIds = $students->pluck('id')->toArray();

            // Получаем все награды этой группы за выбранный период
            $studentRewards = StudentReward::with(['reward'])
                ->whereIn('student_id', $studentIds)
                ->whereDate('created_at', '>=', $dateFrom)
                ->orderBy('created_at')
                ->get();

            // Группируем даты (только те дни, когда реально выдавались награды)
            $uniqueDates = $studentRewards->pluck('created_at')->map(fn($d) => $d->format('Y-m-d'))->unique()->sort()->values();

            // Строим матрицу: $rewardsMatrix[student_id][date] = [массив наград]
            foreach ($studentRewards as $sr) {
                $dateKey = $sr->created_at->format('Y-m-d');
                $rewardsMatrix[$sr->student_id][$dateKey][] = $sr;
            }
        }

        return view('rewards.journal', compact(
            'groups', 'groupId', 'dateFrom', 'students', 'uniqueDates', 'rewardsMatrix', 'availableRewards'
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
        ]);

        StudentReward::create([
            'student_id' => $request->student_id,
            'reward_id' => $request->reward_id,
            'teacher_id' => auth()->id(),
            'is_accounted' => true, // Ручное добавление сразу считается учтенным!
            'is_handed_over' => true, // Считаем, что и вручено
            'created_at' => Carbon::parse($request->date)->setTime(12, 0, 0), // Ставим время на полдень
        ]);

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
     * Отображение мобильного пульта (Вход по токену)
     */
    public function remoteShow(Request $request, Group $group)
    {
        $token = $request->query('token');
        if (!$token) abort(403, 'Токен доступа не передан.');

        // Ищем учителя по токену
        $teacher = User::where('auth_token', $token)->first();
        if (!$teacher || !$teacher->hasAnyRole(['teacher', 'author', 'admin'])) {
            abort(403, 'Неверный токен или нет прав преподавателя.');
        }

        // Авторизуем учителя (чтобы безопасно работали AJAX-запросы)
        auth()->login($teacher);

        // Получаем всех учеников выбранной группы
        $students = User::where('group_id', $group->id)->orderBy('last_name')->get();
        
        // Получаем все группы для выпадающего списка вверху
        $allGroups = Group::orderBy('grade')->orderBy('name')->get();

        // Получаем только награды "На лету" И требующие регистрации
        $rewards = Reward::where('is_for_answer', true)
                         ->where('requires_registration', true)
                         ->orderBy('z_number')
                         ->get();

        return view('rewards.remote', compact('group', 'students', 'allGroups', 'rewards', 'token'));
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
            'is_accounted' => false, // По умолчанию не учтено
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
     * Удаление выданной награды из Журнала
     */
    public function destroy(StudentReward $studentReward)
    {
        // Удалять может либо автор выдачи, либо админ
        if ($studentReward->teacher_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403, 'Вы можете удалять только выданные вами награды.');
        }

        $studentReward->delete();
        return back()->with('success', 'Награда успешно удалена из истории ученика.');
    }
};