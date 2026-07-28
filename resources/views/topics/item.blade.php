<li class="p-2 {{ $level === 0 ? 'bg-gray-50 border rounded mb-4 shadow-sm' : 'hover:bg-gray-100 rounded mt-1' }}">
    <div class="flex justify-between items-center">
        <div>
            <!-- Делаем корневые темы синими и крупными, а вложенные - обычными -->
            <span class="{{ $level === 0 ? 'font-bold text-lg text-blue-600' : 'text-gray-700' }}">
                {{ $topic->name }}
            </span>
            
            @if($topic->description && $level === 0)
                <div class="text-sm text-gray-500">{{ $topic->description }}</div>
            @endif
        </div>
        <div class="space-x-3 text-sm opacity-50 hover:opacity-100 transition-opacity">
            <a href="#" class="text-blue-500 hover:underline">Добавить подтему</a>
            <a href="#" class="text-gray-500 hover:underline">Изменить</a>
            <a href="#" class="text-red-500 hover:underline">Удалить</a>
        </div>
    </div>

    <!-- Рекурсивный вызов: если есть дети, рисуем их этим же самым шаблоном! -->
    @if ($topic->children->count() > 0)
        <!-- Отступ слева (ml-6) и полосочка (border-l-2) создают визуальную лесенку -->
        <ul class="ml-6 border-l-2 border-gray-200 pl-4 mt-2">
            @foreach ($topic->children as $child)
                <!-- Вызываем этот же файл, но увеличиваем $level на 1 -->
                @include('topics.item', ['topic' => $child, 'level' => $level + 1])
            @endforeach
        </ul>
    @endif
</li>