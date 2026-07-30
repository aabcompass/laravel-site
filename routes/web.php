<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\StudentAssignmentController; 

Route::get('/', function () {
    return view('welcome');
});

//Route::get('/dashboard', function () {
//    return view('dashboard');
//})->middleware(['auth', 'verified'])->name('dashboard');

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
    Route::get('/assignments/{assignment}', [StudentAssignmentController::class, 'show'])->name('assignments.show');
    Route::post('/assignments/{assignment}/submit', [StudentAssignmentController::class, 'submit'])->name('assignments.submit');
    Route::post('/assignments/{assignment}/recall', [StudentAssignmentController::class, 'recall'])->name('assignments.recall');
    Route::delete('/assignments/{assignment}/attachments/{attachment}', [StudentAssignmentController::class, 'destroyAttachment'])->name('assignments.attachments.destroy');
 
    
    // === ЗОНА АДМИНА (Только для 'admin') ===
    Route::middleware('can:manage-references')->group(function () {
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
    });
});

require __DIR__.'/auth.php';
