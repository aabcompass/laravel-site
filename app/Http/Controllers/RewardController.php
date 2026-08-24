<?php

namespace App\Http\Controllers;

use App\Models\Reward;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function index(Request $request)
    {
        $query = Reward::query();
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('key', 'like', "%{$request->search}%");
        }
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
        return redirect()->route('rewards.index')->with('success', "Награда '{$reward->name}' обновлена.");
    }

    public function destroy(Reward $reward)
    {
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
            'svg_content' => 'nullable|string', // <- ТЕПЕРЬ ЭТО ПРОСТО ТЕКСТ
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
}