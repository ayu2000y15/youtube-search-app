<?php

namespace App\Http\Controllers;

use App\Models\Dialogue;
use App\Models\Video;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use MrMySQL\YoutubeTranscript\TranscriptListFetcher;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;

class DialogueController extends Controller
{
    use AuthorizesRequests;

    public function create(Video $video)
    {
        $this->authorize('update', $video->space);
        // 登録済みの文字起こしを時間順に取得
        $dialogues = $video->dialogues()->orderBy('timestamp')->get();

        // 登録済みの発言者一覧を取得（重複を除く）
        $speakers = $video->dialogues()
            ->select('speaker')
            ->whereNotNull('speaker')
            ->where('speaker', '!=', '')
            ->distinct()
            ->orderBy('speaker')
            ->pluck('speaker')
            ->toArray();

        return view('dialogues.create', compact('video', 'dialogues', 'speakers'));
    }

    public function store(Request $request, Video $video)
    {
        $this->authorize('update', $video->space);

        $validated = $request->validate([
            'timestamp' => 'required|integer|min:0',
            'speaker'   => 'nullable|string|max:255',
            'dialogue'  => 'required|string',
            'edit_mode' => 'nullable|boolean',
            'dialogue_id' => 'nullable|integer|exists:dialogues,id',
        ]);

        // 編集モードの場合
        if ($request->input('edit_mode') && $request->input('dialogue_id')) {
            $dialogue = Dialogue::findOrFail($request->input('dialogue_id'));
            $this->authorize('update', $dialogue->video->space);

            $dialogue->update([
                'timestamp' => $validated['timestamp'],
                'speaker' => $validated['speaker'],
                'dialogue' => $validated['dialogue'],
            ]);

            // AJAXリクエストの場合はJSONレスポンス
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '文字起こしを更新しました。',
                    'dialogue' => $dialogue
                ]);
            }

            return back()->with('success', '文字起こしを更新しました。');
        }

        // 新規作成の場合
        $dialogue = $video->dialogues()->create([
            'timestamp' => $validated['timestamp'],
            'speaker' => $validated['speaker'],
            'dialogue' => $validated['dialogue'],
        ]);

        // AJAXリクエストの場合はJSONレスポンス
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => '文字起こしを登録しました。',
                'dialogue' => $dialogue
            ]);
        }

        return back()->with('success', '文字起こしを登録しました。');
    }

    public function update(Request $request, Dialogue $dialogue)
    {
        $this->authorize('update', $dialogue->video->space);

        $validated = $request->validate([
            'timestamp' => 'required|integer|min:0',
            'speaker'   => 'nullable|string|max:255',
            'dialogue'  => 'required|string',
        ]);

        $dialogue->update($validated);

        // AJAXリクエストの場合はJSONレスポンス
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => '文字起こしを更新しました。',
                'dialogue' => $dialogue
            ]);
        }

        return back()->with('success', '文字起こしを更新しました。');
    }

    public function destroy(Dialogue $dialogue)
    {
        $this->authorize('update', $dialogue->video->space);
        $dialogue->delete();
        return back()->with('success', '文字起こしを削除しました。');
    }

    /**
     * まとめて発言者を更新
     */
    public function bulkUpdateSpeaker(Request $request)
    {
        $validated = $request->validate([
            'speaker' => 'required|string|max:255',
            'dialogue_ids' => 'required|array',
            'dialogue_ids.*' => 'required|integer|exists:dialogues,id',
        ]);

        // 権限チェック - すべての文字起こしに対してチェック
        foreach ($validated['dialogue_ids'] as $dialogueId) {
            $dialogue = Dialogue::findOrFail($dialogueId);
            $this->authorize('update', $dialogue->video->space);
        }

        // まとめて更新
        $updatedCount = Dialogue::whereIn('id', $validated['dialogue_ids'])
            ->update(['speaker' => $validated['speaker']]);

        return back()->with('success', "{$updatedCount}件の発言者を更新しました。");
    }

    /**
     * まとめて削除
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'dialogue_ids' => 'required|array',
            'dialogue_ids.*' => 'required|integer|exists:dialogues,id',
        ]);

        // 権限チェック - すべての文字起こしに対してチェック
        foreach ($validated['dialogue_ids'] as $dialogueId) {
            $dialogue = Dialogue::findOrFail($dialogueId);
            $this->authorize('update', $dialogue->video->space);
        }

        // まとめて削除
        $deletedCount = Dialogue::whereIn('id', $validated['dialogue_ids'])->delete();

        return back()->with('success', "{$deletedCount}件の文字起こしを削除しました。");
    }

    /**
     * YouTubeから文字起こしを取得して一括登録
     */
    public function import(Video $video)
    {
        $this->authorize('update', $video->space);

        try {
            // 実行時間を延長
            set_time_limit(300);

            Log::info("字幕取得開始: {$video->title} (ID: {$video->youtube_video_id})");

            // 方法1: 専用ライブラリを使用して字幕を取得
            $transcriptData = $this->getTranscriptUsingLibrary($video->youtube_video_id);

            if (!$transcriptData) {
                Log::info("Library method failed, trying public transcript method for video {$video->youtube_video_id}");
                // 方法2: 公開されている自動生成字幕を取得（認証不要）
                $transcriptData = $this->getPublicTranscript($video->youtube_video_id);
            }

            if (!$transcriptData) {
                Log::info("Public transcript method failed, trying alternative method for video {$video->youtube_video_id}");
                // 方法3: 代替方法
                $transcriptData = $this->getTranscriptAlternative($video->youtube_video_id);
            }

            if (!$transcriptData) {
                return back()->with('error', 'この動画には自動生成字幕が利用できません。または字幕の取得に失敗しました。詳細はログを確認してください。');
            }

            Log::info("字幕データ取得成功: " . count($transcriptData) . "件のセグメント");

            // 既存の文字起こしを削除（重複を避けるため）
            $video->dialogues()->delete();

            // 新しい文字起こしを一括登録
            $insertedCount = 0;
            foreach ($transcriptData as $segment) {
                $video->dialogues()->create([
                    'timestamp' => (int)$segment['start'],
                    'speaker' => '', // 発言者は空
                    'dialogue' => $segment['text'],
                ]);
                $insertedCount++;
            }

            return back()->with('success', "{$insertedCount}件の文字起こしをYouTubeから取得して登録しました。");
        } catch (\Exception $e) {
            Log::error('YouTube transcript import failed: ' . $e->getMessage());
            return back()->with('error', '文字起こしの取得中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * 公開されている自動生成字幕を取得（認証不要）
     */
    private function getPublicTranscript($videoId)
    {
        try {
            // YouTubeの動画ページから字幕情報を取得
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept-Language' => 'ja,en;q=0.9',
            ])->get("https://www.youtube.com/watch?v={$videoId}");

            if ($response->failed()) {
                Log::error("YouTube page fetch failed for video {$videoId}");
                return null;
            }

            $html = $response->body();
            Log::info("YouTube page fetched for video {$videoId}, content length: " . strlen($html));

            // 字幕トラック一覧を抽出（複数のパターンを試行）
            $patterns = [
                '/"captionTracks":\s*(\[.*?\])/s',
                '/"captions":\s*\{[^}]*"playerCaptionsTracklistRenderer":\s*\{[^}]*"captionTracks":\s*(\[.*?\])/s',
                '/captionTracks[\'\"]\s*:\s*(\[.*?\])/s'
            ];

            $captionTracks = null;
            $usedPattern = null;

            foreach ($patterns as $index => $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    $captionTracks = json_decode($matches[1], true);
                    $usedPattern = $index + 1;
                    Log::info("Caption tracks found using pattern {$usedPattern} for video {$videoId}");
                    break;
                }
            }

            if (!$captionTracks || empty($captionTracks)) {
                Log::warning("No caption tracks found for video {$videoId}. Trying alternative method...");

                // 代替方法：より広範囲で字幕情報を検索
                if (str_contains($html, 'captionTracks') || str_contains($html, 'captions')) {
                    Log::info("Caption-related content found in HTML, but parsing failed for video {$videoId}");
                    // HTMLの一部をログに出力（デバッグ用）
                    $captionSection = '';
                    if (preg_match('/captionTracks.*?(?=,\s*["\']|\})/s', $html, $matches)) {
                        $captionSection = substr($matches[0], 0, 500) . '...';
                    }
                    Log::info("Caption section preview: " . $captionSection);
                } else {
                    Log::info("No caption-related content found in HTML for video {$videoId}");
                }
                return null;
            }

            Log::info("Found " . count($captionTracks) . " caption tracks for video {$videoId}");
            Log::info("Caption tracks: " . json_encode($captionTracks, JSON_UNESCAPED_UNICODE));

            // 日本語字幕を優先、なければ最初の字幕を選択
            $selectedTrack = null;
            foreach ($captionTracks as $track) {
                Log::info("Checking track: " . json_encode($track, JSON_UNESCAPED_UNICODE));
                if (isset($track['languageCode']) && $track['languageCode'] === 'ja') {
                    $selectedTrack = $track;
                    Log::info("Selected Japanese track for video {$videoId}");
                    break;
                }
            }

            if (!$selectedTrack && !empty($captionTracks)) {
                $selectedTrack = $captionTracks[0];
                Log::info("Selected first available track for video {$videoId}: " . json_encode($selectedTrack, JSON_UNESCAPED_UNICODE));
            }

            if (!$selectedTrack) {
                Log::error("No track selected for video {$videoId}");
                return null;
            }

            if (!isset($selectedTrack['baseUrl'])) {
                Log::error("No baseUrl found in selected track for video {$videoId}");
                return null;
            }

            // 字幕データを取得
            $captionUrl = $selectedTrack['baseUrl'];
            Log::info("Fetching caption data from URL: {$captionUrl}");

            // Try simplified URL first (remove some parameters that might cause issues)
            $simplifiedUrl = $this->simplifyCaptionUrl($captionUrl);
            if ($simplifiedUrl !== $captionUrl) {
                Log::info("Also trying simplified URL: {$simplifiedUrl}");
            }

            // Try multiple approaches to fetch the caption data
            $captionResponse = null;

            // Try both original and simplified URLs
            $urlsToTry = [$captionUrl];
            if ($simplifiedUrl !== $captionUrl) {
                $urlsToTry[] = $simplifiedUrl;
            }

            // First try: Standard HTTP request
            foreach ($urlsToTry as $urlIndex => $currentUrl) {
                try {
                    Log::info("Attempting to fetch from URL " . ($urlIndex + 1) . ": {$currentUrl}");
                    $response = Http::withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                        'Accept' => 'text/xml,application/xml,*/*',
                        'Accept-Language' => 'ja,en;q=0.9',
                        'Cache-Control' => 'no-cache',
                        'Referer' => "https://www.youtube.com/watch?v={$videoId}",
                        'Origin' => 'https://www.youtube.com',
                    ])->timeout(30)->get($currentUrl);

                    if ($response->successful() && strlen($response->body()) > 0) {
                        $captionResponse = $response;
                        Log::info("Standard request successful with URL " . ($urlIndex + 1) . ", content length: " . strlen($response->body()));
                        break;
                    } else {
                        $bodyLength = strlen($response->body());
                        $headers = $response->headers();
                        Log::warning("Standard request failed or returned empty content with URL " . ($urlIndex + 1) . ", status: " . $response->status() . ", body length: " . $bodyLength);

                        // Log all response headers for debugging
                        foreach ($headers as $name => $value) {
                            if (is_array($value)) {
                                Log::info("Header {$name}: " . implode(', ', $value));
                            } else {
                                Log::info("Header {$name}: {$value}");
                            }
                        }

                        if ($bodyLength > 0) {
                            Log::info("Body preview: " . substr($response->body(), 0, 200));
                        }

                        // Check for redirect or error status
                        if ($response->status() >= 300 && $response->status() < 400 && isset($headers['location'])) {
                            $location = is_array($headers['location']) ? $headers['location'][0] : $headers['location'];
                            Log::info("Redirect detected to: {$location}");
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Standard request exception with URL " . ($urlIndex + 1) . ": " . $e->getMessage());
                }
            }

            // Second try: Without compression-related headers
            if (!$captionResponse) {
                foreach ($urlsToTry as $urlIndex => $currentUrl) {
                    try {
                        Log::info("Second attempt with URL " . ($urlIndex + 1) . ": {$currentUrl}");
                        $response = Http::withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                            'Accept' => 'text/xml,application/xml,text/plain,*/*',
                            'Accept-Language' => 'ja,en-US,en;q=0.5',
                            'Connection' => 'keep-alive',
                        ])->timeout(30)->get($currentUrl);

                        if ($response->successful() && strlen($response->body()) > 0) {
                            $captionResponse = $response;
                            Log::info("Second attempt successful with URL " . ($urlIndex + 1) . ", content length: " . strlen($response->body()));
                            break;
                        } else {
                            Log::warning("Second attempt failed or returned empty content with URL " . ($urlIndex + 1) . ", status: " . $response->status());
                        }
                    } catch (\Exception $e) {
                        Log::error("Second attempt exception with URL " . ($urlIndex + 1) . ": " . $e->getMessage());
                    }
                }
            }

            // Third try: Simple request without special headers
            if (!$captionResponse) {
                foreach ($urlsToTry as $urlIndex => $currentUrl) {
                    try {
                        Log::info("Simple request attempt with URL " . ($urlIndex + 1) . ": {$currentUrl}");
                        $response = Http::timeout(30)->get($currentUrl);
                        if ($response->successful() && strlen($response->body()) > 0) {
                            $captionResponse = $response;
                            Log::info("Simple request successful with URL " . ($urlIndex + 1) . ", content length: " . strlen($response->body()));
                            break;
                        } else {
                            Log::warning("Simple request failed or returned empty content with URL " . ($urlIndex + 1) . ", status: " . $response->status());
                        }
                    } catch (\Exception $e) {
                        Log::error("Simple request exception with URL " . ($urlIndex + 1) . ": " . $e->getMessage());
                    }
                }
            }

            // Fourth try: Different format parameters
            if (!$captionResponse) {
                $formatUrls = [
                    "https://www.youtube.com/api/timedtext?v={$videoId}&lang=ja&fmt=srv3",
                    "https://www.youtube.com/api/timedtext?v={$videoId}&lang=ja&fmt=srv1",
                    "https://www.youtube.com/api/timedtext?v={$videoId}&lang=ja&fmt=vtt",
                    "https://www.youtube.com/api/timedtext?v={$videoId}&lang=ja",
                    "https://www.youtube.com/api/timedtext?v={$videoId}&kind=asr&lang=ja",
                ];

                foreach ($formatUrls as $index => $formatUrl) {
                    Log::info("Trying format URL " . ($index + 1) . ": {$formatUrl}");
                    try {
                        $response = Http::withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                            'Referer' => "https://www.youtube.com/watch?v={$videoId}",
                            'Origin' => 'https://www.youtube.com',
                            'Accept' => '*/*',
                        ])->timeout(30)->get($formatUrl);

                        Log::info("Format URL " . ($index + 1) . " response: status={$response->status()}, length=" . strlen($response->body()));

                        if ($response->successful() && strlen($response->body()) > 0) {
                            $captionResponse = $response;
                            Log::info("Format URL " . ($index + 1) . " successful, content length: " . strlen($response->body()));
                            break;
                        }
                    } catch (\Exception $e) {
                        Log::error("Format URL " . ($index + 1) . " exception: " . $e->getMessage());
                    }
                }
            }

            // Fifth try: Try extracting the transcript from the original caption URL with different approach
            if (!$captionResponse) {
                Log::info("Trying direct access to caption URL with session simulation");
                try {
                    // Use cURL directly to have more control
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $captionUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Accept: text/xml,application/xml,*/*',
                        'Accept-Language: ja,en;q=0.9',
                        'Referer: https://www.youtube.com/watch?v=' . $videoId,
                        'Origin: https://www.youtube.com',
                    ]);

                    $result = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                    curl_close($ch);

                    Log::info("Direct cURL attempt: HTTP {$httpCode}, Content-Type: {$contentType}, Length: " . strlen($result));

                    if ($httpCode === 200 && !empty($result)) {
                        Log::info("Direct cURL successful, content preview: " . substr($result, 0, 300));

                        // Create a mock response object
                        $captionResponse = new class($result) {
                            private $body;
                            public function __construct($body)
                            {
                                $this->body = $body;
                            }
                            public function body()
                            {
                                return $this->body;
                            }
                            public function failed()
                            {
                                return false;
                            }
                        };
                    }
                } catch (\Exception $e) {
                    Log::error("Direct cURL exception: " . $e->getMessage());
                }
            }

            if (!$captionResponse || $captionResponse->failed()) {
                Log::error("All caption data fetch attempts failed for URL: {$captionUrl}");
                return null;
            }

            $content = $captionResponse->body();

            if (empty($content)) {
                Log::error("Caption data is empty");
                return null;
            }

            Log::info("Caption data fetched successfully, content length: " . strlen($content));
            Log::info("Content preview: " . substr($content, 0, 300));

            // Try to determine the format and parse accordingly
            $trimmedContent = trim($content);
            if (empty($trimmedContent)) {
                Log::error("Retrieved content is empty");
                return null;
            }

            // Check if it's JSON format
            if (substr($trimmedContent, 0, 1) === '{' || substr($trimmedContent, 0, 1) === '[') {
                Log::info("Detected JSON format, attempting JSON parsing");
                return $this->parseJsonTranscript($content);
            }

            // Default to XML parsing
            Log::info("Attempting XML parsing");
            return $this->parseXmlTranscript($content);
        } catch (\Exception $e) {
            Log::error("Public transcript fetch failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * XML形式の字幕をパース
     */
    private function parseXmlTranscript($xmlContent)
    {
        try {
            Log::info("Parsing XML transcript, content length: " . strlen($xmlContent));
            Log::info("Content preview: " . substr($xmlContent, 0, 500) . "...");

            // 空のコンテンツをチェック
            if (empty(trim($xmlContent))) {
                Log::error("XML content is empty or contains only whitespace");
                return null;
            }

            // libxml エラーを有効にして詳細なエラー情報を得る
            $oldValue = libxml_use_internal_errors(true);
            libxml_clear_errors();

            $xml = simplexml_load_string($xmlContent);

            if (!$xml) {
                $errors = libxml_get_errors();
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = "Line {$error->line}: {$error->message}";
                }
                Log::error("Failed to parse XML content. Errors: " . implode('; ', $errorMessages));
                libxml_use_internal_errors($oldValue);
                return null;
            }

            libxml_use_internal_errors($oldValue);

            $transcriptData = [];
            $elementCount = 0;

            foreach ($xml->text as $textElement) {
                $elementCount++;
                $start = (float)$textElement['start'];
                $text = html_entity_decode(strip_tags((string)$textElement), ENT_QUOTES | ENT_HTML5, 'UTF-8');

                if (!empty(trim($text))) {
                    $transcriptData[] = [
                        'start' => $start,
                        'text' => trim($text)
                    ];
                }

                // 最初の数個の要素をログ出力（デバッグ用）
                if ($elementCount <= 3) {
                    Log::info("Text element {$elementCount}: start={$start}, text=" . trim($text));
                }
            }

            Log::info("XML parsing completed. Found {$elementCount} text elements, extracted " . count($transcriptData) . " valid segments");
            return $transcriptData;
        } catch (\Exception $e) {
            Log::error("XML transcript parsing failed: " . $e->getMessage());
            Log::error("XML content that failed to parse: " . substr($xmlContent, 0, 1000));
            return null;
        }
    }

    /**
     * 代替方法でYouTube字幕を取得
     */
    private function getTranscriptAlternative($videoId)
    {
        try {
            Log::info("Trying alternative transcript method for video {$videoId}");

            // 異なるUser-Agentと方法で試行（Accept-Encodingを削除してcompression問題を回避）
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101 Firefox/91.0',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'ja,en-US;q=0.7,en;q=0.3',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ])->timeout(30)->get("https://www.youtube.com/watch?v={$videoId}");

            if ($response->failed()) {
                Log::error("Alternative method: YouTube page fetch failed for video {$videoId}");
                return null;
            }

            $html = $response->body();
            Log::info("Alternative method: YouTube page fetched, content length: " . strlen($html));

            // より柔軟な正規表現パターンを試行
            $alternativePatterns = [
                '/playerResponse[\'\"]\s*:\s*(\{.*?\})\s*[,;}]/',
                '/ytInitialPlayerResponse[\'\"]\s*=\s*(\{.*?\});/',
                '/var\s+ytInitialPlayerResponse\s*=\s*(\{.*?\});/',
                '/window\[\"ytInitialPlayerResponse\"\]\s*=\s*(\{.*?\});/'
            ];

            foreach ($alternativePatterns as $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    $playerResponse = json_decode($matches[1], true);
                    if ($playerResponse && isset($playerResponse['captions'])) {
                        Log::info("Alternative method: Found captions in playerResponse");
                        return $this->extractCaptionsFromPlayerResponse($playerResponse, $videoId);
                    }
                }
            }

            // 最後の手段：XMLスキーマでの直接アクセスを試行
            return $this->tryDirectXmlAccess($videoId);
        } catch (\Exception $e) {
            Log::error("Alternative transcript method failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * playerResponseから字幕を抽出
     */
    private function extractCaptionsFromPlayerResponse($playerResponse, $videoId)
    {
        try {
            if (!isset($playerResponse['captions']['playerCaptionsTracklistRenderer']['captionTracks'])) {
                Log::info("No caption tracks in playerResponse for video {$videoId}");
                return null;
            }

            $captionTracks = $playerResponse['captions']['playerCaptionsTracklistRenderer']['captionTracks'];
            Log::info("Found " . count($captionTracks) . " caption tracks in playerResponse");

            // 日本語字幕を優先
            $selectedTrack = null;
            foreach ($captionTracks as $track) {
                if (isset($track['languageCode']) && $track['languageCode'] === 'ja') {
                    $selectedTrack = $track;
                    break;
                }
            }

            if (!$selectedTrack && !empty($captionTracks)) {
                $selectedTrack = $captionTracks[0];
            }

            if (!$selectedTrack || !isset($selectedTrack['baseUrl'])) {
                Log::error("No suitable track found in playerResponse for video {$videoId}");
                return null;
            }

            // 字幕データを取得
            $captionUrl = $selectedTrack['baseUrl'];
            $captionResponse = Http::get($captionUrl);

            if ($captionResponse->successful()) {
                return $this->parseXmlTranscript($captionResponse->body());
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Failed to extract captions from playerResponse: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 直接XML URLアクセスを試行
     */
    private function tryDirectXmlAccess($videoId)
    {
        try {
            Log::info("Trying direct XML access for video {$videoId}");

            // YouTube字幕の一般的なURL構造を試行
            $directUrls = [
                "https://www.youtube.com/api/timedtext?lang=ja&v={$videoId}",
                "https://www.youtube.com/api/timedtext?lang=en&v={$videoId}",
                "https://www.youtube.com/api/timedtext?lang=ja&v={$videoId}&fmt=srv3",
                "https://www.youtube.com/api/timedtext?lang=en&v={$videoId}&fmt=srv3"
            ];

            foreach ($directUrls as $url) {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])->get($url);

                if ($response->successful() && !empty(trim($response->body()))) {
                    Log::info("Direct XML access successful with URL: {$url}");
                    $transcripts = $this->parseXmlTranscript($response->body());
                    if ($transcripts && count($transcripts) > 0) {
                        return $transcripts;
                    }
                }
            }

            Log::info("Direct XML access failed for all URLs for video {$videoId}");
            return null;
        } catch (\Exception $e) {
            Log::error("Direct XML access failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 字幕URLを簡素化（問題を起こす可能性のあるパラメータを削除）
     */
    private function simplifyCaptionUrl($url)
    {
        // URLをパースして、基本的なパラメータのみ残す
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['query'])) {
            return $url;
        }

        parse_str($parsedUrl['query'], $params);

        // 必要最小限のパラメータのみ保持
        $essentialParams = [];
        $keepParams = ['v', 'lang', 'kind', 'hl'];

        foreach ($keepParams as $param) {
            if (isset($params[$param])) {
                $essentialParams[$param] = $params[$param];
            }
        }

        // 簡素化されたクエリ文字列を構築
        $simplifiedQuery = http_build_query($essentialParams);

        // URLを再構築
        $simplifiedUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
        if (!empty($simplifiedQuery)) {
            $simplifiedUrl .= '?' . $simplifiedQuery;
        }

        return $simplifiedUrl;
    }

    /**
     * JSON形式の字幕をパース
     */
    private function parseJsonTranscript($jsonContent)
    {
        try {
            Log::info("Parsing JSON transcript, content length: " . strlen($jsonContent));

            $data = json_decode($jsonContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Failed to parse JSON content: " . json_last_error_msg());
                return null;
            }

            $transcriptData = [];

            // Handle different JSON structures that YouTube might use
            if (isset($data['events'])) {
                // srv3 format
                foreach ($data['events'] as $event) {
                    if (isset($event['tStartMs']) && isset($event['segs'])) {
                        $start = $event['tStartMs'] / 1000; // Convert to seconds
                        $text = '';
                        foreach ($event['segs'] as $seg) {
                            if (isset($seg['utf8'])) {
                                $text .= $seg['utf8'];
                            }
                        }

                        if (!empty(trim($text))) {
                            $transcriptData[] = [
                                'start' => $start,
                                'text' => trim($text)
                            ];
                        }
                    }
                }
            } elseif (isset($data['tracks'])) {
                // Alternative format
                foreach ($data['tracks'] as $track) {
                    if (isset($track['events'])) {
                        foreach ($track['events'] as $event) {
                            if (isset($event['tStartMs']) && isset($event['dDurationMs']) && isset($event['segs'])) {
                                $start = $event['tStartMs'] / 1000;
                                $text = '';
                                foreach ($event['segs'] as $seg) {
                                    if (isset($seg['utf8'])) {
                                        $text .= $seg['utf8'];
                                    }
                                }

                                if (!empty(trim($text))) {
                                    $transcriptData[] = [
                                        'start' => $start,
                                        'text' => trim($text)
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            Log::info("JSON parsing completed. Extracted " . count($transcriptData) . " segments");
            return $transcriptData;
        } catch (\Exception $e) {
            Log::error("JSON transcript parsing failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * YouTubeTranscriptライブラリを使用して字幕を取得
     */
    private function getTranscriptUsingLibrary($videoId)
    {
        try {
            Log::info("Trying YouTubeTranscript library for video {$videoId}");

            // Initialize HTTP client and factory
            $httpClient = new Client();
            $requestFactory = new HttpFactory();
            $streamFactory = new HttpFactory();

            $fetcher = new TranscriptListFetcher($httpClient, $requestFactory, $streamFactory);

            // Fetch transcript list
            $transcriptList = $fetcher->fetch($videoId);

            // Try to find Japanese transcript first
            $transcript = null;
            try {
                $transcript = $transcriptList->findTranscript(['ja']);
                Log::info("Found Japanese transcript for video {$videoId}");
            } catch (\Exception $e) {
                Log::info("Japanese transcript not found, trying other languages");
                try {
                    // Get all available language codes and try the first one
                    $availableCodes = $transcriptList->getAvailableLanguageCodes();
                    if (!empty($availableCodes)) {
                        $transcript = $transcriptList->findTranscript($availableCodes);
                        Log::info("Found transcript in language: " . implode(', ', $availableCodes));
                    }
                } catch (\Exception $e2) {
                    Log::error("No transcript found at all: " . $e2->getMessage());
                    return null;
                }
            }

            if (!$transcript) {
                Log::error("No transcript available for video {$videoId}");
                return null;
            }

            // Fetch the transcript content
            $transcriptText = $transcript->fetch();

            if (empty($transcriptText)) {
                Log::warning("YouTubeTranscript library returned empty result");
                return null;
            }

            $transcriptData = [];
            foreach ($transcriptText as $entry) {
                if (isset($entry['text']) && isset($entry['start'])) {
                    $transcriptData[] = [
                        'start' => (float)$entry['start'],
                        'text' => trim($entry['text'])
                    ];
                }
            }

            Log::info("YouTubeTranscript library successful. Extracted " . count($transcriptData) . " segments");
            return $transcriptData;
        } catch (\Exception $e) {
            Log::error("YouTubeTranscript library failed: " . $e->getMessage());
            return null;
        }
    }
}
