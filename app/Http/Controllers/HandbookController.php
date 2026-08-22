<?php

namespace App\Http\Controllers;

use App\Models\ReferenceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HandbookController extends Controller
{
    public function index()
    {
        // 1. Свойства веществ (Плотность, Теплоемкость и т.д.)
        $propertiesRaw = DB::select("
            SELECT pp.name as property_name, pp.units as property_units, s.name as substance_name, s.state as substance_state, pv.value 
            FROM Property_Values pv
            JOIN Physical_Properties pp ON pv.property_id = pp.id
            JOIN Substances s ON pv.substance_id = s.id
            ORDER BY pp.id, s.name
        ");
        
        $propertiesByGroup = [];
        foreach ($propertiesRaw as $row) {
            $propertiesByGroup[$row->property_name][] = $row;
        }

        // 2. Общие справочные данные
        $referenceCategories = ReferenceCategory::with('data')->orderBy('sorting_num')->get();

        // 3. Психрометрическая таблица (группируем по температуре сухого термометра)
        $psychrometricRaw = DB::table('Psychrometric_Data')->orderBy('dry_bulb_temp')->orderBy('temp_difference')->get();
        $psychrometricData = [];
        foreach ($psychrometricRaw as $row) {
            $psychrometricData[$row->dry_bulb_temp][$row->temp_difference] = $row->relative_humidity;
        }

        // 4. Насыщенный пар
        $vaporData = DB::table('Saturated_Vapor_Properties_H2O')->orderBy('temperature_celsius')->get();

        return view('handbook.index', compact('propertiesByGroup', 'referenceCategories', 'psychrometricData', 'vaporData'));
    }

    // --- API: ЖИВОЙ ПОИСК ВЕЩЕСТВА ---
    public function search(Request $request)
    {
        $query = trim($request->get('q', ''));
        if (mb_strlen($query) < 2) return response()->json([]);

        $substances = DB::table('Substances')
            ->where('name', 'like', "%{$query}%")
            ->limit(10)
            ->get();
            
        return response()->json($substances);
    }

    // --- API: ПОЛУЧЕНИЕ СВОЙСТВ ВЕЩЕСТВА ---
    public function getSubstance($id)
    {
        $substance = DB::table('Substances')->where('id', $id)->first();
        if (!$substance) return response()->json(['error' => 'Not found'], 404);

        $properties = DB::table('Property_Values as pv')
            ->join('Physical_Properties as pp', 'pv.property_id', '=', 'pp.id')
            ->where('pv.substance_id', $id)
            ->select('pp.name', 'pp.symbol', 'pp.units', 'pv.value', 'pv.notes')
            ->orderBy('pp.name')
            ->get();

        // Форматируем значения перед отправкой в браузер
        foreach ($properties as $prop) {
            $prop->formatted_value = self::formatScientific($prop->value);
        }

        return response()->json([
            'substance' => $substance,
            'properties' => $properties
        ]);
    }

    // --- ХЕЛПЕРЫ ФОРМАТИРОВАНИЯ ---
    public static function formatScientific($number, $precision = 7)
    {
        if ($number == 0) return "0";
        $cleaned = rtrim(rtrim(sprintf('%.' . $precision . 'f', $number), '0'), '.');
        $abs = abs((float)$cleaned);
        
        if ($abs < 0.00001 || $abs >= 1000000) {
            $scientific = sprintf('%.2E', $number);
            list($mantissa, $exponent) = explode('E', $scientific);
            $map = ['0'=>'⁰','1'=>'¹','2'=>'²','3'=>'³','4'=>'⁴','5'=>'⁵','6'=>'⁶','7'=>'⁷','8'=>'⁸','9'=>'⁹'];
            $expStr = (string)abs((int)$exponent);
            $superExp = strtr($expStr, $map);
            if ((int)$exponent < 0) $superExp = '⁻' . $superExp;
            return str_replace('.', ',', $mantissa) . '·10' . $superExp;
        }
        return str_replace('.', ',', $cleaned);
    }

    public static function formatReferenceValue($entry)
    {
        if (!empty($entry->value_text)) {
            $parts = explode(';', $entry->value_text);
            $html = '';
            foreach ($parts as $part) {
                $html .= '<span class="inline-block mr-4 last:mr-0">' . htmlspecialchars(trim($part)) . '</span>';
            }
            return $html;
        }
        if ($entry->value_max !== null) {
            return self::formatScientific($entry->value_min) . ' – ' . self::formatScientific($entry->value_max);
        }
        if ($entry->value_min !== null) {
            return self::formatScientific($entry->value_min);
        }
        return '—';
    }
}