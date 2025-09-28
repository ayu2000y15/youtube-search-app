<?php

namespace App\Http\Controllers;

use App\Models\Space;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Authファサードをインポート
use Illuminate\Support\Str; // Strファサードをインポート

class SpaceController extends Controller
{
    /**
     * ログインユーザーのスペース一覧を表示
     */
    public function index()
    {
        // 明示的に user_id で取得して静的解析の警告を回避
        $spaces = Space::where('user_id', Auth::id())->latest()->get();

        return view('spaces.index', compact('spaces'));
    }

    /**
     * スペース作成フォームを表示
     */
    public function create()
    {
        return view('spaces.create');
    }

    /**
     * 新しいスペースをデータベースに保存
     */
    public function store(Request $request)
    {
        // バリデーションルールを定義
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:spaces,slug|regex:/^[a-zA-Z0-9\-]+$/',
            'visibility' => 'required|integer|in:0,1,2',
            'related_urls' => 'nullable|array',
            'related_urls.*.label' => 'nullable|string|max:255',
            'related_urls.*.url' => 'nullable|url|max:2048',
        ];

        // 日本語のカスタムメッセージを定義
        $messages = [
            'name.required' => 'スペース名は必ず入力してください。',
            'name.max'      => 'スペース名は255文字以内で入力してください。',
            'slug.required' => 'URL用識別子は必ず入力してください。',
            'slug.regex'    => 'URL用識別子は半角英数字とハイフンのみ使用できます。',
            'slug.unique'   => 'このURL用識別子は既に使用されています。別のものを指定してください。',
            'visibility.required' => '公開範囲を選択してください。',
        ];

        // バリデーションを実行
        $validated = $request->validate($rules, $messages);

        // related_urls の正規化:
        // - 既存データやフォームの入力でラベルのみ/URLのみが分かれている場合、可能ならペアに結合して保存する
        $related = [];
        $rawRelated = $request->input('related_urls', []);
        if (!empty($rawRelated) && is_array($rawRelated)) {
            $pendingLabel = null;
            foreach ($rawRelated as $item) {
                // 削除フラグが立っている場合は無視
                if (is_array($item) && isset($item['_delete']) && (string)$item['_delete'] === '1') {
                    continue;
                }
                // 文字列で渡されている旧形式は URL とみなす
                if (is_string($item)) {
                    $label = '';
                    $url = trim($item);
                } elseif (is_array($item)) {
                    $label = isset($item['label']) ? trim($item['label']) : '';
                    $url = isset($item['url']) ? trim($item['url']) : '';
                } else {
                    $label = '';
                    $url = '';
                }

                // 両方がある場合はそのまま確定。保留中のラベルがあれば先に保留分を空 URL で確定してから現在分を追加する。
                if ($label !== '' && $url !== '') {
                    if (!is_null($pendingLabel)) {
                        $related[] = ['label' => $pendingLabel, 'url' => ''];
                        $pendingLabel = null;
                    }
                    $related[] = ['label' => $label, 'url' => $url];
                    continue;
                }

                // ラベルのみ -> 次の URL 単体と結合するために一時保持
                if ($label !== '' && $url === '') {
                    if (!is_null($pendingLabel)) {
                        // 直前にも保留がある場合はそれを空 URL で確定
                        $related[] = ['label' => $pendingLabel, 'url' => ''];
                    }
                    $pendingLabel = $label;
                    continue;
                }

                // URL のみ
                if ($label === '' && $url !== '') {
                    if (!is_null($pendingLabel)) {
                        // 保留中のラベルと結合
                        $related[] = ['label' => $pendingLabel, 'url' => $url];
                        $pendingLabel = null;
                    } else {
                        $related[] = ['label' => '', 'url' => $url];
                    }
                    continue;
                }

                // ラベル・URL ともに空の場合は無視
            }

            // ループ後、保留中のラベルが残っていれば空 URL で確定
            if (!is_null($pendingLabel)) {
                $related[] = ['label' => $pendingLabel, 'url' => ''];
            }
        }

        $space = Space::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'visibility' => $validated['visibility'],
            'invite_token' => $validated['visibility'] == 1 ? Str::random(32) : null,
            'related_urls' => count($related) ? $related : null,
        ]);

        return redirect()->route('spaces.index')->with('success', '新しいスペースを作成しました。');
    }


    /**
     * スペース編集フォームを表示
     */
    public function edit(Space $space)
    {
        // 認可チェック: ログインユーザーがスペースの所有者でなければ403エラー
        if (Auth::id() !== $space->user_id) {
            abort(403);
        }

        return view('spaces.edit', compact('space'));
    }

    /**
     * スペース情報を更新
     */
    public function update(Request $request, Space $space)
    {
        if (Auth::id() !== $space->user_id) {
            abort(403);
        }

        // バリデーションルールを定義
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:spaces,slug,' . $space->id . '|regex:/^[a-zA-Z0-9\-]+$/',
            'visibility' => 'required|integer|in:0,1,2',
            'related_urls' => 'nullable|array',
            'related_urls.*.label' => 'nullable|string|max:255',
            'related_urls.*.url' => 'nullable|url|max:2048',
        ];

        // 日本語のカスタムメッセージを定義
        $messages = [
            'name.required' => 'スペース名は必ず入力してください。',
            'name.max'      => 'スペース名は255文字以内で入力してください。',
            'slug.required' => 'URL用識別子は必ず入力してください。',
            'slug.regex'    => 'URL用識別子は半角英数字とハイフンのみ使用できます。',
            'slug.unique'   => 'このURL用識別子は既に使用されています。別のものを指定してください。',
            'visibility.required' => '公開範囲を選択してください。',
        ];

        // バリデーションを実行
        $validated = $request->validate($rules, $messages);

        $space->name = $validated['name'];
        $space->slug = $validated['slug'];
        $space->visibility = $validated['visibility'];

        // related_urls の正規化 (store と同じロジック)
        $related = [];
        $rawRelated = $request->input('related_urls', []);
        if (!empty($rawRelated) && is_array($rawRelated)) {
            $pendingLabel = null;
            foreach ($rawRelated as $item) {
                // 削除フラグが立っている場合は無視
                if (is_array($item) && isset($item['_delete']) && (string)$item['_delete'] === '1') {
                    continue;
                }
                if (is_string($item)) {
                    $label = '';
                    $url = trim($item);
                } elseif (is_array($item)) {
                    $label = isset($item['label']) ? trim($item['label']) : '';
                    $url = isset($item['url']) ? trim($item['url']) : '';
                } else {
                    $label = '';
                    $url = '';
                }

                if ($label !== '' && $url !== '') {
                    if (!is_null($pendingLabel)) {
                        $related[] = ['label' => $pendingLabel, 'url' => ''];
                        $pendingLabel = null;
                    }
                    $related[] = ['label' => $label, 'url' => $url];
                    continue;
                }

                if ($label !== '' && $url === '') {
                    if (!is_null($pendingLabel)) {
                        $related[] = ['label' => $pendingLabel, 'url' => ''];
                    }
                    $pendingLabel = $label;
                    continue;
                }

                if ($label === '' && $url !== '') {
                    if (!is_null($pendingLabel)) {
                        $related[] = ['label' => $pendingLabel, 'url' => $url];
                        $pendingLabel = null;
                    } else {
                        $related[] = ['label' => '', 'url' => $url];
                    }
                    continue;
                }
            }

            if (!is_null($pendingLabel)) {
                $related[] = ['label' => $pendingLabel, 'url' => ''];
            }
        }

        $space->related_urls = count($related) ? $related : null;

        if ($validated['visibility'] == 1 && is_null($space->invite_token)) {
            $space->invite_token = Str::random(32);
        }

        $space->save();

        return redirect()->route('spaces.index')->with('success', 'スペースを更新しました。');
    }

    /**
     * 指定されたスペースの詳細を表示する
     */
    public function show(Space $space)
    {
        // イベント情報と一緒に、開催日時とチケット販売情報も読み込む
        $space->load('events.schedules', 'events.ticketSales', 'timelines');

        // イベントを最も早い公演日の昇順でソートする
        $space->events = $space->events->sortBy(function ($event) {
            // schedules の performance_date を集めて最小値（timestamp）を返す
            $dates = $event->schedules->pluck('performance_date')->filter();
            if ($dates->isEmpty()) {
                // スケジュール未設定のイベントは末尾に回す
                return PHP_INT_MAX;
            }

            $timestamps = $dates->map(function ($d) {
                if ($d instanceof \Carbon\Carbon) {
                    return $d->getTimestamp();
                }
                // 文字列の場合は strtotime でパース（失敗時は大きな値）
                $ts = strtotime($d);
                return $ts !== false ? $ts : PHP_INT_MAX;
            });

            return $timestamps->min();
        })->values();

        return view('spaces.show', compact('space'));
    }

    /**
     * 招待トークンを生成・再生成
     */
    public function generateInviteToken(Space $space)
    {
        if (Auth::id() !== $space->user_id) {
            abort(403);
        }

        $token = $space->generateInviteToken();

        return response()->json([
            'success' => true,
            'token' => $token,
            'url' => $space->getGuestUrl(),
            'message' => '招待リンクを生成しました。'
        ]);
    }

    /**
     * スペースを削除
     */
    public function destroy(Space $space)
    {
        if (Auth::id() !== $space->user_id) {
            abort(403);
        }

        $space->delete();

        return redirect()->route('spaces.index')->with('success', 'スペースを削除しました。');
    }
}
