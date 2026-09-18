#!/bin/bash

# Имя итогового файла
OUTPUT="laravel_project_dump.txt"

# Очищаем файл, если он уже существует
> $OUTPUT

echo "Собираем файлы проекта..."

# Одиночные файлы, которые мы меняли
FILES=(
    "routes/web.php"
    "app/Providers/AppServiceProvider.php"
    "config/services.php"
)

# Папки, из которых берем все .php файлы (включая .blade.php)
DIRS=(
    "app/Models"
    "app/Http/Controllers"
    "app/Notifications"
    "resources/views"
    "database/migrations"
)

# Функция записи в файл
append_file() {
    local file=$1
    if [ -f "$file" ]; then
        echo -e "\n========================================================================" >> $OUTPUT
        echo "|| ФАЙЛ: ./$file" >> $OUTPUT
        echo "========================================================================" >> $OUTPUT
        cat "$file" >> $OUTPUT
        echo -e "\n" >> $OUTPUT
    fi
}

# Обрабатываем одиночные файлы
for file in "${FILES[@]}"; do
    append_file "$file"
done

# Обрабатываем папки (рекурсивно)
for dir in "${DIRS[@]}"; do
    if [ -d "$dir" ]; then
        # Ищем все .php файлы
        find "$dir" -type f -name "*.php" | sort | while read -r file; do
            # Исключаем стандартные дефолтные миграции Laravel, если они не нужны (по желанию)
            # но лучше забрать все, чтобы ИИ видел структуру БД
            append_file "$file"
        done
    fi
done

echo "✅ Экспорт завершен! Все данные сохранены в файл: $OUTPUT"
