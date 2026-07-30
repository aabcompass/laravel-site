@php
    $stat = $stats[$topic->id];
@endphp

<tr class="hover:bg-blue-50 border-b transition-colors {{ $level == 0 ? 'bg-gray-50' : 'bg-white' }}">
    <!-- Название темы со сдвигом -->
    <td class="px-4 py-3" style="padding-left: {{ $level * 1.5 + 1 }}rem;">
        <span class="{{ $level == 0 ? 'font-bold text-gray-800' : 'text-gray-600' }}">
            {{ $topic->name }}
        </span>
    </td>
    
    <!-- В базе -->
    <td class="px-4 py-3 text-center font-bold">
        @if($stat['total_db'] > 0)
            <span class="text-red-500">{{ $stat['total_db'] }}</span>
        @else
            <span class="text-gray-300">—</span>
        @endif
    </td>
    
    <!-- Назначено -->
    <td class="px-4 py-3 text-center">
        @if($stat['assigned'] > 0)
            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded font-bold">{{ $stat['assigned'] }}</span>
        @else
            <span class="text-gray-300">—</span>
        @endif
    </td>
    
    <!-- На проверке -->
    <td class="px-4 py-3 text-center">
        @if($stat['submitted'] > 0)
            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded font-bold">{{ $stat['submitted'] }}</span>
        @else
            <span class="text-gray-300">—</span>
        @endif
    </td>

    <!-- Решено -->
    <td class="px-4 py-3 text-center">
        @if($stat['solved'] > 0)
            <span class="bg-green-100 text-green-800 px-2 py-1 rounded font-bold">{{ $stat['solved'] }}</span>
        @else
            <span class="text-gray-300">—</span>
        @endif
    </td>

    <!-- Средний балл (Решено) -->
    <td class="px-4 py-3 text-center">
        @if($stat['avg_score'] !== null)
            <span class="font-bold {{ $stat['avg_score'] >= 80 ? 'text-green-600' : ($stat['avg_score'] >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                {{ round($stat['avg_score'], 1) }}%
            </span>
        @else
            <span class="text-gray-300">—</span>
        @endif
    </td>

    <!-- Ср. сложность (Решено) -->
    <td class="px-4 py-3 text-center text-gray-700 font-medium">
        {{ $stat['avg_comp_solved'] !== null ? round($stat['avg_comp_solved'], 1) : '—' }}
    </td>

    <!-- Ср. сложность (Нерешено) -->
    <td class="px-4 py-3 text-center text-gray-700 font-medium">
        {{ $stat['avg_comp_unsolved'] !== null ? round($stat['avg_comp_unsolved'], 1) : '—' }}
    </td>
</tr>

<!-- Рекурсивный вызов для подтем -->
@if ($topic->children->count() > 0)
    @foreach ($topic->children as $child)
        @include('assignments.progress-row', ['topic' => $child, 'level' => $level + 1])
    @endforeach
@endif