<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TopicController extends Controller
{
    public function index()
    {
        // Получаем только темы верхнего уровня (где parent_id = null или 0)
        // И сразу "жадно" подгружаем их дочерние темы (with('children'))
        $topics = Topic::whereNull('parent_id')
                       ->orWhere('parent_id', 0)
                       ->orderBy('sorting_num')
                       ->with('children')
                       ->get();

        // Передаем данные в шаблон
        return view('topics.index', compact('topics'));
    }

    public function edit(Topic $topic)
    {
        // Достаем всё дерево тем для выпадающего списка "Родительская тема"
        $allTopics = Topic::whereNull('parent_id')
                          ->orWhere('parent_id', 0)
                          ->orderBy('sorting_num')
                          ->with('children')
                          ->get();

        return view('topics.edit', compact('topic', 'allTopics'));
    }

    public function update(Request $request, Topic $topic)
    {
        // 1. Валидация (Laravel сам проверит данные и вернет ошибки, если что не так)
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|integer',
        ]);

        // 2. Защита "от дурака" (нельзя сделать тему родителем самой себя)
        if ($request->parent_id == $topic->id) {
            return back()->with('error', 'Тема не может быть родительской для самой себя.');
        }

        // 3. Обновление
        $topic->update([
            'name' => $request->name,
            'description' => $request->description,
            'parent_id' => empty($request->parent_id) ? null : $request->parent_id,
        ]);

        // 4. Редирект обратно с сообщением об успехе
        return redirect()->route('topics.index')->with('success', "Тема '{$topic->name}' успешно обновлена.");
    }

    public function destroy(Topic $topic)
    {
        $name = $topic->name;
        $topic->delete(); // В БД сработает ON DELETE SET NULL для детей автоматически!
        
        return redirect()->route('topics.index')->with('success', "Тема '{$name}' удалена.");
    }

    public function create(Request $request)
    {
        $allTopics = Topic::whereNull('parent_id')
                          ->orWhere('parent_id', 0)
                          ->orderBy('sorting_num')
                          ->with('children')
                          ->get();
        
        // Если мы кликнули "Добавить подтему", сюда прилетит parent_id
        $selectedParentId = $request->query('parent_id', 0);

        return view('topics.create', compact('allTopics', 'selectedParentId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|integer',
        ]);

        // Как и в вашем старом коде, находим максимальный sorting_num для новой темы
        $maxSort = Topic::where('parent_id', empty($request->parent_id) ? null : $request->parent_id)->max('sorting_num');

        Topic::create([
            'name' => $request->name,
            'description' => $request->description,
            'parent_id' => empty($request->parent_id) ? null : $request->parent_id,
            'sorting_num' => $maxSort !== null ? $maxSort + 1 : 0,
        ]);

        return redirect()->route('topics.index')->with('success', "Новая тема '{$request->name}' успешно создана!");
    }

    public function move(Topic $topic, $direction)
    {
        // Защита от неверных параметров
        if (!in_array($direction, ['up', 'down'])) {
            abort(400);
        }

        $operator = $direction === 'up' ? '<' : '>';
        $order = $direction === 'up' ? 'desc' : 'asc';

        // Ищем "соседа", с которым нужно поменяться местами
        $query = Topic::query();
        
        // Учитываем, что parent_id может быть 0 или null
        if (empty($topic->parent_id)) {
            $query->where(function($q) {
                $q->whereNull('parent_id')->orWhere('parent_id', 0);
            });
        } else {
            $query->where('parent_id', $topic->parent_id);
        }

        // Берем ближайшего соседа в нужном направлении
        $adjacentTopic = $query->where('sorting_num', $operator, $topic->sorting_num)
                               ->orderBy('sorting_num', $order)
                               ->first();

        // Если сосед найден - меняем их sorting_num местами в транзакции
        if ($adjacentTopic) {
            DB::transaction(function () use ($topic, $adjacentTopic) {
                $tempSort = $topic->sorting_num;
                $topic->update(['sorting_num' => $adjacentTopic->sorting_num]);
                $adjacentTopic->update(['sorting_num' => $tempSort]);
            });
        }

        // Возвращаемся обратно на страницу (без лишних уведомлений)
        return back();
    }
}