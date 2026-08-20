<x-guest-layout>
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-black text-gray-900 mb-2">Задания для класса</h1>
        <div class="inline-block bg-indigo-100 text-indigo-800 text-lg px-4 py-1 rounded-full font-bold shadow-sm">
            {{ $group->grade ? $group->grade.' класс - ' : '' }}{{ $group->name }}
        </div>
    </div>

    @if($groupVariants->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($groupVariants as $history)
                <!-- Ссылка ведет на публичный просмотр варианта -->
                <a href="/variant/{{ $history->variant->public_hash }}" class="block bg-white p-5 rounded-lg border border-gray-200 hover:border-indigo-400 hover:shadow-md transition group">
                    <div class="text-xs text-indigo-500 font-bold mb-2 uppercase tracking-wider">
                        {{ $history->variant->work->title ?? 'Без названия' }}
                    </div>
                    <div class="font-bold text-gray-800 text-lg group-hover:text-indigo-700 transition">
                        {{ $history->variant->name }}
                    </div>
                    <div class="mt-4 flex justify-between items-center text-sm text-gray-500 border-t pt-3">
                        <span class="bg-gray-100 px-2 py-1 rounded">Выдано: {{ $history->assigned_at->format('d.m.Y') }}</span>
                        <span class="text-indigo-600 font-medium group-hover:underline">Открыть &rarr;</span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $groupVariants->links() }}
        </div>
    @else
        <div class="bg-white p-8 text-center text-gray-500 rounded-lg border shadow-sm text-lg">
            Учитель пока не выдал ни одной работы этому классу.
        </div>
    @endif
</x-guest-layout>