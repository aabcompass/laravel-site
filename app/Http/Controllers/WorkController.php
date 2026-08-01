<?php

namespace App\Http\Controllers;

use App\Models\Work;
use App\Models\Topic;
use Illuminate\Http\Request;

class WorkController extends Controller
{
    public function index(Request $request)
    {
        $query = Work::with(['topic', 'author'])->withCount('variants');

        // Фильтры
        $query->when($request->grade, fn($q, $v) => $q->where('grade', $v));
        $query->when($request->topic_id, fn($q, $v) => $q->where('topic_id', $v));
        $query->when($request->search, fn($q, $v) => $q->where('title', 'like', "%{$v}%"));

        $works = $query->orderByDesc('id')->paginate(50)->withQueryString();
        $topics = Topic::whereNull('parent_id')->orWhere('parent_id', 0)->with('children')->orderBy('sorting_num')->get();

        return view('works.index', compact('works', 'topics'));
    }

    public function create()
    {
        $topics = Topic::whereNull('parent_id')->orWhere('parent_id', 0)->with('children')->orderBy('sorting_num')->get();
        return view('works.edit', compact('topics'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'topic_id' => 'required|exists:Topics,id',
            'grade' => 'nullable|integer|min:1|max:12',
            'description' => 'nullable|string',
        ]);

        $data['author_id'] = auth()->id(); // Автором становится тот, кто создает
        Work::create($data);

        return redirect()->route('works.index')->with('success', 'Работа успешно создана!');
    }

    public function edit(Work $work)
    {
        // Проверяем права на редактирование
        if ($work->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return back()->with('error', 'Только автор или администратор может редактировать работу.');
        }

        $topics = Topic::whereNull('parent_id')->orWhere('parent_id', 0)->with('children')->orderBy('sorting_num')->get();
        return view('works.edit', compact('work', 'topics'));
    }

    public function update(Request $request, Work $work)
    {
        if ($work->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'topic_id' => 'required|exists:Topics,id',
            'grade' => 'nullable|integer|min:1|max:12',
            'description' => 'nullable|string',
        ]);

        $work->update($data);
        return redirect()->route('works.index')->with('success', 'Работа обновлена.');
    }

    public function destroy(Work $work)
    {
        // ПРАВИЛО 1: Только автор или админ
        if ($work->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return back()->with('error', 'Удалить работу может только её создатель или администратор.');
        }

        // ПРАВИЛО 2: Только если нет вариантов
        if ($work->variants()->count() > 0) {
            return back()->with('error', 'Нельзя удалить работу: в ней есть созданные варианты. Сначала удалите их.');
        }

        $work->delete();
        return redirect()->route('works.index')->with('success', 'Работа удалена.');
    }
}