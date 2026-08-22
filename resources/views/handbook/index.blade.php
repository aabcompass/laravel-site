@php
use App\Http\Controllers\HandbookController;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Справочник по физике</h2>
    </x-slot>

    <!-- Alpine.js Состояние: activeTab переключает вкладки -->
    <div class="py-8" x-data="{ activeTab: 'search' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- НАВИГАЦИЯ (Вкладки) -->
            <div class="flex overflow-x-auto border-b border-gray-200 bg-white rounded-t-lg shadow-sm">
                <button @click="activeTab = 'search'" :class="activeTab === 'search' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-bold text-sm focus:outline-none transition-colors">
                    🔍 Поиск вещества
                </button>
                <button @click="activeTab = 'density'" :class="activeTab === 'density' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-bold text-sm focus:outline-none transition-colors">
                    🧱 Плотность
                </button>
                <button @click="activeTab = 'thermal'" :class="activeTab === 'thermal' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-bold text-sm focus:outline-none transition-colors">
                    🌡 Тепловые свойства
                </button>
                <button @click="activeTab = 'humidity'" :class="activeTab === 'humidity' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-bold text-sm focus:outline-none transition-colors">
                    💧 Влажность и Пар
                </button>
                <button @click="activeTab = 'other'" :class="activeTab === 'other' ? 'border-blue-500 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-bold text-sm focus:outline-none transition-colors">
                    📚 Разное
                </button>
            </div>

            <div class="bg-white shadow-sm sm:rounded-b-lg border-x border-b border-gray-200 p-6 min-h-[500px]">

                <!-- Вкладка 1: ПОИСК (Живой AJAX) -->
                <div x-show="activeTab === 'search'" x-data="handbookSearch()">
                    <div class="max-w-xl mx-auto mb-8 relative">
                        <input type="text" x-model="query" @input.debounce.300ms="search()" placeholder="Начните вводить (вода, медь, лед)..." class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 py-3 text-lg">
                        
                        <!-- Выпадающий список результатов -->
                        <ul x-show="results.length > 0" x-cloak @click.away="results = []" class="absolute z-50 w-full bg-white border rounded-lg shadow-xl mt-1 max-h-64 overflow-y-auto">
                            <template x-for="item in results" :key="item.id">
                                <li @click="loadSubstance(item.id)" class="px-4 py-3 hover:bg-blue-50 cursor-pointer border-b last:border-b-0 font-medium text-gray-800" x-text="item.name"></li>
                            </template>
                        </ul>
                    </div>

                    <!-- Карточка вещества -->
                    <div x-show="substance" x-cloak class="max-w-2xl mx-auto bg-blue-50 border border-blue-200 rounded-xl p-6 shadow-sm">
                        <h3 class="text-2xl font-black text-blue-900 mb-4 border-b border-blue-200 pb-2" x-text="substance?.name"></h3>
                        <ul class="space-y-3">
                            <template x-for="prop in properties" :key="prop.name">
                                <li class="flex justify-between items-center bg-white p-3 rounded shadow-sm border border-blue-100">
                                    <span class="font-bold text-gray-700">
                                        <span x-text="prop.name"></span> 
                                        <span class="text-gray-400 font-normal" x-text="prop.symbol ? `(${prop.symbol})` : ''"></span>
                                    </span>
                                    <span class="font-mono text-lg text-blue-800 font-bold">
                                        <span x-text="prop.formatted_value"></span> 
                                        <span class="text-sm text-gray-500 ml-1" x-text="prop.units"></span>
                                    </span>
                                </li>
                            </template>
                        </ul>
                        <div x-show="properties.length === 0" class="text-gray-500 italic text-center py-4">Свойства для этого вещества не найдены.</div>
                    </div>
                    
                    <div x-show="!substance && query === ''" class="text-center text-gray-400 mt-10">
                        <div class="text-5xl mb-3">🔍</div>
                        Введите название вещества для поиска его физических констант
                    </div>
                </div>

                <!-- Вкладка 2: ПЛОТНОСТЬ -->
                <div x-show="activeTab === 'density'" x-cloak class="space-y-8">
                    @php $densityStates = ['solid' => 'Твердые тела', 'liquid' => 'Жидкости', 'gas' => 'Газы (при 0°C и 760 мм рт.ст.)']; @endphp
                    @foreach($densityStates as $state => $title)
                        <div>
                            <h3 class="font-black text-lg text-gray-800 border-b-2 border-blue-500 pb-1 mb-4 inline-block">Плотность: {{ $title }} <span class="text-gray-500 font-normal text-sm ml-2">(кг/м³)</span></h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-2">
                                @if(isset($propertiesByGroup['Плотность']))
                                    @foreach(array_filter($propertiesByGroup['Плотность'], fn($i) => $i->substance_state == $state) as $item)
                                        <div class="flex justify-between items-center border-b border-gray-100 py-1 hover:bg-gray-50">
                                            <span class="text-gray-700">{{ $item->substance_name }}</span>
                                            <span class="font-mono font-bold">{{ HandbookController::formatScientific($item->value) }}</span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Вкладка 3: ТЕПЛОВЫЕ СВОЙСТВА -->
                <div x-show="activeTab === 'thermal'" x-cloak class="space-y-8">
                    @php
                    $thermalProps = [
                        ['name' => 'Удельная теплоемкость', 'label' => 'Удельная теплоемкость (c)', 'units' => 'Дж/(кг·°C)'],
                        ['name' => 'Удельная теплота сгорания', 'label' => 'Удельная теплота сгорания (q)', 'units' => 'Дж/кг'],
                        ['name' => 'Температура плавления', 'label' => 'Температура плавления (t пл)', 'units' => '°C'],
                        ['name' => 'Удельная теплота плавления', 'label' => 'Удельная теплота плавления (λ)', 'units' => 'Дж/кг'],
                        ['name' => 'Температура кипения', 'label' => 'Температура кипения (t кип)', 'units' => '°C'],
                        ['name' => 'Удельная теплота парообразования', 'label' => 'Удельная теплота парообразования (L)', 'units' => 'Дж/кг'],
                        ['name' => 'Удельное сопротивление', 'label' => 'Удельное сопротивление (ρ)', 'units' => 'Ом·мм²/м'],
                    ];
                    @endphp

                    @foreach($thermalProps as $prop)
                        @if(isset($propertiesByGroup[$prop['name']]))
                        <div>
                            <h3 class="font-black text-lg text-gray-800 border-b-2 border-red-500 pb-1 mb-4 inline-block">{{ $prop['label'] }} <span class="text-gray-500 font-normal text-sm ml-2">({{ $prop['units'] }})</span></h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-2">
                                @foreach($propertiesByGroup[$prop['name']] as $item)
                                    <div class="flex justify-between items-center border-b border-gray-100 py-1 hover:bg-gray-50">
                                        <span class="text-gray-700">{{ $item->substance_name }}</span>
                                        <span class="font-mono font-bold">{{ HandbookController::formatScientific($item->value) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>

                <!-- Вкладка 4: ВЛАЖНОСТЬ И ПАР -->
                <div x-show="activeTab === 'humidity'" x-cloak class="space-y-8">
                    <div>
                        <h3 class="font-black text-lg text-gray-800 border-b-2 border-cyan-500 pb-1 mb-4 inline-block">Психрометрическая таблица <span class="text-gray-500 font-normal text-sm ml-2">(Относительная влажность, %)</span></h3>
                        <div class="overflow-x-auto border rounded-lg">
                            <table class="w-full text-sm text-center">
                                <thead class="bg-gray-100 text-gray-700">
                                    <tr>
                                        <th class="py-2 px-3 border-b border-r bg-gray-200">↓ t° сухого</th>
                                        @for($i=0; $i<=11; $i++) <th class="py-2 px-3 border-b border-gray-300">Δt° = {{ $i }}</th> @endfor
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($psychrometricData as $dryTemp => $diffs)
                                        <tr class="hover:bg-gray-50 border-b">
                                            <td class="py-1.5 px-3 border-r bg-gray-50 font-bold">{{ $dryTemp }}</td>
                                            @for($i=0; $i<=11; $i++) <td class="py-1.5 px-3 {{ isset($diffs[$i]) ? 'text-gray-800' : 'text-gray-300' }}">{{ $diffs[$i] ?? '—' }}</td> @endfor
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-black text-lg text-gray-800 border-b-2 border-cyan-500 pb-1 mb-4 inline-block">Насыщенный водяной пар</h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            @php 
                                $vaporCount = count($vaporData); 
                                $vaporMid = (int)ceil($vaporCount / 2);
                            @endphp
                            
                            <!-- Левая колонка пара -->
                            <table class="w-full text-sm text-center border">
                                <thead class="bg-gray-100"><tr><th class="py-2">t, °C</th><th class="py-2">Давление (мм рт.ст.)</th><th class="py-2">Плотность (г/м³)</th></tr></thead>
                                <tbody>
                                    @for($i = 0; $i < $vaporMid; $i++)
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="py-1 font-bold bg-gray-50">{{ $vaporData[$i]->temperature_celsius }}</td>
                                            <td class="py-1">{{ str_replace('.', ',', $vaporData[$i]->pressure_mmhg) }}</td>
                                            <td class="py-1">{{ str_replace('.', ',', $vaporData[$i]->density_kg_m3 * 1000) }}</td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                            
                            <!-- Правая колонка пара -->
                            <table class="w-full text-sm text-center border">
                                <thead class="bg-gray-100"><tr><th class="py-2">t, °C</th><th class="py-2">Давление (мм рт.ст.)</th><th class="py-2">Плотность (г/м³)</th></tr></thead>
                                <tbody>
                                    @for($i = $vaporMid; $i < $vaporCount; $i++)
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="py-1 font-bold bg-gray-50">{{ $vaporData[$i]->temperature_celsius }}</td>
                                            <td class="py-1">{{ str_replace('.', ',', $vaporData[$i]->pressure_mmhg) }}</td>
                                            <td class="py-1">{{ str_replace('.', ',', $vaporData[$i]->density_kg_m3 * 1000) }}</td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Вкладка 5: ОБЩИЕ СПРАВОЧНЫЕ ДАННЫЕ -->
                <div x-show="activeTab === 'other'" x-cloak class="space-y-8">
                    @foreach($referenceCategories as $category)
                        <div>
                            <h3 class="font-black text-lg text-gray-800 border-b-2 border-purple-500 pb-1 mb-2 inline-block">{{ $category->name }}</h3>
                            @if($category->description)<p class="text-sm text-gray-500 mb-4">{{ $category->description }}</p>@endif
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2">
                                @foreach($category->data as $entry)
                                    <div class="flex justify-between items-start border-b border-gray-100 py-1.5 hover:bg-gray-50">
                                        <span class="text-gray-700 pr-4 leading-tight">{{ $entry->name }} {!! $entry->notes ? "<br><span class='text-xs text-gray-400'>{$entry->notes}</span>" : "" !!}</span>
                                        <span class="font-mono font-bold text-right flex-shrink-0">
                                            {!! HandbookController::formatReferenceValue($entry) !!} 
                                            <span class="text-gray-500 font-sans font-normal text-xs ml-1">{{ $entry->units }}</span>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>
    </div>

    <!-- Скрипт для живого поиска на вкладке 1 -->
    <script>
        function handbookSearch() {
            return {
                query: '',
                results: [],
                substance: null,
                properties: [],

                async search() {
                    this.substance = null;
                    if (this.query.length < 2) {
                        this.results = [];
                        return;
                    }
                    let res = await fetch(`/api/handbook/search?q=${encodeURIComponent(this.query)}`);
                    this.results = await res.json();
                },

                async loadSubstance(id) {
                    this.results = [];
                    let res = await fetch(`/api/handbook/substance/${id}`);
                    let data = await res.json();
                    
                    if (data.substance) {
                        this.substance = data.substance;
                        this.properties = data.properties;
                        this.query = this.substance.name;
                    }
                }
            }
        }
    </script>
</x-app-layout>