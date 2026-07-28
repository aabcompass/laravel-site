<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\SourceController;

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
    Route::delete('/topics/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy');});

    // --- ИСТОЧНИКИ ---
    Route::get('/sources', [SourceController::class, 'index'])->name('sources.index');
    Route::get('/sources/create', [SourceController::class, 'create'])->name('sources.create');
    Route::post('/sources', [SourceController::class, 'store'])->name('sources.store');
    Route::get('/sources/{source}/edit', [SourceController::class, 'edit'])->name('sources.edit');
    Route::put('/sources/{source}', [SourceController::class, 'update'])->name('sources.update');
    Route::patch('/sources/{source}/move/{direction}', [SourceController::class, 'move'])->name('sources.move');
    Route::delete('/sources/{source}', [SourceController::class, 'destroy'])->name('sources.destroy');

require __DIR__.'/auth.php';
