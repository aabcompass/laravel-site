<?php

namespace App\Http\Controllers;

use App\Models\Reward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class RewardController extends Controller
{
    public function index(Request $request)
    {
        $query = Reward::query();

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('key', 'like', "%{$request->search}%");
        }

        // Сортируем по Зарядовому числу (как в таблице Менделеева)
        $rewards = $query->orderBy('z_number')->orderBy('a_number')->paginate(50);

        return view('rewards.index', compact('rewards'));
    }

    public function create()
    {
        return view('rewards.edit');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $this->handleCheckboxes($request, $data);

        $reward = Reward::create($data);
        $this->uploadImage($request, $reward);

        return redirect()->route('rewards.index')->with('success', "Награда '{$reward->name}' добавлена.");
    }

    public function edit(Reward $reward)
    {
        return view('rewards.edit', compact('reward'));
    }

    public function update(Request $request, Reward $reward)
    {
        $data = $request->validate($this->rules($reward->id));
        $this->handleCheckboxes($request, $data);

        $reward->update($data);
        $this->uploadImage($request, $reward);

        return redirect()->route('rewards.index')->with('success', "Награда '{$reward->name}' обновлена.");
    }

    public function destroy(Reward $reward)
    {
        if ($reward->image_path && File::exists(public_path($reward->image_path))) {
            File::delete(public_path($reward->image_path));
        }
        $reward->delete();
        return redirect()->route('rewards.index')->with('success', 'Награда удалена.');
    }

    private function rules($ignoreId = null)
    {
        $uniqueKey = 'unique:Rewards,key';
        if ($ignoreId) $uniqueKey .= ',' . $ignoreId;

        return [
            'key' => ['required', 'string', 'max:50', $uniqueKey],
            'name' => 'required|string|max:255',
            'symbol_latex' => 'nullable|string|max:255',
            'image' => 'nullable|file|mimes:svg,png,jpg,jpeg|max:2048', // Поле для загрузки файла
            'physical_desc' => 'nullable|string',
            'public_desc' => 'nullable|string',
            'private_desc' => 'nullable|string',
            'perks' => 'nullable|string',
            'carrier_type' => 'nullable|string|max:100',
            'z_number' => 'nullable|integer',
            'a_number' => 'nullable|integer',
        ];
    }

    private function handleCheckboxes(Request $request, array &$data)
    {
        $data['is_for_answer'] = $request->has('is_for_answer');
        $data['requires_registration'] = $request->has('requires_registration');
    }

    private function uploadImage(Request $request, Reward $reward)
    {
        if ($request->hasFile('image')) {
            // Удаляем старую картинку
            if ($reward->image_path && File::exists(public_path($reward->image_path))) {
                File::delete(public_path($reward->image_path));
            }

            $file = $request->file('image');
            $filename = 'reward_' . $reward->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Создаем папку, если нет
            if (!File::exists(public_path('uploads/rewards'))) {
                File::makeDirectory(public_path('uploads/rewards'), 0755, true);
            }

            $file->move(public_path('uploads/rewards'), $filename);
            $reward->update(['image_path' => 'uploads/rewards/' . $filename]);
        }
    }
}