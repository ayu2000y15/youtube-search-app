<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventCategoryRequest;
use App\Http\Requests\UpdateEventCategoryRequest;
use App\Models\EventCategory;
use App\Models\Space;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EventCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = EventCategory::orderBy('display_order')->get();

        // Try to resolve space: from request param 'space' (id), or fallback to auth user's first space
        $space = null;
        $spaceId = $request->query('space');
        if ($spaceId) {
            $space = Space::find($spaceId);
        }

        if (!$space && Auth::check()) {
            $space = Auth::user()->spaces()->first();
        }

        return view('event_categories.index', compact('categories', 'space'));
    }

    public function store(StoreEventCategoryRequest $request)
    {
        EventCategory::create($request->validated());
        return back()->with('success', 'カテゴリを作成しました。');
    }

    /**
     * イベントカテゴリを更新
     */
    public function update(UpdateEventCategoryRequest $request, EventCategory $eventCategory)
    {
        $eventCategory->update($request->validated());
        return back()->with('success', 'カテゴリを更新しました。');
    }

    public function destroy(EventCategory $eventCategory)
    {
        // 注: onDelete('restrict')設定の場合、使用中のカテゴリは削除時エラーになります
        $eventCategory->delete();
        return back()->with('success', 'カテゴリを削除しました。');
    }

    /**
     * カテゴリの並び順を更新
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:event_categories,id',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->ids as $index => $id) {
                EventCategory::where('id', $id)->update([
                    'display_order' => $index
                ]);
            }
        });

        return response()->json(['status' => 'success']);
    }
}
