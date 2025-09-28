<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHistoryCategoryRequest;
use App\Models\HistoryCategory;
use App\Models\Space;

class HistoryCategoryController extends Controller
{
    public function index(Space $space)
    {
        $categories = $space->historyCategories()->orderBy('display_order')->get();
        // カテゴリ管理画面のビューを返す (ビューは別途作成)
        return view('history_categories.index', compact('space', 'categories'));
    }

    public function store(StoreHistoryCategoryRequest $request, Space $space)
    {
        $space->historyCategories()->create($request->validated());
        return back()->with('success', 'カテゴリを登録しました。');
    }
}
