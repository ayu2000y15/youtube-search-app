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
        $spaces = Auth::user()->spaces()->latest()->get();

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

        $space = Auth::user()->spaces()->create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'visibility' => $validated['visibility'],
            'invite_token' => $validated['visibility'] == 1 ? Str::random(32) : null,
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

        if ($validated['visibility'] == 1 && is_null($space->invite_token)) {
            $space->invite_token = Str::random(32);
        }

        $space->save();

        return redirect()->route('spaces.index')->with('success', 'スペースを更新しました。');
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
