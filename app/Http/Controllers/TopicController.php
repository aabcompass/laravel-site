<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Illuminate\Http\Request;

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
}