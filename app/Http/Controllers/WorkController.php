<?php

namespace App\Http\Controllers;

use App\Models\Work;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkController extends Controller
{
    public function index(Request $request)
    {
        session(['works_return_url' => request()->fullUrl()]);

        // Возвращаем обычный запрос (JOIN больше не нужен)
        $query = Work::with(['topic', 'author'])->withCount('variants');

        // Фильтры
        $query->when($request->grade, fn($q, $v) => $q->where('grade', $v));
        $query->when($request->topic_id, fn($q, $v) => $q->where('topic_id', $v));
        $query->when($request->search, fn($q, $v) => $q->where('title', 'like', "%{$v}%"));

        // Сортировка по нашему новому полю
        $works = $query->orderBy('sorting_num', 'asc')
                       ->orderBy('id', 'desc')
                       ->paginate(50)
                       ->withQueryString();

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

        $data['author_id'] = auth()->id(); 
        
        // Новая работа добавляется в самый конец списка
        $maxSort = Work::max('sorting_num');
        $data['sorting_num'] = $maxSort !== null ? $maxSort + 1 : 0;

        Work::create($data);

        return redirect()->route('works.index')->with('success', 'Работа успешно создана!');
    }

    public function edit(Work $work)
    {
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
        if ($work->author_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return back()->with('error', 'Удалить работу может только её создатель или администратор.');
        }

        if ($work->variants()->count() > 0) {
            return back()->with('error', 'Нельзя удалить работу: в ней есть созданные варианты. Сначала удалите их.');
        }

        $work->delete();
        return redirect()->route('works.index')->with('success', 'Работа удалена.');
    }

    // НОВЫЙ МЕТОД ДЛЯ ПЕРЕМЕЩЕНИЯ ВВЕРХ/ВНИЗ
    public function move(Work $work, $direction)
    {
        if (!in_array($direction, ['up', 'down'])) abort(400);

        $operator = $direction === 'up' ? '<' : '>';
        $order = $direction === 'up' ? 'desc' : 'asc';

        // Ищем соседнюю работу для обмена местами (меняем глобально)
        $adjacent = Work::where('sorting_num', $operator, $work->sorting_num)
                        ->orderBy('sorting_num', $order)
                        ->first();

        if ($adjacent) {
            DB::transaction(function () use ($work, $adjacent) {
                $tempSort = $work->sorting_num;
                $work->update(['sorting_num' => $adjacent->sorting_num]);
                $adjacent->update(['sorting_num' => $tempSort]);
            });
        }

        return back();
    }
}