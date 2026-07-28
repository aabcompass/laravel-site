<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TopicController; // <- Добавьте эту строку вверх файла!

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
    // Обработка удаления
    Route::delete('/topics/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy');});

require __DIR__.'/auth.php';
