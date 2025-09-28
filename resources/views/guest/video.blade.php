@extends('layouts.guest-app')

@section('content')

    <div class="py-8 px-2">
        <div class="max-w-7xl mx-auto  sm:px-6 lg:px-8">
            <!-- Page Title -->
            <div class="mb-6">
                <!-- 戻るボタン -->
                <div class="mb-4">
                    <a href="@if($space->visibility === 2)
                        {{ route('guest.space.public', $space->slug) }}
                    @else
                            {{ route('guest.space.invite', [$space->slug, $space->invite_token]) }}
                        @endif"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 hover:text-gray-900 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        <i class="fa-solid fa-chevron-left mr-2"></i>
                        <span class="text-sm font-medium">一覧に戻る</span>
                    </a>
                </div>
                <h1 class="md:text-2xl text-base font-bold text-gray-900 leading-tight">
                    <i class="fa-solid fa-video mr-2"></i>{{ $video->title }}
                </h1>
                <p class="text-xs text-gray-600 mt-2">
                    <i class="fa-solid fa-tv mr-1"></i>{{ $video->channel->name }} •
                    {{ $video->published_at->format('Y/m/d') }}
                </p>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- 動画情報 -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg sticky top-6">
                        <div class="p-6 flex flex-col">
                            @if($video->youtube_video_id)
                                <!-- YouTube埋め込みプレイヤー -->
                                <div id="video-player" class="mb-4">
                                    <div class="relative w-full" style="padding-bottom: 56.25%; height: 0;">
                                        <iframe id="youtube-iframe" class="absolute top-0 left-0 w-full h-full rounded-lg"
                                            src="https://www.youtube.com/embed/{{ $video->youtube_video_id }}?rel=0&modestbranding=1&enablejsapi=1"
                                            frameborder="0"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen>
                                        </iframe>
                                    </div>
                                    <!-- プレイヤー制御ボタン -->
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-3 gap-2">
                                        <a href="https://www.youtube.com/watch?v={{ $video->youtube_video_id }}" target="_blank"
                                            class="inline-flex items-center justify-center px-3 py-1.5 text-xs sm:text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                                            <i class="fa-brands fa-youtube mr-1"></i>
                                            <span class="hidden xs:inline">YouTubeで開く</span>
                                            <span class="xs:hidden">YouTube</span>
                                        </a>
                                        <div class="flex items-center space-x-1 sm:space-x-2">
                                            <button onclick="seekVideo(-5)"
                                                class="inline-flex items-center justify-center px-2 sm:px-3 py-1.5 text-xs sm:text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors flex-1 sm:flex-none">
                                                <i class="fa-solid fa-backward mr-1"></i>
                                                <span class="hidden xs:inline">-5秒</span>
                                                <span class="xs:hidden">-5s</span>
                                            </button>
                                            <button onclick="seekVideo(5)"
                                                class="inline-flex items-center justify-center px-2 sm:px-3 py-1.5 text-xs sm:text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors flex-1 sm:flex-none">
                                                <i class="fa-solid fa-forward mr-1"></i>
                                                <span class="hidden xs:inline">+5秒</span>
                                                <span class="xs:hidden">+5s</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @elseif($video->thumbnail_url)
                                <!-- サムネイル画像のみ（動画IDがない場合） -->
                                <div class="mb-4">
                                    <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}" class="w-full rounded-lg">
                                </div>
                            @endif

                            <!-- 基本情報 -->
                            <div class="space-y-2 text-sm mb-4 order-first lg:order-none">
                                @if($video->video_type === 'short' || ($video->playlists && $video->playlists->count() > 0))
                                    <div>
                                        <div class="flex flex-wrap gap-2">
                                            @if($video->video_type === 'short')
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-pink-100 text-pink-800 ">
                                                    <i class="fa-solid fa-wand-magic-sparkles mr-1"></i>
                                                    ショート動画
                                                </span>
                                            @endif
                                            @if($video->playlists && $video->playlists->count() > 0)
                                                @foreach($video->playlists as $playlist)
                                                    <button type="button"
                                                        onclick="searchByPlaylist('{{ $playlist->id }}', '{{ addslashes($playlist->title) }}')"
                                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 hover:bg-purple-200 transition-colors"
                                                        title="この再生リストで検索">
                                                        <i class="fa-solid fa-list mr-1"></i>
                                                        {{ $playlist->title }}
                                                    </button>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <!-- 統計情報 -->
                            @if($video->view_count || $video->like_count || $video->comment_count)
                                <div class="mb-4 grid grid-cols-3 gap-2 order-first lg:order-none">
                                    @if($video->view_count)
                                        <div class="text-center p-2 bg-red-50 rounded-lg">
                                            <div class="flex items-center justify-center mb-1">
                                                <i class="fa-solid fa-eye text-red-600 text-sm"></i>
                                            </div>
                                            <div class="text-xs font-medium text-red-700">{{ number_format($video->view_count) }}
                                            </div>
                                            <div class="text-xs text-red-600">視聴回数</div>
                                        </div>
                                    @endif
                                    @if($video->like_count)
                                        <div class="text-center p-2 bg-blue-50 rounded-lg">
                                            <div class="flex items-center justify-center mb-1">
                                                <i class="fa-solid fa-thumbs-up text-blue-600 text-sm"></i>
                                            </div>
                                            <div class="text-xs font-medium text-blue-700">{{ number_format($video->like_count) }}
                                            </div>
                                            <div class="text-xs text-blue-600">高評価</div>
                                        </div>
                                    @endif
                                    @if($video->comment_count)
                                        <div class="text-center p-2 bg-green-50 rounded-lg">
                                            <div class="flex items-center justify-center mb-1">
                                                <i class="fa-solid fa-comment text-green-600 text-sm"></i>
                                            </div>
                                            <div class="text-xs font-medium text-green-700">
                                                {{ number_format($video->comment_count) }}
                                            </div>
                                            <div class="text-xs text-green-600">コメント</div>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- 説明文 -->
                            @if($video->description)
                                <div class="border-t pt-4">
                                    <!-- モバイル向け：説明文の上に折りたたみ式の字幕エリアを追加 -->
                                    <div class="block lg:hidden mb-4">
                                        <button onclick="toggleMobileSubtitles()"
                                            class="flex items-center justify-between w-full text-left">
                                            <span class="font-medium text-gray-700">字幕</span>
                                            <i id="mobile-subtitles-icon"
                                                class="fa-solid fa-chevron-down text-gray-400 transition-transform duration-200"></i>
                                        </button>
                                        <div id="mobile-subtitles-content" class="hidden mt-2">
                                            <div class="max-h-64 overflow-y-auto space-y-3 pr-2">
                                                @foreach($dialogues as $dialogue)
                                                    <div id="mobile-dialogue-{{ $dialogue->id }}"
                                                        class="border-l-4 border-teal-600 pl-4 py-2 pr-2 hover:bg-gray-50 transition-colors">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <div class="flex items-center space-x-2">
                                                                <span class="text-sm font-medium text-teal-800">
                                                                    {{ floor($dialogue->timestamp / 60) }}:{{ sprintf('%02d', $dialogue->timestamp % 60) }}
                                                                </span>
                                                                @if($dialogue->speaker)
                                                                    <span
                                                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-20 text-amber-800">
                                                                        <i class="fa-solid fa-user mr-1"></i>
                                                                        {{ $dialogue->speaker }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            @if($video->youtube_video_id)
                                                                <button type="button" onclick="seekToTime({{ $dialogue->timestamp }})"
                                                                    class="text-xs text-gray-500 hover:text-indigo-600 flex items-center cursor-pointer transition-colors">
                                                                    <i class="fa-solid fa-play mr-1"></i>
                                                                    再生
                                                                </button>
                                                            @endif
                                                        </div>
                                                        <div class="text-sm text-gray-800 leading-relaxed">
                                                            {{ $dialogue->dialogue }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <button onclick="toggleDescription()"
                                        class="flex items-center justify-between w-full text-left">
                                        <span class="font-medium text-gray-700">説明文</span>
                                        <i id="description-icon"
                                            class="fa-solid fa-chevron-down text-gray-400 transition-transform duration-200"></i>
                                    </button>
                                    <div id="description-content"
                                        class="hidden mt-2 text-sm text-gray-600 leading-relaxed max-h-48 overflow-y-auto">
                                        @php
                                            $description = $video->description;
                                            // URLパターンをリンクに変換
                                            $pattern = '/(https?:\/\/[^\s\<\>"\'\(\)\[\]]+)/i';
                                            $replacement = '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 underline break-all">$1 <i class="fa-solid fa-external-link-alt text-xs ml-1"></i></a>';
                                            $escapedDescription = e($description);
                                            $withLinks = preg_replace($pattern, $replacement, $escapedDescription);
                                            $finalDescription = nl2br($withLinks);
                                        @endphp
                                        {!! $finalDescription !!}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 字幕 -->
                <div class="lg:col-span-1 hidden lg:block">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="font-semibold text-lg text-gray-900 mb-4">
                                <i class="fa-solid fa-comments mr-2"></i>字幕 ({{ $dialogues->count() }}件)
                            </h3>

                            @if($dialogues->count() > 0)
                                <div class=" max-h-96 overflow-y-auto space-y-4 pr-2">
                                    @foreach($dialogues as $dialogue)
                                        <div id="dialogue-{{ $dialogue->id }}"
                                            class="border-l-4 border-teal-600 pl-4 py-2 pr-2 hover:bg-gray-50 transition-colors">
                                            <div class="flex items-center justify-between mb-2">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-sm font-medium text-teal-800">
                                                        {{ floor($dialogue->timestamp / 60) }}:{{ sprintf('%02d', $dialogue->timestamp % 60) }}
                                                    </span>
                                                    @if($dialogue->speaker)
                                                        <span
                                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-20 text-amber-800">
                                                            <i class="fa-solid fa-user mr-1"></i>
                                                            {{ $dialogue->speaker }}
                                                        </span>
                                                    @endif
                                                </div>
                                                @if($video->youtube_video_id)
                                                    <button type="button" onclick="seekToTime({{ $dialogue->timestamp }})"
                                                        class="text-xs text-gray-500 hover:text-indigo-600 flex items-center cursor-pointer transition-colors">
                                                        <i class="fa-solid fa-play mr-1"></i>
                                                        この時刻で再生
                                                    </button>
                                                @endif
                                            </div>
                                            <div class="text-sm text-gray-800 leading-relaxed">
                                                {{ $dialogue->dialogue }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <div
                                        class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 mb-4">
                                        <i class="fa-solid fa-comments text-gray-400 text-xl"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">字幕がありません</h3>
                                    <p class="text-sm text-gray-500">
                                        この動画の字幕はまだ登録されていません。
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- YouTube Player API -->
    <script src="https://www.youtube.com/iframe_api"></script>

    <script>
        // ページ初期化処理
        document.addEventListener('DOMContentLoaded', function () {
            // URLにハッシュが含まれている場合、該当の字幕にスクロール
            if (window.location.hash) {
                const target = document.querySelector(window.location.hash);
                if (target) {
                    setTimeout(() => {
                        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        target.classList.add('bg-yellow-100');
                        setTimeout(() => {
                            target.classList.remove('bg-yellow-100');
                            target.classList.add('transition-colors', 'duration-1000');
                        }, 2000);
                    }, 100);
                }
            }

            // YouTube APIが既に読み込まれている場合は即座に初期化
            if (typeof YT !== 'undefined' && YT.Player) {
                onYouTubeIframeAPIReady();
            }
        });

        // ページを離れる時のクリーンアップ
        window.addEventListener('beforeunload', function () {
            stopSubtitleTracking();
        });

        // ページが非表示になった時も同期を停止
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                stopSubtitleTracking();
            } else if (playerReady && player) {
                // ページが再表示された時に同期を再開
                try {
                    const state = player.getPlayerState();
                    if (state === YT.PlayerState.PLAYING) {
                        startSubtitleTracking();
                    }
                } catch (e) {
                    console.log('Error checking player state on visibility change:', e);
                }
            }
        });

        // 説明文の展開/折りたたみ機能
        function toggleDescription() {
            const content = document.getElementById('description-content');
            const icon = document.getElementById('description-icon');

            if (content.classList.contains('hidden')) {
                // 展開
                content.classList.remove('hidden');
                icon.classList.add('rotate-180');
            } else {
                // 折りたたみ
                content.classList.add('hidden');
                icon.classList.remove('rotate-180');
            }
        }

        // YouTube Player API変数
        let player;
        let playerReady = false;
        let currentHighlightedDialogue = null;
        let subtitleTrackingInterval = null;
        // モバイル用の字幕追跡状態
        let mobileSubtitlesOpen = false;
        let mobileHighlightedDialogue = null;

        // 字幕データを配列として準備
        const dialogues = [
            @foreach($dialogues as $dialogue)
                                                                                                                                {
                    id: {{ $dialogue->id }},
                    timestamp: {{ $dialogue->timestamp }},
                    endTime: {{ $dialogue->timestamp + 5 }}, // 仮で5秒後を終了時刻とする（実際の終了時刻があれば使用）
                    speaker: @json($dialogue->speaker),
                    text: @json($dialogue->dialogue)
                },
            @endforeach
                                                                ];

        // YouTube Player APIが読み込まれた時の初期化
        function onYouTubeIframeAPIReady() {
            const iframe = document.getElementById('youtube-iframe');
            if (iframe) {
                // 既存のiframeを新しいプレイヤーで置き換える
                player = new YT.Player('youtube-iframe', {
                    videoId: '{{ $video->youtube_video_id }}',
                    playerVars: {
                        'rel': 0,
                        'modestbranding': 1,
                        'enablejsapi': 1,
                        'origin': window.location.origin
                    },
                    events: {
                        'onReady': function (event) {
                            playerReady = true;
                            console.log('YouTube Player ready');
                            // 字幕の同期開始
                            startSubtitleTracking();
                        },
                        'onStateChange': function (event) {
                            // 再生状態に応じて字幕同期を制御
                            if (event.data === YT.PlayerState.PLAYING) {
                                startSubtitleTracking();
                            } else if (event.data === YT.PlayerState.PAUSED || event.data === YT.PlayerState.ENDED) {
                                stopSubtitleTracking();
                            }
                        },
                        'onError': function (event) {
                            console.error('YouTube Player error:', event.data);
                            playerReady = false;
                            stopSubtitleTracking();
                        }
                    }
                });
            }
        }

        // 字幕の同期機能
        function startSubtitleTracking() {
            if (subtitleTrackingInterval) {
                clearInterval(subtitleTrackingInterval);
            }

            subtitleTrackingInterval = setInterval(() => {
                if (playerReady && player && typeof player.getCurrentTime === 'function') {
                    try {
                        const currentTime = Math.floor(player.getCurrentTime());
                        updateCurrentSubtitle(currentTime);
                    } catch (error) {
                        console.error('Error getting current time:', error);
                    }
                }
            }, 500); // 0.5秒ごとに更新
        }

        function stopSubtitleTracking() {
            if (subtitleTrackingInterval) {
                clearInterval(subtitleTrackingInterval);
                subtitleTrackingInterval = null;
            }
        }

        function updateCurrentSubtitle(currentTime) {
            // 現在の時刻に該当する字幕を見つける
            let currentDialogue = null;

            for (let i = 0; i < dialogues.length; i++) {
                const dialogue = dialogues[i];
                let nextDialogue = i < dialogues.length - 1 ? dialogues[i + 1] : null;

                // 現在の字幕の開始時刻以降で、次の字幕の開始時刻未満の場合
                if (currentTime >= dialogue.timestamp &&
                    (!nextDialogue || currentTime < nextDialogue.timestamp)) {
                    currentDialogue = dialogue;
                    break;
                }
            }

            // ハイライトを更新
            if (currentDialogue && currentDialogue.id !== currentHighlightedDialogue) {
                // 前のハイライトを削除
                if (currentHighlightedDialogue) {
                    const prevElement = document.getElementById(`dialogue-${currentHighlightedDialogue}`);
                    if (prevElement) {
                        prevElement.classList.remove('bg-teal-100', 'border-teal-400', 'shadow-md');
                        prevElement.classList.add('border-teal-600');
                    }
                }

                // 新しいハイライトを追加
                const currentElement = document.getElementById(`dialogue-${currentDialogue.id}`);
                if (currentElement) {
                    currentElement.classList.remove('border-teal-600');
                    currentElement.classList.add('bg-teal-100', 'border-teal-400', 'shadow-md');

                    // スムーズにスクロール
                    currentElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center',
                        inline: 'nearest'
                    });
                }

                currentHighlightedDialogue = currentDialogue.id;

                // モバイル側のハイライト／スクロール（モバイル領域が存在し、展開されている場合）
                try {
                    const mobileContainer = document.getElementById('mobile-subtitles-content');
                    if (mobileContainer && !mobileContainer.classList.contains('hidden')) {
                        // 前のモバイルハイライトを削除
                        if (mobileHighlightedDialogue) {
                            const prevMobile = document.getElementById(`mobile-dialogue-${mobileHighlightedDialogue}`);
                            if (prevMobile) {
                                prevMobile.classList.remove('bg-teal-100', 'border-teal-400', 'shadow-md');
                                prevMobile.classList.add('border-teal-600');
                            }
                        }

                        const mobileElement = document.getElementById(`mobile-dialogue-${currentDialogue.id}`);
                        if (mobileElement) {
                            mobileElement.classList.remove('border-teal-600');
                            mobileElement.classList.add('bg-teal-100', 'border-teal-400', 'shadow-md');
                            // モバイル内のスクロールを行う（要素がスクロールコンテナ内ならコンテナがスクロールされる）
                            mobileElement.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
                            mobileHighlightedDialogue = currentDialogue.id;
                        }
                    }
                } catch (e) {
                    console.log('mobile highlight error', e);
                }
            } else if (!currentDialogue && currentHighlightedDialogue) {
                // どの字幕にも該当しない場合はハイライトを削除
                const prevElement = document.getElementById(`dialogue-${currentHighlightedDialogue}`);
                if (prevElement) {
                    prevElement.classList.remove('bg-teal-100', 'border-teal-400', 'shadow-md');
                    prevElement.classList.add('border-teal-600');
                }
                currentHighlightedDialogue = null;
                // モバイル側のハイライトもクリア
                if (mobileHighlightedDialogue) {
                    const prevMobile = document.getElementById(`mobile-dialogue-${mobileHighlightedDialogue}`);
                    if (prevMobile) {
                        prevMobile.classList.remove('bg-teal-100', 'border-teal-400', 'shadow-md');
                        prevMobile.classList.add('border-teal-600');
                    }
                    mobileHighlightedDialogue = null;
                }
            }
        }

        // モバイルの折りたたみ字幕をトグル（展開中は自動スクロール）
        function toggleMobileSubtitles() {
            const content = document.getElementById('mobile-subtitles-content');
            const icon = document.getElementById('mobile-subtitles-icon');

            if (!content) return;

            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                if (icon) icon.classList.add('rotate-180');
                mobileSubtitlesOpen = true;
                // 開いた直後に現在時刻の字幕位置にスクロール
                try {
                    const currentTime = playerReady && player && typeof player.getCurrentTime === 'function' ? Math.floor(player.getCurrentTime()) : 0;
                    updateCurrentSubtitle(currentTime);
                } catch (e) {
                    console.log('error updating mobile subtitles on open', e);
                }
            } else {
                content.classList.add('hidden');
                if (icon) icon.classList.remove('rotate-180');
                mobileSubtitlesOpen = false;
                // 折りたたみ時はモバイルのハイライトをクリア
                if (mobileHighlightedDialogue) {
                    const prevMobile = document.getElementById(`mobile-dialogue-${mobileHighlightedDialogue}`);
                    if (prevMobile) {
                        prevMobile.classList.remove('bg-teal-100', 'border-teal-400', 'shadow-md');
                        prevMobile.classList.add('border-teal-600');
                    }
                    mobileHighlightedDialogue = null;
                }
            }
        }

        // シーク機能（+5秒/-5秒）
        function seekVideo(seconds) {
            console.log('seekVideo called with seconds:', seconds);

            if (playerReady && player && typeof player.getCurrentTime === 'function') {
                try {
                    const currentTime = player.getCurrentTime();
                    const newTime = Math.max(0, currentTime + seconds);
                    console.log('Seeking from', currentTime, 'to', newTime);
                    player.seekTo(newTime, true);

                    // シーク後に字幕を即座に更新
                    setTimeout(() => {
                        if (playerReady && player) {
                            const updatedTime = Math.floor(player.getCurrentTime());
                            updateCurrentSubtitle(updatedTime);
                        }
                    }, 1000);
                } catch (error) {
                    console.error('Error in seekVideo:', error);
                    // APIが利用できない場合は、新しいタブでYouTubeを開く
                    window.open(`https://www.youtube.com/watch?v={{ $video->youtube_video_id }}`, '_blank');
                }
            } else {
                console.log('Player not ready for seekVideo');
                // プレイヤーが準備できていない場合は、視覚的フィードバックのみ
                const button = event.target.closest('button');
                if (button) {
                    const originalText = button.innerHTML;
                    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> 準備中...';

                    // 少し待ってから再試行
                    setTimeout(() => {
                        if (playerReady && player) {
                            seekVideo(seconds);
                        }
                        button.innerHTML = originalText;
                    }, 1000);
                }
            }
        }

        // 指定時間へのシーク機能
        function seekToTime(timestamp) {
            console.log('seekToTime called with timestamp:', timestamp);
            console.log('playerReady:', playerReady, 'player:', player);

            if (playerReady && player && typeof player.seekTo === 'function') {
                try {
                    console.log('Seeking to:', timestamp);
                    player.seekTo(timestamp, true);

                    // 少し待ってから再生状態をチェックし、字幕も更新
                    setTimeout(() => {
                        try {
                            const state = player.getPlayerState();
                            console.log('Player state after seek:', state);
                            // 動画が一時停止されている場合は再生
                            if (state !== 1) { // 1 = playing
                                player.playVideo();
                            }

                            // 字幕を即座に更新
                            updateCurrentSubtitle(timestamp);
                        } catch (e) {
                            console.log('Error checking player state:', e);
                        }
                    }, 500);

                    return; // 成功したので関数を終了
                } catch (error) {
                    console.error('Error seeking video:', error);
                }
            }

            // プレイヤーが準備できていない場合またはエラーの場合
            const button = event.target.closest('button');
            if (button) {
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> 準備中...';

                // プレイヤーの準備を待つ
                let retryCount = 0;
                const checkPlayer = setInterval(() => {
                    retryCount++;
                    console.log('Retry count:', retryCount, 'playerReady:', playerReady);

                    if (playerReady && player && typeof player.seekTo === 'function') {
                        clearInterval(checkPlayer);
                        button.innerHTML = originalText;
                        console.log('Retrying seekToTime');
                        seekToTime(timestamp);
                    } else if (retryCount > 20) { // 10秒後にタイムアウト
                        clearInterval(checkPlayer);
                        button.innerHTML = originalText;
                        console.log('Timeout - opening in new tab');
                        // フォールバック: 新しいタブでYouTubeを開く
                        window.open(`https://www.youtube.com/watch?v={{ $video->youtube_video_id }}&t=${timestamp}s`, '_blank');
                    }
                }, 500);
            } else {
                // ボタンが見つからない場合は直接新しいタブで開く
                console.log('Button not found - opening in new tab');
                window.open(`https://www.youtube.com/watch?v={{ $video->youtube_video_id }}&t=${timestamp}s`, '_blank');
            }
        }

        // 再生リスト検索関数
        function searchByPlaylist(playlistId, playlistTitle) {
            // スペース一覧ページに戻って再生リストで検索
            const spaceUrl = @if($space->visibility === 2)
                '{{ route("guest.space.public", $space->slug) }}'
            @else
                '{{ route("guest.space.invite", [$space->slug, $space->invite_token]) }}'
            @endif;

            const url = new URL(spaceUrl);
            url.searchParams.set('playlist_id', playlistId);

            window.location.href = url.toString();
        }
    </script>
@endsection
