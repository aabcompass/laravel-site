<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate; // ВАЖНО: Добавить эту строку!
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Гейт для управления справочниками (Темы, Источники)
        // Разрешаем только админам
        Gate::define('manage-references', function ($user) {
            return $user->hasRole('admin');
        });

        // Гейт для управления задачами (Создание, Редактирование, Удаление)
        // Разрешаем админам и авторам
        Gate::define('manage-tasks', function ($user) {
            return $user->hasAnyRole(['admin', 'author']);
        });

        // Гейт для просмотра чужих задач и назначения их ученикам (будущий функционал)
        // Разрешаем админам, авторам и учителям
        Gate::define('use-tasks', function ($user) {
            return $user->hasAnyRole(['admin', 'author', 'teacher']);
        });

        // (В будущем сюда добавим Гейты для advanced_student и т.д.)
    }
}