<?php

namespace App\Http\Controllers;

use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SourceController extends Controller
{
    public function index()
    {
        $sources = Source::whereNull('parent_id')
                         ->orWhere('parent_id', 0)
                         ->orderBy('sorting_num')
                         ->with('children')
                         ->get();

        return view('sources.index', compact('sources'));
    }

    public function create(Request $request)
    {
        $allSources = Source::whereNull('parent_id')
                            ->orWhere('parent_id', 0)
                            ->orderBy('sorting_num')
                            ->with('children')
                            ->get();
        
        $selectedParentId = $request->query('parent_id', 0);

        return view('sources.create', compact('allSources', 'selectedParentId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|integer',
        ]);

        $maxSort = Source::where('parent_id', empty($request->parent_id) ? null : $request->parent_id)->max('sorting_num');

        Source::create([
            'name' => $request->name,
            'description' => $request->description,
            'parent_id' => empty($request->parent_id) ? null : $request->parent_id,
            'sorting_num' => $maxSort !== null ? $maxSort + 1 : 0,
        ]);

        return redirect()->route('sources.index')->with('success', "Новый источник '{$request->name}' успешно создан!");
    }

    public function edit(Source $source)
    {
        $allSources = Source::whereNull('parent_id')
                            ->orWhere('parent_id', 0)
                            ->orderBy('sorting_num')
                            ->with('children')
                            ->get();

        return view('sources.edit', compact('source', 'allSources'));
    }

    public function update(Request $request, Source $source)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|integer',
        ]);

        if ($request->parent_id == $source->id) {
            return back()->with('error', 'Источник не может быть родителем самого себя.');
        }

        $source->update([
            'name' => $request->name,
            'description' => $request->description,
            'parent_id' => empty($request->parent_id) ? null : $request->parent_id,
        ]);

        return redirect()->route('sources.index')->with('success', "Источник '{$source->name}' успешно обновлен.");
    }

    public function destroy(Source $source)
    {
        $name = $source->name;
        $source->delete();
        
        return redirect()->route('sources.index')->with('success', "Источник '{$name}' удален.");
    }

    public function move(Source $source, $direction)
    {
        if (!in_array($direction, ['up', 'down'])) abort(400);

        $operator = $direction === 'up' ? '<' : '>';
        $order = $direction === 'up' ? 'desc' : 'asc';

        $query = Source::query();
        if (empty($source->parent_id)) {
            $query->where(function($q) { $q->whereNull('parent_id')->orWhere('parent_id', 0); });
        } else {
            $query->where('parent_id', $source->parent_id);
        }

        $adjacent = $query->where('sorting_num', $operator, $source->sorting_num)
                          ->orderBy('sorting_num', $order)
                          ->first();

        if ($adjacent) {
            DB::transaction(function () use ($source, $adjacent) {
                $tempSort = $source->sorting_num;
                $source->update(['sorting_num' => $adjacent->sorting_num]);
                $adjacent->update(['sorting_num' => $tempSort]);
            });
        }
        return back();
    }
}