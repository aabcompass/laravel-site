@foreach($sources as $t)
    @php 
        // Запрещаем выбирать саму себя
        $isDisabled = ($t->id == $currentId) ? 'disabled' : ''; 
    @endphp
    <option value="{{ $t->id }}" {{ $t->id == $selectedId ? 'selected' : '' }} {{ $isDisabled }}>
        {!! str_repeat('&nbsp;&nbsp;&nbsp;', $level) !!} {{ $t->name }}
    </option>
    @if($t->children->count() > 0)
        @include('sources.options', ['sources' => $t->children, 'level' => $level + 1, 'selectedId' => $selectedId, 'currentId' => $currentId])
    @endif
@endforeach