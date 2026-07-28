<li class="p-2 {{ $level === 0 ? 'bg-gray-50 border rounded mb-4 shadow-sm' : 'hover:bg-gray-100 rounded mt-1' }}">
    <div class="flex justify-between items-center">
        <div>
            <span class="{{ $level === 0 ? 'font-bold text-lg text-blue-600' : 'text-gray-700' }}">
                {{ $source->name }}
            </span>
            @if($source->description && $level === 0)
                <div class="text-sm text-gray-500">{{ $source->description }}</div>
            @endif
        </div>
        
        <!-- ЕДИНСТВЕННЫЙ БЛОК С КНОПКАМИ -->
        <div class="space-x-3 text-sm flex items-center">
            
            <!-- КНОПКИ ВВЕРХ / ВНИЗ -->
            <div class="flex items-center gap-1 mr-2 border-r border-gray-300 pr-4">
                <form action="{{ route('sources.move', [$source->id, 'up']) }}" method="POST" class="m-0">
                    @csrf @method('PATCH')
                    <button type="submit" class="w-7 h-7 flex items-center justify-center bg-gray-500 hover:bg-gray-700 text-white rounded-full transition-colors text-xs shadow-sm" title="Вверх">
                        ▲
                    </button>
                </form>
                <form action="{{ route('sources.move', [$source->id, 'down']) }}" method="POST" class="m-0">
                    @csrf @method('PATCH')
                    <button type="submit" class="w-7 h-7 flex items-center justify-center bg-gray-500 hover:bg-gray-700 text-white rounded-full transition-colors text-xs shadow-sm" title="Вниз">
                        ▼
                    </button>
                </form>
            </div>

            <!-- Ссылки -->
            <a href="{{ route('sources.create', ['parent_id' => $source->id]) }}" class="text-blue-600 hover:text-blue-800 hover:underline">Добавить дочерний источник</a>
            
            <a href="{{ route('sources.edit', $source->id) }}" class="text-gray-600 hover:text-gray-800 hover:underline">Изменить</a>
            
            <form action="{{ route('sources.destroy', $source->id) }}" method="POST" class="inline m-0" onsubmit="return confirm('Удалить этот источник? Дочерние источники станут корневыми.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-500 hover:text-red-700 hover:underline cursor-pointer">Удалить</button>
            </form>

        </div>

    </div>

    @if ($source->children->count() > 0)
        <ul class="ml-6 border-l-2 border-gray-200 pl-4 mt-2">
            @foreach ($source->children as $child)
                @include('sources.item', ['source' => $child, 'level' => $level + 1])
            @endforeach
        </ul>
    @endif
</li>