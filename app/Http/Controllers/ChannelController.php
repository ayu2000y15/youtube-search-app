<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Space;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // この行を追記
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ChannelController extends Controller
{
    use AuthorizesRequests; // この行を追記

    public function index(Request $request, Space $space)
    {
        $this->authorize('view', $space);

        // URLパラメータからメッセージを取得してセッションに設定
        if ($request->has('success')) {
            session()->flash('success', $request->get('success'));
        }
        if ($request->has('error')) {
            session()->flash('error', $request->get('error'));
        }

        $channels = $space->channels()->latest()->get();

        // 各チャンネルの処理予定件数を取得
        $totalEstimatedVideos = 0;
        $channelStats = [];
        $hasApiError = false;

        foreach ($channels as $channel) {
            $stats = $this->getChannelStats($channel->youtube_channel_id);
            $channelStats[$channel->id] = $stats;

            if (isset($stats['error'])) {
                $hasApiError = true;
            } else {
                $totalEstimatedVideos += $stats['videoCount'] ?? 0;
            }
        }

        return view('channels.index', compact('space', 'channels', 'channelStats', 'totalEstimatedVideos', 'hasApiError'));
    }

    public function create(Space $space)
    {
        $this->authorize('update', $space);
        return view('channels.create', compact('space'));
    }

    public function store(Request $request, Space $space)
    {
        $this->authorize('update', $space);

        $validated = $request->validate([
            'youtube_channel_id' => 'required|string|max:255',
        ]);

        $channelName = $this->fetchChannelName($validated['youtube_channel_id']);

        $space->channels()->create([
            'youtube_channel_id' => $validated['youtube_channel_id'],
            'name' => $channelName,
        ]);

        return redirect()->route('spaces.channels.index', $space)->with('success', '新しいチャンネルを登録しました。');
    }

    public function edit(Channel $channel)
    {
        $this->authorize('update', $channel->space);
        return view('channels.edit', compact('channel'));
    }

    public function update(Request $request, Channel $channel)
    {
        $this->authorize('update', $channel->space);

        $validated = $request->validate([
            'youtube_channel_id' => 'required|string|max:255',
        ]);

        $channelName = $this->fetchChannelName($validated['youtube_channel_id']);

        $channel->update([
            'youtube_channel_id' => $validated['youtube_channel_id'],
            'name' => $channelName,
        ]);

        return redirect()->route('spaces.channels.index', $channel->space)->with('success', 'チャンネル情報を更新しました。');
    }

    public function destroy(Channel $channel)
    {
        $this->authorize('update', $channel->space);
        $space = $channel->space;
        $channel->delete();
        return redirect()->route('spaces.channels.index', $space)->with('success', 'チャンネルを削除しました。');
    }

    /**
     * URLからチャンネルIDを検索してJSONで返す
     */
    public function findIdByUrl(Request $request)
    {
        $validated = $request->validate(['url' => 'required|url']);
        $url = $validated['url'];
        $channelId = null;

        // 1. /channel/UC... 形式のURLからIDを正規表現で抽出
        if (preg_match('/\/channel\/(UC[a-zA-Z0-9_\-]{22})/', $url, $matches)) {
            $channelId = $matches[1];
        }
        // 2. /@handle または /user/ 形式のURLの場合
        elseif (preg_match('/\/@([a-zA-Z0-9_\-\.]+)/', $url) || preg_match('/\/user\/([a-zA-Z0-9_\-\.]+)/', $url) || preg_match('/\/c\/([a-zA-Z0-9_\-\.]+)/', $url)) {
            // ページのHTMLから直接チャンネルIDを抽出する
            $channelId = $this->scrapeChannelIdFromUrl($url);
        }

        // チャンネルIDが見つからなかった場合
        if (!$channelId) {
            return response()->json(['error' => 'URLからチャンネルIDを抽出できませんでした。有効なYouTubeチャンネルのURLを入力してください。'], 422);
        }

        // 最終チェックとして、見つけたIDでチャンネル名が取得できるか試す
        try {
            $channelName = $this->fetchChannelName($channelId);
            return response()->json([
                'channel_id' => $channelId,
                'channel_name' => $channelName,
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'URLから有効なチャンネルを見つけられませんでした。'], 422);
        }
    }

    /**
     * YouTube APIを叩いてチャンネル名を取得する
     */
    private function fetchChannelName(string $channelId): string
    {
        Log::info('fetchChannelName called with ID: ' . $channelId);

        $apiKey = config('services.youtube.api_key');
        if (!$apiKey) {
            Log::error('YouTube API key not configured');
            throw ValidationException::withMessages(['youtube_channel_id' => 'YouTube APIキーが設定されていません。']);
        }

        $response = Http::withHeaders([
            'Referer' => config('app.url', 'http://localhost:8000'),
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ])->get('https://www.googleapis.com/youtube/v3/channels', [
            'part' => 'snippet',
            'id' => $channelId,
            'key' => $apiKey,
        ]);

        Log::info('YouTube API response status: ' . $response->status());
        Log::info('YouTube API response body: ' . $response->body());

        if ($response->failed() || empty($response->json('items'))) {
            Log::error('YouTube API failed or empty items. Status: ' . $response->status());
            throw ValidationException::withMessages(['youtube_channel_id' => '有効なYouTubeチャンネルIDではありません。']);
        }

        return $response->json('items.0.snippet.title');
    }

    /**
     * URL先のHTMLソースからチャンネルIDを抽出する (新しいメソッド)
     */
    private function scrapeChannelIdFromUrl(string $url): ?string
    {
        try {
            // User-Agentを設定してHTMLコンテンツを取得
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->get($url);

            if ($response->successful()) {
                $html = $response->body();

                // デバッグ用：HTMLの一部をログに出力
                Log::info('Scraping URL: ' . $url);
                Log::info('HTML length: ' . strlen($html));

                // 複数のパターンでチャンネルIDを探す
                $patterns = [
                    '/"externalId":"(UC[a-zA-Z0-9_\-]{22})"/',
                    '/"channelId":"(UC[a-zA-Z0-9_\-]{22})"/',
                    '/channel\/(UC[a-zA-Z0-9_\-]{22})/',
                    '/"browseId":"(UC[a-zA-Z0-9_\-]{22})"/',
                    '/\\\\"UC[a-zA-Z0-9_\-]{22}\\\\"/',  // エスケープされた形式
                    '/UC[a-zA-Z0-9_\-]{22}/',        // シンプルな形式
                ];

                foreach ($patterns as $index => $pattern) {
                    if (preg_match($pattern, $html, $matches)) {
                        $channelId = isset($matches[1]) ? $matches[1] : $matches[0];
                        // UCで始まらない場合は、UCで始まる部分を抽出
                        if (!str_starts_with($channelId, 'UC')) {
                            preg_match('/UC[a-zA-Z0-9_\-]{22}/', $channelId, $ucMatches);
                            $channelId = $ucMatches[0] ?? null;
                        }
                        Log::info("Pattern {$index} matched: " . $channelId);
                        Log::info("Channel ID length: " . strlen($channelId));
                        Log::info("Starts with UC: " . (str_starts_with($channelId, 'UC') ? 'true' : 'false'));
                        if ($channelId && str_starts_with($channelId, 'UC') && strlen($channelId) >= 22) {
                            Log::info("Returning channel ID: " . $channelId);
                            return $channelId;
                        } else {
                            Log::info("Channel ID validation failed");
                        }
                    }
                }

                Log::info('No channel ID patterns matched');
            } else {
                Log::error('HTTP request failed: ' . $response->status());
            }
        } catch (\Exception $e) {
            // 通信エラーなどが発生した場合はnullを返す
            Log::error('Exception in scrapeChannelIdFromUrl: ' . $e->getMessage());
            return null;
        }

        return null;
    }

    /**
     * チャンネルの統計情報を取得する
     */
    private function getChannelStats(string $channelId): array
    {
        $apiKey = config('services.youtube.api_key');
        if (!$apiKey) {
            return ['videoCount' => 0, 'error' => 'APIキー未設定'];
        }

        try {
            // チャンネルの統計情報を取得
            $response = Http::withHeaders([
                'Referer' => config('app.url', 'http://localhost:8000'),
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ])->get('https://www.googleapis.com/youtube/v3/channels', [
                'part' => 'statistics',
                'id' => $channelId,
                'key' => $apiKey,
            ]);

            if ($response->successful() && !empty($response->json('items'))) {
                $statistics = $response->json('items.0.statistics');
                return [
                    'videoCount' => (int)($statistics['videoCount'] ?? 0),
                    'subscriberCount' => (int)($statistics['subscriberCount'] ?? 0),
                    'viewCount' => (int)($statistics['viewCount'] ?? 0),
                ];
            }
        } catch (\Exception $e) {
            Log::error("チャンネル統計取得エラー: {$e->getMessage()}", ['channel_id' => $channelId]);
        }

        return ['videoCount' => 0, 'error' => '取得失敗'];
    }

    /**
     * 絞り込み条件に基づく処理件数を推定
     */
    public function estimateCount(Request $request, Space $space)
    {
        $this->authorize('view', $space);

        $validated = $request->validate([
            'date_range' => 'nullable|string|in:all,last_year,last_6months,last_3months,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'video_types' => 'nullable|array',
            'video_types.*' => 'string|in:all,video,short',
        ]);

        try {
            $channels = $space->channels;
            $totalEstimated = 0;

            foreach ($channels as $channel) {
                $channelStats = $this->getChannelStats($channel->youtube_channel_id);
                if (isset($channelStats['videoCount'])) {
                    $videoCount = $channelStats['videoCount'];

                    // 日付による絞り込み係数を適用
                    $dateFactor = $this->getDateFilterFactor($validated);

                    // 動画タイプによる絞り込み係数を適用
                    $typeFactor = $this->getVideoTypeFilterFactor($validated);

                    $estimatedForChannel = intval($videoCount * $dateFactor * $typeFactor);
                    $totalEstimated += $estimatedForChannel;
                }
            }

            return response()->json([
                'success' => true,
                'estimated_count' => $totalEstimated,
                'filter_applied' => $this->hasFiltersApplied($validated)
            ]);
        } catch (\Exception $e) {
            Log::error("処理件数推定エラー: {$e->getMessage()}", ['space_id' => $space->id]);
            return response()->json([
                'success' => false,
                'message' => '処理件数の計算に失敗しました'
            ], 500);
        }
    }

    /**
     * 日付フィルタによる絞り込み係数を取得
     */
    private function getDateFilterFactor(array $validated): float
    {
        $dateRange = $validated['date_range'] ?? 'all';

        switch ($dateRange) {
            case 'last_3months':
                return 0.1; // 過去3ヶ月なら約10%
            case 'last_6months':
                return 0.2; // 過去6ヶ月なら約20%
            case 'last_year':
                return 0.4; // 過去1年なら約40%
            case 'custom':
                // カスタム期間の場合は大まかな推定（実装簡略化）
                return 0.3;
            case 'all':
            default:
                return 1.0; // 全期間なら100%
        }
    }

    /**
     * 動画タイプフィルタによる絞り込み係数を取得
     */
    private function getVideoTypeFilterFactor(array $validated): float
    {
        $videoTypes = $validated['video_types'] ?? ['all'];

        if (in_array('all', $videoTypes)) {
            return 1.0; // すべての動画なら100%
        }

        $factor = 0.0;
        if (in_array('video', $videoTypes)) {
            $factor += 0.8; // 通常動画は約80%
        }
        if (in_array('short', $videoTypes)) {
            $factor += 0.2; // ショート動画は約20%
        }

        return $factor;
    }

    /**
     * フィルタが適用されているかチェック
     */
    private function hasFiltersApplied(array $validated): bool
    {
        $dateRange = $validated['date_range'] ?? 'all';
        $videoTypes = $validated['video_types'] ?? ['all'];

        return $dateRange !== 'all' || !in_array('all', $videoTypes);
    }
}
