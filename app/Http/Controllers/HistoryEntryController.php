<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHistoryEntryRequest;
use App\Models\HistoryEntry;
use App\Models\Timeline;
use Illuminate\Support\Facades\DB;

class HistoryEntryController extends Controller
{
    public function create(Timeline $timeline)
    {
        // フォームで選択肢として使うデータを取得
        $verticalAxes = $timeline->verticalAxes()->pluck('label', 'id');
        $categories = $timeline->space->historyCategories()->orderBy('display_order')->pluck('name', 'id');
        $videos = $timeline->space->videos()->pluck('title', 'id'); //

        // 年表項目作成画面のビューを返す (ビューは別途作成)
        return view('history_entries.create', compact('timeline', 'verticalAxes', 'categories', 'videos'));
    }

    public function store(StoreHistoryEntryRequest $request, Timeline $timeline)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $entry = HistoryEntry::create($validated);
            if (!empty($validated['videos'])) {
                $entry->videos()->sync($validated['videos']);
            }
        });

        return redirect()->route('timelines.show', $timeline)->with('success', '項目を登録しました。');
    }

    public function edit(HistoryEntry $history_entry)
    {
        // ルート{history_entry} による暗黙バインディングに合わせる
        $entry = $history_entry;

        // フォームで必要なデータを取得
        // 万が一、関連する縦軸が存在しない（データ不整合）場合は安全にハンドリングする
        $verticalAxis = $entry->verticalAxis;
        if (!$verticalAxis) {
            // ここは通常起きないはずだが、発生した場合は年表一覧へ戻す
            return redirect()->route('timelines.show')->with('error', '関連付けられた縦軸が見つかりません（データ不整合）。');
        }

        $timeline = $verticalAxis->timeline;
        $verticalAxes = $timeline->verticalAxes()->pluck('label', 'id');
        $categories = $timeline->space->historyCategories()->orderBy('display_order')->pluck('name', 'id');
        $videos = $timeline->space->videos()->pluck('title', 'id');

        // 関連動画をロードしておく
        $entry->load('videos');

        return view('history_entries.edit', compact('entry', 'timeline', 'verticalAxes', 'categories', 'videos'));
    }

    public function update(StoreHistoryEntryRequest $request, HistoryEntry $history_entry)
    {
        $entry = $history_entry;
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $entry) {
            $entry->update($validated);
            if (!empty($validated['videos'])) {
                $entry->videos()->sync($validated['videos']);
            } else {
                $entry->videos()->detach(); //動画が選択されなかった場合は、関連を全て解除
            }
        });

        // 更新後のリダイレクト先を安全に取得（万が一関連縦軸が消えている場合を考慮）
        $timeline = $entry->verticalAxis ? $entry->verticalAxis->timeline : null;
        if (!$timeline) {
            return redirect()->route('timelines.index')->with('success', '項目を更新しました（関連年表が見つからなかったため一覧に戻ります）。');
        }

        return redirect()->route('timelines.show', $timeline)->with('success', '項目を更新しました。');
    }

    public function destroy(HistoryEntry $history_entry)
    {
        $entry = $history_entry;
        $timeline = $entry->verticalAxis ? $entry->verticalAxis->timeline : null;
        $entry->delete();
        if ($timeline) {
            return redirect()->route('timelines.show', $timeline)->with('success', '項目を削除しました。');
        }

        return redirect()->route('timelines.index')->with('success', '項目を削除しました。');
    }
}
