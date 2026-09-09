<?php

namespace App\Http\Controllers;

use App\Models\Work;
use App\Models\Topic;
use Illuminate\Http\Request;

class WorkController extends Controller
{
    public function index(Request $request)
    {
        // 1. Указываем select('Works.*'), чтобы при JOIN не перезаписался ID работы на ID темы
        $query = Work::select('Works.*')
            ->with(['topic', 'author'])
            ->withCount('variants')
            // 2. Джоиним таблицу тем, чтобы получить доступ к её полям для сортировки
            ->join('Topics', 'Works.topic_id', '=', 'Topics.id');

        // 3. Фильтры (обязательно добавляем префикс 'Works.', чтобы избежать ошибки SQL "ambiguous column")
        $query->when($request->grade, fn($q, $v) => $q->where('Works.grade', $v));
        $query->when($request->topic_id, fn($q, $v) => $q->where('Works.topic_id', $v));
        $query->when($request->search, fn($q, $v) => $q->where('Works.title', 'like', "%{$v}%"));

        // 4. Сортируем: сначала по порядку тем, затем по ID самой работы (на случай совпадений)
        $works = $query->orderBy('Topics.sorting_num', 'asc')
                       ->orderBy('Works.id', 'desc')
                       ->paginate(50)
                       ->withQueryString();

        $topics = Topic::whereNull('parent_id')
                       ->orWhere('parent_id', 0)
                       ->with('children')
                       ->orderBy('sorting_num')
                       ->get();

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