<x-app-layout>
    <x-slot name="header">
        {{-- パンくずリスト --}}
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('spaces.index') }}"
                        class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                        <i class="fa-solid fa-home mr-2"></i> マイスペース
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fa-solid fa-chevron-right text-gray-400 mx-2"></i>
                        <a href="{{ route('spaces.channels.index', $video->channel->space) }}"
                            class="text-sm font-medium text-gray-700 hover:text-blue-600">
                            {{ $video->channel->space->name }}
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fa-solid fa-chevron-right text-gray-400 mx-2"></i>
                        <a href="{{ route('videos.index', $video->channel->space) }}"
                            class="text-sm font-medium text-gray-700 hover:text-blue-600">
                            動画一覧
                        </a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fa-solid fa-chevron-right text-gray-400 mx-2"></i>
                        <span class="text-sm font-medium text-gray-500">
                            詳細：{{ Str::limit($video->title, 30, '...') }}
                        </span>
                    </div>
                </li>
            </ol>
        </nav>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{-- 動画基本情報 --}}
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                        {{-- 左側: サムネイル --}}
                        <div class="lg:col-span-1">
                            <div class="relative group max-w-sm">
                                <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}"
                                    class="w-full rounded-lg shadow-md">

                                {{-- YouTubeで見るボタン --}}
                                <div
                                    class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
                                    <a href="https://www.youtube.com/watch?v={{ $video->youtube_video_id }}"
                                        target="_blank"
                                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded text-sm font-medium transition-colors">
                                        <i class="fab fa-youtube mr-1"></i>YouTube
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- 右側: 動画情報 --}}
                        <div class="lg:col-span-3">
                            <div class="space-y-6">
                                {{-- タイトル --}}
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900 leading-tight">
                                        {{ $video->title }}
                                    </h1>
                                </div>

                                {{-- タグ・種別 --}}
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($video->video_type === 'short')
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-pink-100 text-pink-800">
                                            <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> ショート動画
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                            <i class="fa-solid fa-play mr-1"></i> 通常動画
                                        </span>
                                    @endif

                                    @foreach ($video->playlists as $playlist)
                                        <a href="{{ route('videos.index', ['space' => $video->channel->space, 'playlist_id' => $playlist->id]) }}"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800 hover:bg-purple-200 transition-colors">
                                            <i class="fa-solid fa-list mr-1"></i> {{ $playlist->title }}
                                        </a>
                                    @endforeach
                                </div>

                                {{-- 基本情報 --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-3">
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fa-solid fa-calendar-alt w-5 mr-2 text-center text-gray-400"></i>
                                            <span class="font-medium mr-2">公開日:</span>
                                            <span>{{ optional($video->published_at)->format('Y年m月d日 H:i') ?? '' }}</span>
                                        </div>

                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fa-solid fa-tv w-5 mr-2 text-center text-gray-400"></i>
                                            <span class="font-medium mr-2">チャンネル:</span>
                                            <span>{{ $video->channel->name }}</span>
                                        </div>

                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fa-solid fa-hashtag w-5 mr-2 text-center text-gray-400"></i>
                                            <span class="font-medium mr-2">動画ID:</span>
                                            <span
                                                class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{ $video->youtube_video_id }}</span>
                                        </div>
                                    </div>

                                    {{-- 統計情報 --}}
                                    <div class="flex flex-wrap gap-3">
                                        @if($video->view_count !== null)
                                            <div class="inline-flex items-center px-3 py-2 rounded-full text-sm font-medium bg-red-100 text-red-800 border border-red-200">
                                                <i class="fa-solid fa-eye mr-2"></i>
                                                <span>{{ number_format($video->view_count) }}</span>
                                            </div>
                                        @endif

                                        @if($video->like_count !== null)
                                            <div class="inline-flex items-center px-3 py-2 rounded-full text-sm font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                                <i class="fa-solid fa-thumbs-up mr-2"></i>
                                                <span>{{ number_format($video->like_count) }}</span>
                                            </div>
                                        @endif

                                        @if($video->comment_count !== null)
                                            <div class="inline-flex items-center px-3 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800 border border-green-200">
                                                <i class="fa-solid fa-comments mr-2"></i>
                                                <span>{{ number_format($video->comment_count) }}</span>
                                            </div>
                                        @endif

                                        @if($video->statistics_updated_at)
                                            <div class="flex items-center text-sm text-gray-500">
                                                <i class="fa-solid fa-clock w-5 mr-2 text-center text-gray-400"></i>
                                                <span class="font-medium mr-2">統計更新:</span>
                                                <span class="text-xs">{{ optional($video->statistics_updated_at)->format('Y/m/d H:i') ?? '' }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- アクションボタン --}}
                                <div class="flex flex-wrap gap-3 pt-4 border-t">
                                    <a href="https://www.youtube.com/watch?v={{ $video->youtube_video_id }}"
                                        target="_blank"
                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-sm text-white normal-case hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <i class="fab fa-youtube mr-2"></i>YouTubeで視聴
                                    </a>

                                    <a href="{{ route('videos.dialogues.create', $video) }}"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white normal-case hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <i class="fa-solid fa-comment-dots mr-2"></i>文字起こし
                                    </a>

                                    <a href="{{ route('videos.index', $video->channel->space) }}"
                                        class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-sm text-white normal-case hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <i class="fa-solid fa-reply mr-2 fa-flip-horizontal"></i>一覧に戻る
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 動画詳細情報 --}}
                    @if($video->description)
                        <div class="mt-8 border-t pt-8">
                             {{-- 説明文 --}}
                            @if($video->description)
                                <div class="mb-6">
                                    <div class="flex items-center mb-2">
                                        <h3 class="text-md font-medium text-gray-700">説明文</h3>
                                        <button onclick="toggleDescription()" id="description-toggle"
                                            class="ml-4 text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            <i class="fa-solid fa-chevron-down mr-1"></i>表示する
                                        </button>
                                    </div>
                                    <div id="full-description" class="hidden bg-gray-50 rounded-lg p-4">
                                        <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ $video->description }}</div>
                                    </div>
                                </div>
                            @endif

                            {{-- タグ表示を無効化
                            @if($video->tags && count($video->tags) > 0)
                                <div>
                                    <h3 class="text-md font-medium text-gray-700 mb-2">タグ</h3>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($video->tags as $tag)
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-indigo-100 text-indigo-800">
                                                <i class="fa-solid fa-tag mr-1"></i>{{ $tag }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            --}}
                        </div>
                    @endif
                        </div>
                    </div>

                    {{-- 文字起こし履歴 --}}
                    @if($video->dialogues->isNotEmpty())
                        <div class="mt-12 border-t pt-8" data-dialogue-section>
                            <div class="flex items-center space-x-4 mb-6">
                                <h2 class="text-xl font-bold text-gray-900">
                                    <i class="fa-solid fa-comment-dots mr-2 text-blue-600"></i>
                                    文字起こし
                                </h2>
                                <span id="filter-info" class="text-sm text-gray-500 hidden"></span>
                                <button id="clear-filter" onclick="clearSpeakerFilter()"
                                    class="hidden px-3 py-1 bg-gray-500 text-white text-xs rounded hover:bg-gray-600 transition-colors">
                                    <i class="fa-solid fa-times mr-1"></i>絞り込み解除
                                </button>
                            </div>

                            <div class="space-y-3">
                                @foreach($video->dialogues->sortBy('timestamp') as $dialogue)
                                    <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors" data-dialogue-row>
                                        <div class="grid grid-cols-12 gap-4 items-start">
                                            {{-- 時間 --}}
                                            <div class="col-span-2">
                                                @if($dialogue->timestamp)
                                                    @php
                                                        // timestampが秒数の場合は分:秒に変換
                                                        if (is_numeric($dialogue->timestamp)) {
                                                            $minutes = floor($dialogue->timestamp / 60);
                                                            $seconds = $dialogue->timestamp % 60;
                                                            $timeFormat = sprintf('%02d:%02d', $minutes, $seconds);
                                                            $timestampSeconds = $dialogue->timestamp;
                                                        } else {
                                                            // mm:ss形式をパースして秒数に変換
                                                            $timeParts = explode(':', $dialogue->timestamp);
                                                            if (count($timeParts) >= 2) {
                                                                $timestampSeconds = (int)$timeParts[0] * 60 + (int)$timeParts[1];
                                                            } else {
                                                                $timestampSeconds = 0;
                                                            }
                                                            $timeFormat = $dialogue->timestamp;
                                                        }
                                                    @endphp
                                                    <a href="https://www.youtube.com/watch?v={{ $video->youtube_video_id }}&t={{ $timestampSeconds }}s"
                                                       target="_blank"
                                                       class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 text-xs font-mono rounded hover:bg-blue-200 transition-colors cursor-pointer"
                                                       title="YouTubeでこの時間から再生">
                                                        <i class="fa-solid fa-play mr-1"></i>
                                                        {{ $timeFormat }}
                                                    </a>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-600 text-xs font-mono rounded">
                                                        <i class="fa-solid fa-clock mr-1"></i>
                                                        00:00
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- 発言者 --}}
                                            <div class="col-span-2">
                                                <button onclick="filterBySpeaker('{{ $dialogue->speaker ?? '不明' }}')"
                                                    data-speaker="{{ $dialogue->speaker ?? '不明' }}"
                                                    class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded hover:bg-green-200 transition-colors cursor-pointer"
                                                    title="この発言者で絞り込む">
                                                    <i class="fa-solid fa-user mr-1"></i>
                                                    {{ $dialogue->speaker ?? '不明' }}
                                                </button>
                                            </div>

                                            {{-- 文字起こし --}}
                                            <div class="col-span-8">
                                                <p class="text-gray-800 text-sm leading-relaxed">{{ $dialogue->dialogue }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="mt-12 border-t pt-8">
                            <div class="text-center py-8">
                                <i class="fa-solid fa-comment-slash text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500 mb-4">まだ文字起こしがありません</p>
                                <a href="{{ route('videos.dialogues.create', $video) }}"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white normal-case hover:bg-blue-700 transition ease-in-out duration-150">
                                    <i class="fa-solid fa-plus mr-2"></i>最初の文字起こしを作成
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- 発言者絞り込み用JavaScript --}}
    <script>
        let currentFilter = null;

        function filterBySpeaker(speaker) {
            currentFilter = speaker;
            const dialogueRows = document.querySelectorAll('[data-dialogue-row]');
            const filterInfo = document.getElementById('filter-info');
            const clearButton = document.getElementById('clear-filter');

            let visibleCount = 0;

            dialogueRows.forEach(row => {
                const speakerElement = row.querySelector('[data-speaker]');
                const rowSpeaker = speakerElement ? speakerElement.textContent.trim() : '';

                if (rowSpeaker === speaker) {
                    row.style.display = 'block';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // フィルター情報を表示
            filterInfo.textContent = `「${speaker}」の発言を表示中 (${visibleCount}件)`;
            filterInfo.classList.remove('hidden');
            clearButton.classList.remove('hidden');

            // ページトップへスクロール
            document.querySelector('[data-dialogue-section]').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }

        function clearSpeakerFilter() {
            currentFilter = null;
            const dialogueRows = document.querySelectorAll('[data-dialogue-row]');
            const filterInfo = document.getElementById('filter-info');
            const clearButton = document.getElementById('clear-filter');

            dialogueRows.forEach(row => {
                row.style.display = 'block';
            });

            filterInfo.classList.add('hidden');
            clearButton.classList.add('hidden');
        }

        function toggleDescription() {
            const fullDescription = document.getElementById('full-description');
            const toggleButton = document.getElementById('description-toggle');

            if (fullDescription.classList.contains('hidden')) {
                fullDescription.classList.remove('hidden');
                toggleButton.innerHTML = '<i class="fa-solid fa-chevron-up mr-1"></i>閉じる';
            } else {
                fullDescription.classList.add('hidden');
                toggleButton.innerHTML = '<i class="fa-solid fa-chevron-down mr-1"></i>表示する';
            }
        }
    </script>
</x-app-layout>
