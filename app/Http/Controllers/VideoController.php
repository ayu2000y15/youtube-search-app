<?php

namespace App\Http\Controllers;

use App\Models\Space;
use App\Models\Video;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoController extends Controller
{
    use AuthorizesRequests;

    /**
     * 特定スペースに属する動画一覧を表示 (絞り込み機能を追加)
     */
    public function index(Request $request, Space $space)
    {
        $this->authorize('view', $space);

        // 絞り込み用の再生リスト一覧を取得
        $playlists = $space->playlists()->orderBy('title')->get();

        // 動画クエリのベースを作成
        $query = $space->videos()->with(['channel', 'playlists']); // N+1問題対策

        // 再生リストによる絞り込み
        if ($request->filled('playlist_id')) {
            $query->whereHas('playlists', function ($q) use ($request) {
                $q->where('playlists.id', $request->playlist_id);
            });
        }

        // 動画種別による絞り込み
        if ($request->filled('video_type')) {
            if ($request->video_type === 'video') {
                // 'video'が選択された場合は、'video'またはnullのものを検索
                $query->where(function ($q) {
                    $q->where('video_type', 'video')
                        ->orWhereNull('video_type');
                });
            } else {
                $query->where('video_type', $request->video_type);
            }
        }

        // キーワード検索
        if ($request->filled('keyword')) {
            $query->where('title', 'LIKE', '%' . $request->keyword . '%');
        }

        // 期間検索（全期間チェックが外れている場合のみ）
        if (!$request->filled('all_period') || !$request->all_period) {
            if ($request->filled('date_from')) {
                $query->whereDate('published_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('published_at', '<=', $request->date_to);
            }
        }

        // 並び順
        $sort = $request->input('sort', 'newest'); // デフォルトは新しい順
        switch ($sort) {
            case 'oldest':
                $query->oldest('published_at');
                break;
            case 'view_count_desc':
                $query->orderByDesc('view_count');
                break;
            case 'view_count_asc':
                $query->orderBy('view_count');
                break;
            case 'like_count_desc':
                $query->orderByDesc('like_count');
                break;
            case 'like_count_asc':
                $query->orderBy('like_count');
                break;
            case 'comment_count_desc':
                $query->orderByDesc('comment_count');
                break;
            case 'comment_count_asc':
                $query->orderBy('comment_count');
                break;
            case 'newest':
            default:
                $query->latest('published_at');
                break;
        }

        $videos = $query->paginate(100)->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('videos._video_list', compact('videos'))->render(),
                'next_page_url' => $videos->nextPageUrl()
            ]);
        }

        return view('videos.index', compact('space', 'videos', 'playlists'));
    }

    /**
     * 動画詳細を表示
     */
    public function show(Video $video)
    {
        $this->authorize('view', $video->channel->space);

        // 動画の詳細情報をロード
        $video->load(['channel', 'playlists', 'dialogues']);

        return view('videos.show', compact('video'));
    }

    /**
     * 指定されたスペースのプレイリストと動画情報を同期する (タイムアウト対策版)
     */
    public function sync(Space $space)
    {
        $this->authorize('update', $space);

        // 実行時間制限を5分に延長
        set_time_limit(300);

        // メモリ制限を1GBに増加
        ini_set('memory_limit', '1G');

        $apiKey = config('services.youtube.api_key');
        if (!$apiKey) {
            return back()->with('error', 'YouTube APIキーが設定されていません。');
        }

        $channels = $space->channels;
        if ($channels->isEmpty()) {
            return back()->with('success', '動画を同期するチャンネルがありません。');
        }

        $syncedPlaylistsCount = 0;
        $newPlaylistsCount = 0;
        $syncedVideosCount = 0;
        $newVideosCount = 0;
        $updatedVideosCount = 0;
        $startTime = microtime(true);

        try {
            // --- ステップ1: 各チャンネルの再生リストを同期 ---
            Log::info("開始: 再生リスト同期 (チャンネル数: {$channels->count()})");

            foreach ($channels as $channelIndex => $channel) {
                Log::info("チャンネル {$channelIndex}/{$channels->count()}: {$channel->name}");

                $pageToken = null;
                $channelPlaylistCount = 0;

                do {
                    $playlistsResponse = Http::timeout(30)->withHeaders([
                        'Referer' => config('app.url', 'http://localhost:8000'),
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                    ])->get('https://www.googleapis.com/youtube/v3/playlists', [
                        'part' => 'snippet',
                        'channelId' => $channel->youtube_channel_id,
                        'maxResults' => 50,
                        'pageToken' => $pageToken,
                        'key' => $apiKey,
                    ]);
                    $playlistsResponse->throw();

                    $items = $playlistsResponse->json('items', []);
                    foreach ($items as $playlistData) {
                        $playlist = $space->playlists()->updateOrCreate(
                            ['youtube_playlist_id' => $playlistData['id']],
                            ['title' => $playlistData['snippet']['title']]
                        );

                        if ($playlist->wasRecentlyCreated) {
                            $newPlaylistsCount++;
                        }
                        $channelPlaylistCount++;
                        $syncedPlaylistsCount++;
                    }

                    $pageToken = $playlistsResponse->json('nextPageToken');

                    // 進捗ログ
                    if ($channelPlaylistCount % 10 === 0) {
                        Log::info("再生リスト同期中: {$channelPlaylistCount}件処理完了");
                    }
                } while ($pageToken);

                Log::info("チャンネル {$channel->name}: {$channelPlaylistCount}件の再生リストを同期");
            }

            // --- ステップ2: 各再生リストの動画を同期 ---
            $allPlaylistsInSpace = $space->playlists;
            $existingVideosCount = $space->videos()->count();
            Log::info("開始: 動画同期 (再生リスト数: {$allPlaylistsInSpace->count()}, 既存動画数: {$existingVideosCount})");

            // チャンネルIDマップを事前作成（パフォーマンス向上）
            $channelIdMap = $channels->keyBy('youtube_channel_id');

            // 処理済み動画IDを追跡（同じ動画が複数の再生リストに含まれる場合の重複処理を避けるため）
            $processedVideoIds = [];

            foreach ($allPlaylistsInSpace as $playlistIndex => $playlist) {
                Log::info("再生リスト {$playlistIndex}/{$allPlaylistsInSpace->count()}: {$playlist->title}");

                $pageToken = null;
                $playlistVideoCount = 0;

                do {
                    // 実行時間チェック（4分経過したら処理を停止）
                    if (microtime(true) - $startTime > 240) {
                        Log::warning('実行時間制限により処理を中断しました');
                        throw new \Exception('処理時間が長くなりすぎたため中断しました。再度実行してください。');
                    }

                    $itemsResponse = Http::timeout(30)->withHeaders([
                        'Referer' => config('app.url', 'http://localhost:8000'),
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                    ])->get('https://www.googleapis.com/youtube/v3/playlistItems', [
                        'part' => 'snippet',
                        'playlistId' => $playlist->youtube_playlist_id,
                        'maxResults' => 50,
                        'pageToken' => $pageToken,
                        'key' => $apiKey,
                    ]);
                    $itemsResponse->throw();

                    $items = $itemsResponse->json('items', []);
                    foreach ($items as $videoData) {
                        if ($videoData['snippet']['title'] === 'Private video' || !isset($videoData['snippet']['resourceId']['videoId'])) {
                            continue;
                        }

                        $youtubeVideoId = $videoData['snippet']['resourceId']['videoId'];

                        // 既に処理済みの動画の場合は、再生リストとの紐付けのみ行う
                        if (isset($processedVideoIds[$youtubeVideoId])) {
                            $existingVideo = $space->videos()->where('youtube_video_id', $youtubeVideoId)->first();
                            if ($existingVideo) {
                                $existingVideo->playlists()->syncWithoutDetaching($playlist->id);
                                Log::info("既存動画の再生リスト紐付け: {$videoData['snippet']['title']}");
                            }
                            continue;
                        }

                        // チャンネルIDを効率的に取得
                        $channel = $channelIdMap->get($videoData['snippet']['channelId']);
                        if (!$channel) {
                            Log::warning("チャンネルIDが見つかりません: {$videoData['snippet']['channelId']}");
                            continue;
                        }

                        // 動画種別を判定（タイトルに #shorts が含まれるか）
                        $videoType = (str_contains(strtolower($videoData['snippet']['title']), '#shorts')) ? 'short' : 'video';

                        // 動画の詳細情報（統計情報含む）を取得
                        $videoDetailsResponse = Http::timeout(120)->withHeaders([
                            'Referer' => config('app.url', 'http://localhost:8000'),
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                        ])->get('https://www.googleapis.com/youtube/v3/videos', [
                            'part' => 'snippet,statistics',
                            'id' => $videoData['snippet']['resourceId']['videoId'],
                            'key' => $apiKey,
                        ]);

                        $videoDetails = null;
                        $statistics = null;
                        if ($videoDetailsResponse->successful()) {
                            $videoDetailsData = $videoDetailsResponse->json('items.0');
                            if ($videoDetailsData) {
                                $videoDetails = $videoDetailsData['snippet'] ?? null;
                                $statistics = $videoDetailsData['statistics'] ?? null;

                                // デバッグログ: 動画詳細情報を確認
                                Log::info("動画詳細取得: {$videoData['snippet']['title']}", [
                                    'video_id' => $videoData['snippet']['resourceId']['videoId'],
                                    'description_length' => isset($videoDetails['description']) ? strlen($videoDetails['description']) : 0,
                                    'category_id' => $videoDetails['categoryId'] ?? 'カテゴリなし',
                                    'view_count' => $statistics['viewCount'] ?? null,
                                    'like_count' => $statistics['likeCount'] ?? null
                                ]);
                            } else {
                                Log::warning("動画詳細データが空: {$videoData['snippet']['resourceId']['videoId']}");
                            }
                        } else {
                            Log::error("動画詳細取得失敗: {$videoData['snippet']['resourceId']['videoId']}", [
                                'status' => $videoDetailsResponse->status(),
                                'response' => $videoDetailsResponse->json()
                            ]);
                        }

                        $video = $space->videos()->updateOrCreate(
                            [
                                'youtube_video_id' => $videoData['snippet']['resourceId']['videoId']
                            ],
                            [
                                'channel_id' => $channel->id,
                                'title' => $videoData['snippet']['title'],
                                'thumbnail_url' => $videoData['snippet']['thumbnails']['high']['url'] ?? $videoData['snippet']['thumbnails']['default']['url'],
                                'published_at' => \Carbon\Carbon::parse($videoData['snippet']['publishedAt']),
                                'video_type' => $videoType,
                                // 統計情報
                                'view_count' => $statistics['viewCount'] ?? null,
                                'like_count' => $statistics['likeCount'] ?? null,
                                'comment_count' => $statistics['commentCount'] ?? null,
                                // 詳細情報
                                'description' => $videoDetails['description'] ?? null,
                                'tags' => null, // タグの取得を無効化
                                'category_id' => $videoDetails['categoryId'] ?? null,
                                'language' => $videoDetails['defaultLanguage'] ?? $videoDetails['defaultAudioLanguage'] ?? null,
                                'statistics_updated_at' => now(),
                            ]
                        );

                        // 新規作成か更新かを判定
                        if ($video->wasRecentlyCreated) {
                            $newVideosCount++;
                            Log::info("新規動画作成: {$videoData['snippet']['title']} (ID: {$videoData['snippet']['resourceId']['videoId']})");
                        } else {
                            $updatedVideosCount++;
                            Log::info("動画更新: {$videoData['snippet']['title']} (ID: {$videoData['snippet']['resourceId']['videoId']})");
                        }

                        // 動画と再生リストを紐付ける
                        $video->playlists()->syncWithoutDetaching($playlist->id);

                        // 処理済み動画IDとして記録
                        $processedVideoIds[$youtubeVideoId] = true;

                        $playlistVideoCount++;
                        $syncedVideosCount++;
                    }

                    $pageToken = $itemsResponse->json('nextPageToken');

                    // 進捗ログ
                    if ($playlistVideoCount % 20 === 0) {
                        Log::info("動画同期中: {$playlistVideoCount}件処理完了");
                    }
                } while ($pageToken);

                Log::info("再生リスト {$playlist->title}: {$playlistVideoCount}件の動画を同期");
            }

            $executionTime = round(microtime(true) - $startTime, 2);
            Log::info("同期完了: 実行時間 {$executionTime}秒");

            // データベースに保存されている実際の件数を取得
            $totalPlaylistsInDb = $space->playlists()->count();
            $totalVideosInDb = $space->videos()->count();

            // 詳細なメッセージを作成
            $playlistMessage = "再生リスト: {$syncedPlaylistsCount}件処理 (新規: {$newPlaylistsCount}件)";
            $videoMessage = "動画: {$syncedVideosCount}件処理 (新規登録: {$newVideosCount}件, 更新: {$updatedVideosCount}件)";
            $message = "{$playlistMessage}, {$videoMessage} (実行時間: {$executionTime}秒)";

            // AJAX リクエストの場合は JSON を返す
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'playlists_processed' => $syncedPlaylistsCount,
                        'playlists_new' => $newPlaylistsCount,
                        'playlists_total_in_db' => $totalPlaylistsInDb,
                        'videos_processed' => $syncedVideosCount,
                        'videos_new' => $newVideosCount,
                        'videos_updated' => $updatedVideosCount,
                        'videos_total_in_db' => $totalVideosInDb,
                        'execution_time' => $executionTime
                    ]
                ]);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 2);
            $errorMessage = $e->getMessage() . " (実行時間: {$executionTime}秒)";

            Log::error("YouTube情報の同期に失敗しました (実行時間: {$executionTime}秒): " . $e->getMessage());

            // AJAX リクエストの場合は JSON を返す
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => $e->getMessage()
                ], 500);
            }

            return back()->with('error', $errorMessage);
        }
    }

    /**
     * バックグラウンドで動画同期を開始
     */
    public function syncBackground(Request $request, Space $space)
    {
        $this->authorize('update', $space);

        // 絞り込み設定をバリデーション
        $validated = $request->validate([
            'date_range' => 'nullable|string|in:all,last_year,last_6months,last_3months,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'video_types' => 'nullable|array',
            'video_types.*' => 'string|in:all,video,short',
        ]);

        $apiKey = config('services.youtube.api_key');
        if (!$apiKey) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'YouTube APIキーが設定されていません。'
                ], 400);
            }
            return back()->with('error', 'YouTube APIキーが設定されていません。');
        }

        $channels = $space->channels;
        if ($channels->isEmpty()) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '動画を同期するチャンネルがありません。'
                ], 400);
            }
            return back()->with('error', '動画を同期するチャンネルがありません。');
        }

        // 既に進行中の同期があるかチェック（時間制限付き）
        $progressKey = "sync_progress_{$space->id}_" . Auth::id();
        $currentProgress = \Illuminate\Support\Facades\Cache::get($progressKey);

        if ($currentProgress && in_array($currentProgress['status'], ['queued', 'processing'])) {
            // 開始から一定時間（2時間）経過している場合は自動でクリア
            $startedAt = isset($currentProgress['started_at']) ?
                \Carbon\Carbon::parse($currentProgress['started_at']) : null;

            if ($startedAt && $startedAt->diffInHours(now()) > 2) {
                \Illuminate\Support\Facades\Cache::forget($progressKey);
                Log::info("古い同期進捗を自動クリア: Space ID {$space->id}, User ID " . Auth::id());
            } else {
                if (request()->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => '既に同期処理が実行中です。完了をお待ちいただくか、「進捗クリア」ボタンで強制的にリセットしてください。'
                    ], 409);
                }
                return back()->with('error', '既に同期処理が実行中です。完了をお待ちいただくか、「進捗クリア」ボタンで強制的にリセットしてください。');
            }
        }

        // バックグラウンドジョブをキューに追加（設定付き）
        \App\Jobs\SyncVideosJob::dispatch($space, Auth::id(), $validated);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'バックグラウンドで動画同期を開始しました。',
                'progress_key' => $progressKey
            ]);
        }

        return back()->with('success', 'バックグラウンドで動画同期を開始しました。進捗はこのページで確認できます。');
    }

    /**
     * 同期進捗を取得
     */
    public function syncProgress(Space $space)
    {
        $this->authorize('view', $space);

        $progressKey = "sync_progress_{$space->id}_" . Auth::id();
        $progress = \Illuminate\Support\Facades\Cache::get($progressKey);

        if (!$progress) {
            return response()->json([
                'status' => 'not_found',
                'message' => '同期処理の情報が見つかりません。'
            ], 404);
        }

        return response()->json($progress);
    }

    /**
     * 同期進捗をクリア（強制停止）
     */
    public function clearSyncProgress(Space $space)
    {
        $this->authorize('update', $space);

        $progressKey = "sync_progress_{$space->id}_" . Auth::id();
        \Illuminate\Support\Facades\Cache::forget($progressKey);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => '同期進捗をクリアしました。'
            ]);
        }

        return back()->with('success', '同期進捗をクリアしました。再度同期を実行できます。');
    }
}
