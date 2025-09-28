<?php

namespace App\Jobs;

use App\Models\Space;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SyncVideosJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 1800; // 30分のタイムアウト
    public $tries = 3; // 失敗時のリトライ回数

    protected $space;
    protected $userId;
    protected $progressKey;
    protected $maxVideosPerSync;
    protected $syncSettings;

    /**
     * Create a new job instance.
     */
    public function __construct(Space $space, int $userId, array $syncSettings = [])
    {
        $this->space = $space;
        $this->userId = $userId;
        $this->progressKey = "sync_progress_{$space->id}_{$userId}";
        $this->maxVideosPerSync = config('services.youtube.max_videos_per_sync', 1000);
        $this->syncSettings = $syncSettings;

        // 進捗状態を初期化
        Cache::put($this->progressKey, [
            'status' => 'queued',
            'progress' => 0,
            'message' => '同期処理をキューに追加しました',
            'current_task' => '開始待ち',
            'started_at' => now()->toISOString(),
        ], 3600); // 1時間保持
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("バックグラウンド同期開始: Space ID {$this->space->id}");

        $this->updateProgress(5, '同期処理を開始しています', 'チャンネル情報を確認中');

        $apiKey = config('services.youtube.api_key');
        if (!$apiKey) {
            $this->failSync('YouTube APIキーが設定されていません。');
            return;
        }

        $channels = $this->space->channels;
        if ($channels->isEmpty()) {
            $this->completeSync('動画を同期するチャンネルがありません。', []);
            return;
        }

        $syncedPlaylistsCount = 0;
        $newPlaylistsCount = 0;
        $syncedVideosCount = 0;
        $newVideosCount = 0;
        $updatedVideosCount = 0;
        $startTime = microtime(true);

        try {
            // ステップ1: 再生リスト同期
            $this->updateProgress(10, '再生リストを同期中', "チャンネル数: {$channels->count()}");

            foreach ($channels as $channelIndex => $channel) {
                $progress = 10 + (($channelIndex / $channels->count()) * 30); // 10-40%
                $this->updateProgress($progress, '再生リストを同期中', "チャンネル: {$channel->name}");

                $pageToken = null;
                $channelPlaylistCount = 0;

                do {
                    $playlistsResponse = Http::timeout(30)->withHeaders([
                        'Referer' => config('app.url', 'http://localhost:8000'),
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
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
                        $playlist = $this->space->playlists()->updateOrCreate(
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
                } while ($pageToken);

                Log::info("チャンネル {$channel->name}: {$channelPlaylistCount}件の再生リストを同期");
            }

            // ステップ2: 動画同期
            $allPlaylistsInSpace = $this->space->playlists;
            $channelIdMap = $channels->keyBy('youtube_channel_id');
            $processedVideoIds = [];
            $totalProcessedVideos = 0; // 処理済み動画数カウンター

            $this->updateProgress(40, '動画情報を同期中', "再生リスト数: {$allPlaylistsInSpace->count()}");

            foreach ($allPlaylistsInSpace as $playlistIndex => $playlist) {
                $progress = 40 + (($playlistIndex / $allPlaylistsInSpace->count()) * 50); // 40-90%
                $this->updateProgress($progress, '動画情報を同期中', "再生リスト: {$playlist->title}");

                $pageToken = null;
                $playlistVideoCount = 0;

                do {
                    $itemsResponse = Http::timeout(30)->withHeaders([
                        'Referer' => config('app.url', 'http://localhost:8000'),
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
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
                        // 最大処理件数チェック
                        if ($totalProcessedVideos >= $this->maxVideosPerSync) {
                            Log::info("最大処理件数 {$this->maxVideosPerSync} に達したため同期を停止します");
                            break 3; // 3つのループから抜ける（foreach、do-while、foreach）
                        }

                        if ($videoData['snippet']['title'] === 'Private video' || !isset($videoData['snippet']['resourceId']['videoId'])) {
                            continue;
                        }

                        $youtubeVideoId = $videoData['snippet']['resourceId']['videoId'];

                        if (isset($processedVideoIds[$youtubeVideoId])) {
                            $existingVideo = $this->space->videos()->where('youtube_video_id', $youtubeVideoId)->first();
                            if ($existingVideo) {
                                $existingVideo->playlists()->syncWithoutDetaching($playlist->id);
                            }
                            continue;
                        }

                        $channel = $channelIdMap->get($videoData['snippet']['channelId']);
                        if (!$channel) {
                            continue;
                        }

                        $videoType = (str_contains(strtolower($videoData['snippet']['title']), '#shorts')) ? 'short' : 'video';
                        $publishedAt = \Carbon\Carbon::parse($videoData['snippet']['publishedAt']);

                        // 日付フィルタリング
                        if (!$this->isDateInRange($publishedAt)) {
                            continue;
                        }

                        // 動画タイプフィルタリング
                        if (!$this->isVideoTypeAllowed($videoType)) {
                            continue;
                        }

                        // 動画の詳細情報を取得
                        $videoDetailsResponse = Http::timeout(30)->withHeaders([
                            'Referer' => config('app.url', 'http://localhost:8000'),
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
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
                            }
                        }

                        $video = $this->space->videos()->updateOrCreate(
                            ['youtube_video_id' => $videoData['snippet']['resourceId']['videoId']],
                            [
                                'channel_id' => $channel->id,
                                'title' => $videoData['snippet']['title'],
                                'thumbnail_url' => $videoData['snippet']['thumbnails']['high']['url'] ?? $videoData['snippet']['thumbnails']['default']['url'],
                                'published_at' => \Carbon\Carbon::parse($videoData['snippet']['publishedAt']),
                                'video_type' => $videoType,
                                'view_count' => $statistics['viewCount'] ?? null,
                                'like_count' => $statistics['likeCount'] ?? null,
                                'comment_count' => $statistics['commentCount'] ?? null,
                                'description' => $videoDetails['description'] ?? null,
                                'tags' => null,
                                'category_id' => $videoDetails['categoryId'] ?? null,
                                'language' => $videoDetails['defaultLanguage'] ?? $videoDetails['defaultAudioLanguage'] ?? null,
                                'statistics_updated_at' => now(),
                            ]
                        );

                        if ($video->wasRecentlyCreated) {
                            $newVideosCount++;
                        } else {
                            $updatedVideosCount++;
                        }

                        $video->playlists()->syncWithoutDetaching($playlist->id);
                        $processedVideoIds[$youtubeVideoId] = true;
                        $syncedVideosCount++;
                        $playlistVideoCount++;
                        $totalProcessedVideos++; // 処理済み動画数を増やす
                    }

                    $pageToken = $itemsResponse->json('nextPageToken');
                } while ($pageToken);

                Log::info("再生リスト {$playlist->title}: {$playlistVideoCount}件の動画を同期");
            }

            // 完了処理
            $this->updateProgress(95, '最終処理中', 'データの整合性をチェック中');

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            $summary = [
                'playlists' => ['synced' => $syncedPlaylistsCount, 'new' => $newPlaylistsCount],
                'videos' => ['synced' => $syncedVideosCount, 'new' => $newVideosCount, 'updated' => $updatedVideosCount],
                'execution_time' => $executionTime,
                'max_videos_limit' => $this->maxVideosPerSync,
                'limit_reached' => $totalProcessedVideos >= $this->maxVideosPerSync
            ];

            $limitMessage = $totalProcessedVideos >= $this->maxVideosPerSync ? "（制限 {$this->maxVideosPerSync}件に達成）" : "";

            // フィルタ情報を追加
            $filterInfo = $this->getFilterDescription();
            $filterMessage = $filterInfo ? "（{$filterInfo}）" : "";

            $message = "動画同期が完了しました{$limitMessage}{$filterMessage}。再生リスト: {$syncedPlaylistsCount}件（新規: {$newPlaylistsCount}件）、動画: {$syncedVideosCount}件（新規: {$newVideosCount}件、更新: {$updatedVideosCount}件）実行時間: {$executionTime}秒";

            $this->completeSync($message, $summary);
        } catch (\Exception $e) {
            Log::error("バックグラウンド同期エラー: {$e->getMessage()}", [
                'space_id' => $this->space->id,
                'exception' => $e
            ]);
            $this->failSync("同期処理中にエラーが発生しました: {$e->getMessage()}");
        }
    }

    protected function updateProgress(float $progress, string $message, string $currentTask): void
    {
        Cache::put($this->progressKey, [
            'status' => 'processing',
            'progress' => $progress,
            'message' => $message,
            'current_task' => $currentTask,
            'updated_at' => now()->toISOString(),
        ], 3600);
    }

    protected function completeSync(string $message, array $summary): void
    {
        Cache::put($this->progressKey, [
            'status' => 'completed',
            'progress' => 100,
            'message' => $message,
            'current_task' => '完了',
            'summary' => $summary,
            'completed_at' => now()->toISOString(),
        ], 3600);

        Log::info("バックグラウンド同期完了: Space ID {$this->space->id}", $summary);
    }

    protected function failSync(string $message): void
    {
        Cache::put($this->progressKey, [
            'status' => 'failed',
            'progress' => 0,
            'message' => $message,
            'current_task' => 'エラー',
            'failed_at' => now()->toISOString(),
        ], 3600);

        Log::error("バックグラウンド同期失敗: Space ID {$this->space->id}", ['message' => $message]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->failSync("同期処理が失敗しました: {$exception->getMessage()}");
    }

    /**
     * 日付が指定範囲内かチェック
     */
    private function isDateInRange(\Carbon\Carbon $publishedAt): bool
    {
        $dateRange = $this->syncSettings['date_range'] ?? 'all';

        if ($dateRange === 'all') {
            return true;
        }

        $now = now();
        $cutoffDate = null;

        switch ($dateRange) {
            case 'last_year':
                $cutoffDate = $now->copy()->subYear();
                break;
            case 'last_6months':
                $cutoffDate = $now->copy()->subMonths(6);
                break;
            case 'last_3months':
                $cutoffDate = $now->copy()->subMonths(3);
                break;
            case 'custom':
                $startDate = isset($this->syncSettings['start_date']) ?
                    \Carbon\Carbon::parse($this->syncSettings['start_date']) : null;
                $endDate = isset($this->syncSettings['end_date']) ?
                    \Carbon\Carbon::parse($this->syncSettings['end_date'])->endOfDay() : null;

                if ($startDate && $publishedAt->lt($startDate)) {
                    return false;
                }
                if ($endDate && $publishedAt->gt($endDate)) {
                    return false;
                }
                return true;
        }

        return $cutoffDate ? $publishedAt->gte($cutoffDate) : true;
    }

    /**
     * 動画タイプが許可されているかチェック
     */
    private function isVideoTypeAllowed(string $videoType): bool
    {
        $allowedTypes = $this->syncSettings['video_types'] ?? ['all'];

        if (in_array('all', $allowedTypes)) {
            return true;
        }

        return in_array($videoType, $allowedTypes);
    }

    /**
     * フィルタ設定の説明文を生成
     */
    private function getFilterDescription(): string
    {
        $descriptions = [];

        // 日付フィルタの説明
        $dateRange = $this->syncSettings['date_range'] ?? 'all';
        switch ($dateRange) {
            case 'last_year':
                $descriptions[] = '過去1年';
                break;
            case 'last_6months':
                $descriptions[] = '過去6ヶ月';
                break;
            case 'last_3months':
                $descriptions[] = '過去3ヶ月';
                break;
            case 'custom':
                $start = $this->syncSettings['start_date'] ?? null;
                $end = $this->syncSettings['end_date'] ?? null;
                if ($start && $end) {
                    $descriptions[] = "{$start}〜{$end}";
                } elseif ($start) {
                    $descriptions[] = "{$start}以降";
                } elseif ($end) {
                    $descriptions[] = "{$end}以前";
                }
                break;
        }

        // 動画タイプフィルタの説明
        $videoTypes = $this->syncSettings['video_types'] ?? ['all'];
        if (!in_array('all', $videoTypes)) {
            $typeLabels = [];
            if (in_array('video', $videoTypes)) {
                $typeLabels[] = '通常動画';
            }
            if (in_array('short', $videoTypes)) {
                $typeLabels[] = 'ショート動画';
            }
            if (!empty($typeLabels)) {
                $descriptions[] = implode('・', $typeLabels) . 'のみ';
            }
        }

        return implode('、', $descriptions);
    }
}
