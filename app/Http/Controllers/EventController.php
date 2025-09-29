<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest; // 作成したフォームリクエストを使用
use App\Models\Event;
use App\Models\Space; // Spaceモデルを使用
use Illuminate\Support\Facades\DB; // DBトランザクションのため
use Illuminate\Support\Facades\Log; // エラーログのため
use App\Models\EventCategory;

class EventController extends Controller
{
    // index, show, edit, update, destroy メソッドは省略

    /**
     * イベント登録フォームを表示
     */
    public function create(Space $space)
    {
        // カテゴリ一覧を取得してビューに渡す
        $categories = EventCategory::orderBy('display_order')->get();
        return view('events.create', compact('space', 'categories'));
    }

    /**
     * 新しいイベントをDBに保存
     */
    public function store(StoreEventRequest $request, Space $space)
    {
        // バリデーション済みのデータを取得
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated, $space) {
                // 1. イベント本体を登録
                // $validated を直接渡すことで、fillableな項目が全て一度に保存される
                $event = $space->events()->create($validated);

                // 2. 開催日時を登録
                if (!empty($validated['schedules'])) {
                    $event->schedules()->createMany($validated['schedules']);
                }

                // 3. チケット販売情報を登録
                if (!empty($validated['ticket_sales'])) {
                    $event->ticketSales()->createMany($validated['ticket_sales']);
                }
            });
        } catch (\Throwable $e) {
            // エラーが発生した場合はログに記録し、エラーメッセージと共にリダイレクト
            Log::error($e);
            return back()->withInput()->with('error', 'イベントの登録に失敗しました。');
        }

        // 成功したらスペース詳細ページなどにリダイレクト
        return redirect()->route('spaces.show', $space)->with('success', 'イベントを登録しました。');
    }

    /**
     * イベント編集フォームを表示
     */
    public function edit(Space $space, Event $event)
    {
        // 関連データを読み込んでおく
        $event->load(['schedules', 'ticketSales']);
        $schedulesForView = $event->schedules->map(function ($schedule) {
            // DBから取得した値（H:i:s）をそのまま使う
            // Carbonインスタンスからフォーマットする
            $schedule->performance_date = $schedule->performance_date?->format('Y-m-d');
            return $schedule;
        });

        // 元のschedules情報を、ビュー表示用にフォーマットしたもので上書きする
        $event->schedules = $schedulesForView;

        $categories = EventCategory::orderBy('display_order')->get();
        return view('events.edit', compact('space', 'event', 'categories'));
    }

    /**
     * イベントを更新
     */
    public function update(StoreEventRequest $request, Space $space, Event $event)
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated, $event) {
                // 1. イベント本体を更新
                $event->update($validated);

                // 2. 関連する開催日時・チケット情報を一旦全て削除して、再登録する
                $event->schedules()->delete();
                $event->ticketSales()->delete();

                if (!empty($validated['schedules'])) {
                    $event->schedules()->createMany($validated['schedules']);
                }
                if (!empty($validated['ticket_sales'])) {
                    $event->ticketSales()->createMany($validated['ticket_sales']);
                }
            });
        } catch (\Throwable $e) {
            Log::error($e);
            return back()->withInput()->with('error', 'イベントの更新に失敗しました。');
        }

        return redirect()->route('spaces.show', $space)->with('success', 'イベントを更新しました。');
    }

    /**
     * イベントを削除
     */
    public function destroy(Space $space, Event $event)
    {
        try {
            $event->delete();
        } catch (\Throwable $e) {
            Log::error($e);
            return back()->with('error', 'イベントの削除に失敗しました。');
        }

        return redirect()->route('spaces.show', $space)->with('success', 'イベントを削除しました。');
    }
}
