<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\StudentAssignmentController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkController;
use App\Http\Controllers\WorkVariantController;
use App\Http\Controllers\PublicController; 
use App\Http\Controllers\TutorController;
use App\Http\Controllers\TutorMatrixController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\HandbookController;


Route::get('/', function () {
    return view('welcome');
});

// === ОБЩИЙ СПРАВОЧНИК ===
Route::get('/handbook', [HandbookController::class, 'index'])->name('handbook.index');
Route::get('/api/handbook/search', [HandbookController::class, 'search']);
Route::get('/api/handbook/substance/{id}', [HandbookController::class, 'getSubstance']);


Route::get('/my_assignments.php', function (\Illuminate\Http\Request $request) {
    $token = $request->query('token');
    
    if (!$token) {
        abort(401, 'Токен доступа не передан.');
    }

    // Ищем пользователя с таким токеном в базе
    $user = \App\Models\User::where('auth_token', $token)->first();

    if (!$user) {
        abort(403, 'Пользователь с таким токеном не найден.');
    }

    // Авторизуем пользователя в Laravel без пароля
    auth()->login($user);

    // Перенаправляем на новую главную страницу студента
    return redirect()->route('dashboard')->with('success', 'Вы успешно вошли по токену доступа!');
});

// === ОБЩАЯ ЗОНА (Только для авторизованных) ===
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        // === ЗОНА СТУДЕНТОВ (Мои задания) ===
    Route::get('/dashboard', [StudentAssignmentController::class, 'index'])->name('dashboard');
    Route::get('/progress', [StudentAssignmentController::class, 'progress'])->name('assignments.progress');
    Route::get('/student/variants/{variant}', [StudentAssignmentController::class, 'showVariant'])->name('student.variants.show');
    Route::get('/assignments/{assignment}', [StudentAssignmentController::class, 'show'])->name('assignments.show');
    Route::post('/assignments/{assignment}/submit', [StudentAssignmentController::class, 'submit'])->name('assignments.submit');
    Route::post('/assignments/{assignment}/recall', [StudentAssignmentController::class, 'recall'])->name('assignments.recall');
    Route::delete('/assignments/{assignment}/attachments/{attachment}', [StudentAssignmentController::class, 'destroyAttachment'])->name('assignments.attachments.destroy');
    Route::post('/student/variants/{variant}/tasks/{task}/self-assign', [StudentAssignmentController::class, 'selfAssign'])->name('student.selfAssign');
    
    // === ЗОНА АДМИНА (Только для 'admin') ===
    Route::middleware('can:manage-references')->group(function () {
        // Системные инструменты
        Route::get('/system/mail-test', [SystemController::class, 'mailTest'])->name('system.mail-test');
        Route::post('/system/mail-test', [SystemController::class, 'sendMailTest'])->name('system.mail-test.send');
        // Пользователи
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/bulk', [UserController::class, 'bulkCreate'])->name('users.bulkCreate');
        Route::post('/users/bulk', [UserController::class, 'bulkStore'])->name('users.bulkStore');
        Route::get('/users/print-qr', [UserController::class, 'printQr'])->name('users.printQr');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        // Группы
        Route::patch('/users/{user}/group', [UserController::class, 'updateGroup'])->name('users.updateGroup');
        Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
        Route::get('/groups/create', [GroupController::class, 'create'])->name('groups.create');
        Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
        Route::get('/groups/{group}/edit', [GroupController::class, 'edit'])->name('groups.edit');
        Route::put('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
        Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');
        // Темы
        Route::get('/topics', [TopicController::class, 'index'])->name('topics.index');
        Route::get('/topics/create', [TopicController::class, 'create'])->name('topics.create');
        Route::post('/topics', [TopicController::class, 'store'])->name('topics.store');
        Route::get('/topics/{topic}/edit', [TopicController::class, 'edit'])->name('topics.edit');
        Route::put('/topics/{topic}', [TopicController::class, 'update'])->name('topics.update');
        Route::patch('/topics/{topic}/move/{direction}', [TopicController::class, 'move'])->name('topics.move');
        Route::delete('/topics/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy');

        // Источники
        Route::get('/sources', [SourceController::class, 'index'])->name('sources.index');
        Route::get('/sources/create', [SourceController::class, 'create'])->name('sources.create');
        Route::post('/sources', [SourceController::class, 'store'])->name('sources.store');
        Route::get('/sources/{source}/edit', [SourceController::class, 'edit'])->name('sources.edit');
        Route::put('/sources/{source}', [SourceController::class, 'update'])->name('sources.update');
        Route::patch('/sources/{source}/move/{direction}', [SourceController::class, 'move'])->name('sources.move');
        Route::delete('/sources/{source}', [SourceController::class, 'destroy'])->name('sources.destroy');
        Route::get('/login-as/{user}', function (\App\Models\User $user) {
        // Защита: только админы или авторы могут использовать эту фичу
            if (!auth()->user()->hasAnyRole(['admin', 'author', 'teacher'])) {
            abort(403, 'Только учителя могут входить под чужим аккаунтом.');
            }

            // Авторизуем найденного пользователя (без пароля!)
            auth()->login($user);

            // Перекидываем на главную страницу студента
            return redirect('/dashboard')->with('success', "Вы вошли как {$user->first_name} {$user->last_name}");
        });
    });

    // === ЗОНА АВТОРОВ (Для 'admin' и 'author') ===
    Route::middleware('can:manage-tasks')->group(function () {
        // Задачи
        Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
        Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::delete('/attachments/{attachment}', [TaskController::class, 'destroyAttachment'])->name('attachments.destroy');
        Route::post('/tasks/{task}/copy', [TaskController::class, 'copy'])->name('tasks.copy');
        Route::resource('works', WorkController::class)->except(['show']);
        // Варианты работ (Уровень 1)
        Route::get('/works/{work}/variants', [WorkVariantController::class, 'index'])->name('works.variants.index');
        Route::post('/works/{work}/variants', [WorkVariantController::class, 'store'])->name('works.variants.store');
        Route::post('/variants/{variant}/clone', [WorkVariantController::class, 'clone'])->name('variants.clone');
        Route::patch('/variants/{variant}/archive', [WorkVariantController::class, 'toggleArchive'])->name('variants.archive');
        Route::patch('/variants/{variant}/name', [WorkVariantController::class, 'updateName'])->name('variants.updateName');
        // Наполнение варианта (Уровень 2)
        Route::get('/variants/{variant}/build', [WorkVariantController::class, 'build'])->name('variants.build');
        Route::post('/variants/{variant}/attach', [WorkVariantController::class, 'attachTasks'])->name('variants.attach');
        Route::delete('/variants/{variant}/detach/{task}', [WorkVariantController::class, 'detachTask'])->name('variants.detach');
        Route::put('/variants/{variant}/reorder', [WorkVariantController::class, 'reorderTasks'])->name('variants.reorder');
        Route::put('/variants/{variant}/sort-complexity', [WorkVariantController::class, 'sortByComplexity'])->name('variants.sortComplexity');
        // Настройки печати и печать
        Route::get('/variants/{variant}/print-config', [WorkVariantController::class, 'printConfig'])->name('variants.printConfig');
        Route::put('/variants/{variant}/print-config', [WorkVariantController::class, 'updatePrintConfig'])->name('variants.updatePrintConfig');
        Route::get('/variants/{variant}/print', [WorkVariantController::class, 'print'])->name('variants.print');
        // Выдача и отзыв вариантов
        Route::post('/variants/{variant}/assign', [WorkVariantController::class, 'assignToGroup'])->name('variants.assign');
        Route::delete('/variants/assignments/{history}', [WorkVariantController::class, 'revokeFromGroup'])->name('variants.revoke');
        // Удаление вариантов
        Route::delete('/variants/{variant}', [WorkVariantController::class, 'destroy'])->name('variants.destroy');
        // Индивидуальная работа (Мои ученики)
        Route::get('/tutors/students', [TutorController::class, 'index'])->name('tutors.index');
        Route::post('/tutors/students', [TutorController::class, 'store'])->name('tutors.store');
        Route::delete('/tutors/students', [TutorController::class, 'destroy'])->name('tutors.destroy');
        // Матрица индивидуальных назначений
        Route::get('/tutors/matrix', [TutorMatrixController::class, 'index'])->name('tutors.matrix');
        Route::post('/tutors/matrix/assign', [TutorMatrixController::class, 'assign'])->name('tutors.matrix.assign');
        Route::post('/tutors/matrix/unassign', [TutorMatrixController::class, 'unassign'])->name('tutors.matrix.unassign');      
        // Проверка решений учителем
        Route::get('/assignments/review/{assignment}', [ReviewController::class, 'show'])->name('assignments.review');
        Route::put('/assignments/review/{assignment}', [ReviewController::class, 'update'])->name('assignments.review.update');
        Route::patch('/variants/{variant}/tasks/{task}/self-assign', [WorkVariantController::class, 'toggleSelfAssign'])->name('variants.toggleSelfAssign');
    });
});

Route::get('/v/{hash}', [PublicController::class, 'showVariant'])->name('public.variant');
Route::get('/g/{hash}', [PublicController::class, 'showGroup'])->name('public.group');

require __DIR__.'/auth.php';
