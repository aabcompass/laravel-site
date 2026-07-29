<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\TaskController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Вывод списка
    Route::get('/topics', [TopicController::class, 'index'])->name('topics.index');
    Route::get('/topics/create', [TopicController::class, 'create'])->name('topics.create');
    Route::post('/topics', [TopicController::class, 'store'])->name('topics.store');
    // Показ формы редактирования
    Route::get('/topics/{topic}/edit', [TopicController::class, 'edit'])->name('topics.edit');
    // Обработка сохранения изменений
    Route::put('/topics/{topic}', [TopicController::class, 'update'])->name('topics.update');
    Route::patch('/topics/{topic}/move/{direction}', [TopicController::class, 'move'])->name('topics.move');
    // Обработка удаления
    Route::delete('/topics/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy');

    // --- ИСТОЧНИКИ ---
    Route::get('/sources', [SourceController::class, 'index'])->name('sources.index');
    Route::get('/sources/create', [SourceController::class, 'create'])->name('sources.create');
    Route::post('/sources', [SourceController::class, 'store'])->name('sources.store');
    Route::get('/sources/{source}/edit', [SourceController::class, 'edit'])->name('sources.edit');
    Route::put('/sources/{source}', [SourceController::class, 'update'])->name('sources.update');
    Route::patch('/sources/{source}/move/{direction}', [SourceController::class, 'move'])->name('sources.move');
    Route::delete('/sources/{source}', [SourceController::class, 'destroy'])->name('sources.destroy');

    // --- ЗАДАЧИ ---
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    
    // Специальный маршрут для удаления картинки
    Route::delete('/attachments/{attachment}', [TaskController::class, 'destroyAttachment'])->name('attachments.destroy');
    // Специальный маршрут для копирования задачи
    Route::post('/tasks/{task}/copy', [TaskController::class, 'copy'])->name('tasks.copy');
    });

require __DIR__.'/auth.php';
