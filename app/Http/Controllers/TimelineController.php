<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimelineRequest;
use App\Models\Space;
use App\Models\Timeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimelineController extends Controller
{
    public function index(Space $space)
    {
        $timelines = $space->timelines()->get();
        // 年表一覧画面のビューを返す (ビューは別途作成)
        return view('spaces.show', compact('space', 'timelines'));
    }

    public function create(Space $space)
    {
        // 年表作成画面のビューを返す (ビューは別途作成)
        return view('timelines.create', compact('space'));
    }

    public function store(StoreTimelineRequest $request, Space $space)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $space) {
            $timeline = $space->timelines()->create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'horizontal_axis_label' => $validated['horizontal_axis_label'],
            ]);

            $timeline->verticalAxes()->createMany($validated['vertical_axes']);
        });

        return redirect()->route('spaces.show', $space)->with('success', '年表を作成しました。');
    }

    /**
     * 年表編集フォームを表示
     */
    public function edit(Timeline $timeline)
    {
        // 縦軸の情報も一緒に読み込む
        $timeline->load('verticalAxes');
        return view('timelines.edit', compact('timeline'));
    }

    /**
     * 年表を更新
     */
    public function update(StoreTimelineRequest $request, Timeline $timeline)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $timeline) {
            // 1. 年表本体を更新
            $timeline->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'horizontal_axis_label' => $validated['horizontal_axis_label'],
            ]);

            $incomingAxes = $validated['vertical_axes'] ?? [];
            $existingAxes = $timeline->verticalAxes()->get()->keyBy('id');
            $incomingIds = [];

            // 2. 縦軸を更新または作成 (送信された順序で display_order を設定)
            foreach ($incomingAxes as $index => $axisData) {
                $id = $axisData['id'] ?? null;
                if ($id) {
                    $incomingIds[] = (int)$id;
                }

                $payload = [
                    'label' => $axisData['label'],
                    'display_order' => $index, // 配列のインデックスを表示順として設定
                ];

                if ($id && $existingAxes->has($id)) {
                    // IDが存在する場合は更新
                    $existingAxes->get($id)->update($payload);
                } else {
                    // IDがない場合は新規作成
                    $timeline->verticalAxes()->create($payload);
                }
            }

            // 3. リクエストに含まれなかった既存の軸を削除
            $deletableIds = $existingAxes->keys()->diff($incomingIds);

            if ($deletableIds->isNotEmpty()) {
                // 削除対象の軸に項目が紐づいていないかチェック
                $axesToDelete = $timeline->verticalAxes()->whereIn('id', $deletableIds)->withCount('historyEntries')->get();

                $undeletableAxes = $axesToDelete->filter(fn($axis) => $axis->history_entries_count > 0);

                if ($undeletableAxes->isNotEmpty()) {
                    // 削除できない軸がある場合、エラーメッセージを表示
                    $labels = $undeletableAxes->pluck('label')->implode(', ');
                    throw ValidationException::withMessages([
                        'vertical_axes' => '項目が紐づいているため、次の縦軸は削除できません: ' . $labels,
                    ]);
                }

                // 項目が紐づいていない軸のみ削除
                $timeline->verticalAxes()->whereIn('id', $axesToDelete->pluck('id'))->delete();
            }
        });

        return redirect()->route('spaces.show', $timeline->space_id)->with('success', '年表を更新しました。');
    }


    /**
     * 年表を削除
     */
    public function destroy(Timeline $timeline)
    {
        $space = $timeline->space;
        $timeline->delete();

        return redirect()->route('spaces.show', $space)->with('success', '年表を削除しました。');
    }

    public function show(Timeline $timeline)
    {
        // 年表に紐づく全てのエントリーを、必要な関連情報と共に一度に読み込む
        $entries = $timeline->historyEntries()
            ->with(['category', 'verticalAxis', 'videos'])
            ->get()
            ->sortBy(function ($entry) { // 先に全体を時系列でソート
                return $entry->axis_type === 'date' ? $entry->axis_date : $entry->axis_custom_value;
            });

        // カテゴリ名でグループ化
        $groupedByCategory = $entries->groupBy('category.name');

        // さらに各カテゴリ内で、縦軸ラベルによってグループ化する
        $groupedData = $groupedByCategory->map(function ($categoryEntries) {
            return $categoryEntries->groupBy('verticalAxis.label');
        });

        // 整形したデータをビューに渡す
        return view('timelines.show', compact('timeline', 'groupedData'));
    }
}
