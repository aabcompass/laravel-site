<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($reward) ? "Редактирование: {$reward->name}" : 'Новая награда' }}
        </h2>
    </x-slot>

    <!-- MathJax -->
    <script> MathJax = { tex: { inlineMath: [['$', '$']], displayMath: [['$$', '$$']] } }; </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js"></script>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            
            <form action="{{ isset($reward) ? route('rewards.update', $reward->id) : route('rewards.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @if(isset($reward)) @method('PUT') @endif

                @if ($errors->any())
                    <div class="p-4 bg-red-100 text-red-700 rounded-lg shadow-sm font-bold">
                        <ul class="list-disc pl-5">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- ЛЕВАЯ КОЛОНКА -->
                    <div class="space-y-6">
                        <div class="bg-white shadow sm:rounded-lg p-6 border">
                            <h3 class="text-lg font-bold border-b pb-2 mb-4">Основные данные</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block font-medium text-sm text-gray-700 mb-1">Ключ / Артикул <span class="text-red-500">*</span></label>
                                    <input type="text" name="key" value="{{ old('key', $reward->key ?? '') }}" placeholder="Напр. HE-4" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500" required>
                                </div>
                                
                                <div>
                                    <label class="block font-medium text-sm text-gray-700 mb-1">Название <span class="text-red-500">*</span></label>
                                    <input type="text" name="name" value="{{ old('name', $reward->name ?? '') }}" placeholder="Гелий" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500" required>
                                </div>

                                <div>
                                    <label class="block font-medium text-sm text-gray-700 mb-1">Символ (LaTeX)</label>
                                    <div class="flex gap-4">
                                        <input type="text" id="symbol_latex" name="symbol_latex" value="{{ old('symbol_latex', $reward->symbol_latex ?? '') }}" placeholder="^{4}_{2}\text{He}" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500" oninput="renderMath()">
                                        <div class="bg-gray-50 border px-4 py-2 rounded shadow-inner min-w-[80px] flex items-center justify-center font-bold text-lg" id="preview_symbol"></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block font-medium text-sm text-gray-700 mb-1">Зарядовое число (Z)</label>
                                        <input type="number" name="z_number" value="{{ old('z_number', $reward->z_number ?? '') }}" class="w-full border-gray-300 rounded shadow-sm">
                                    </div>
                                    <div>
                                        <label class="block font-medium text-sm text-gray-700 mb-1">Массовое число (A)</label>
                                        <input type="number" name="a_number" value="{{ old('a_number', $reward->a_number ?? '') }}" class="w-full border-gray-300 rounded shadow-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white shadow sm:rounded-lg p-6 border">
                            <h3 class="text-lg font-bold border-b pb-2 mb-4">Правила выдачи</h3>
                            <div class="space-y-4">
                                <label class="flex items-center gap-2 cursor-pointer p-2 hover:bg-gray-50 rounded border">
                                    <input type="checkbox" name="is_for_answer" value="1" {{ old('is_for_answer', $reward->is_for_answer ?? false) ? 'checked' : '' }} class="rounded text-green-600 focus:ring-green-500 w-5 h-5">
                                    <div>
                                        <span class="font-bold text-sm text-gray-800">Награда "за ответ"</span>
                                        <div class="text-xs text-gray-500">Выдается на уроке "на лету" (без сложной процедуры)</div>
                                    </div>
                                </label>

                                <label class="flex items-center gap-2 cursor-pointer p-2 hover:bg-gray-50 rounded border">
                                    <input type="checkbox" name="requires_registration" value="1" {{ old('requires_registration', $reward->requires_registration ?? true) ? 'checked' : '' }} class="rounded text-indigo-600 focus:ring-indigo-500 w-5 h-5">
                                    <div>
                                        <span class="font-bold text-sm text-gray-800">Требует регистрации</span>
                                        <div class="text-xs text-gray-500">Если снято, награда выдается физически без занесения в БД ученика</div>
                                    </div>
                                </label>

                                <div>
                                    <label class="block font-medium text-sm text-gray-700 mb-1">Тип физического носителя</label>
                                    <input type="text" name="carrier_type" value="{{ old('carrier_type', $reward->carrier_type ?? '') }}" placeholder="Магнит, Брелок (или оставьте пустым)" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ПРАВАЯ КОЛОНКА -->
                    <div class="space-y-6">
                        
                        <div class="bg-white shadow sm:rounded-lg p-6 border">
                            <h3 class="text-lg font-bold border-b pb-2 mb-4">Визуал (Карточка SVG/PNG)</h3>
                            
                            @if(isset($reward) && $reward->image_path)
                                <div class="mb-4 bg-gray-900 p-4 rounded-lg flex justify-center">
                                    <img src="{{ asset($reward->image_path) }}" class="max-h-64 object-contain shadow-lg rounded">
                                </div>
                            @endif

                            <input type="file" name="image" accept=".svg,.png,.jpg,.jpeg" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        </div>

                        <div class="bg-white shadow sm:rounded-lg p-6 border">
                            <h3 class="text-lg font-bold border-b pb-2 mb-4">Тексты и Описания</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block font-bold text-sm text-gray-700 mb-1">Публичное описание</label>
                                    <div class="text-xs text-gray-500 mb-1">За что дается (видят все)</div>
                                    <textarea name="public_desc" rows="2" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 text-sm">{{ old('public_desc', $reward->public_desc ?? '') }}</textarea>
                                </div>

                                <div>
                                    <label class="block font-bold text-sm text-gray-700 mb-1">Преференции</label>
                                    <div class="text-xs text-gray-500 mb-1">Что дает эта награда (например, иммунитет к двойке)</div>
                                    <textarea name="perks" rows="2" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 text-sm">{{ old('perks', $reward->perks ?? '') }}</textarea>
                                </div>

                                <div>
                                    <label class="block font-bold text-sm text-gray-700 mb-1">Физическое описание</label>
                                    <div class="text-xs text-gray-500 mb-1">Интересный факт о самом элементе/частице</div>
                                    <textarea name="physical_desc" rows="3" class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 text-sm">{{ old('physical_desc', $reward->physical_desc ?? '') }}</textarea>
                                </div>

                                <div>
                                    <label class="block font-bold text-sm text-red-700 mb-1">Приватное описание (Только для учителей)</label>
                                    <textarea name="private_desc" rows="2" class="w-full border-red-200 bg-red-50 rounded shadow-sm focus:ring-red-500 text-sm">{{ old('private_desc', $reward->private_desc ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="flex items-center gap-4 bg-white p-4 shadow sm:rounded-lg border">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-lg py-2 px-6 rounded shadow transition">
                        {{ isset($reward) ? 'Сохранить изменения' : 'Создать награду' }}
                    </button>
                    <a href="{{ route('rewards.index') }}" class="text-gray-600 hover:underline">Отмена</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function renderMath() {
            const input = document.getElementById('symbol_latex').value;
            const target = document.getElementById('preview_symbol');
            target.innerHTML = '$' + input + '$';
            if (typeof MathJax !== 'undefined' && MathJax.typesetPromise) {
                MathJax.typesetPromise([target]);
            }
        }
        document.addEventListener('DOMContentLoaded', renderMath);
    </script>
</x-app-layout> 