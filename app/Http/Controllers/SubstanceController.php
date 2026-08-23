<?php

namespace App\Http\Controllers;

use App\Models\Substance;
use App\Models\PhysicalProperty;
use App\Models\PropertyValue;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubstanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Substance::withCount('propertyValues');

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $substances = $query->orderBy('name')->paginate(50)->withQueryString();

        return view('substances.index', compact('substances'));
    }

    public function create()
    {
        // Достаем все возможные физические свойства, чтобы построить сетку полей
        $properties = PhysicalProperty::orderBy('name')->get();
        return view('substances.edit', compact('properties'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:Substances,name',
            'state' => 'nullable|in:solid,liquid,gas',
            'props' => 'nullable|array' // Массив переданных свойств
        ]);

        $substance = Substance::create($data);
        $this->syncProperties($substance, $request->input('props', []));

        return redirect()->route('substances.index')->with('success', "Вещество '{$substance->name}' добавлено.");
    }

    public function edit(Substance $substance)
    {
        $properties = PhysicalProperty::orderBy('name')->get();
        // Достаем уже сохраненные значения и делаем ключом массива property_id
        $values = $substance->propertyValues->keyBy('property_id');

        return view('substances.edit', compact('substance', 'properties', 'values'));
    }

    public function update(Request $request, Substance $substance)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('Substances')->ignore($substance->id)],
            'state' => 'nullable|in:solid,liquid,gas',
            'props' => 'nullable|array'
        ]);

        $substance->update($data);
        $this->syncProperties($substance, $request->input('props', []));

        return redirect()->route('substances.index')->with('success', "Вещество '{$substance->name}' обновлено.");
    }

    public function destroy(Substance $substance)
    {
        $substance->delete(); // Связанные значения удалятся через ON DELETE CASCADE в базе
        return redirect()->route('substances.index')->with('success', 'Вещество удалено.');
    }

    /**
     * Умное сохранение свойств
     */
    private function syncProperties(Substance $substance, array $props)
    {
        foreach ($props as $propId => $data) {
            $val = $data['value'] ?? null;
            $notes = $data['notes'] ?? null;

            if ($val !== null && $val !== '') {
                // Заменяем запятую на точку для правильного сохранения в базу
                $val = str_replace(',', '.', $val);
                
                PropertyValue::updateOrCreate(
                    ['substance_id' => $substance->id, 'property_id' => $propId],
                    ['value' => $val, 'notes' => $notes]
                );
            } else {
                // Если поле пустое, удаляем запись из базы (очистка)
                PropertyValue::where('substance_id', $substance->id)
                             ->where('property_id', $propId)
                             ->delete();
            }
        }
    }
}