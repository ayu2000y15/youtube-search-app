<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - {{ $space->name ?? '' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        <!-- Navigation -->
        <nav id="navbar"
            class="fixed top-0 left-0 right-0 bg-white border-b border-gray-200 shadow-sm z-50 transition-transform duration-300 ease-in-out">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <!-- Logo/Space Name -->
                        <div class="flex-shrink-0 flex items-center">
                            <!-- Visibility Status -->
                            <div class="ml-2 flex items-center">
                                @if(isset($space))
                                    <div class="flex items-center">
                                        <span class="mr-2 px-2 sm:px-3 py-1 text-xs sm:text-sm rounded-full font-medium
                                                                                @if($space->visibility === 2)
                                                                                    bg-green-100 text-green-800
                                                                                @else
                                                                                    bg-yellow-100 text-yellow-800
                                                                                @endif">
                                            @if($space->visibility === 2)
                                                <i class="fa-solid fa-globe"></i>
                                                <span class="hidden ml-1 sm:inline">全体公開</span>
                                            @else
                                                <i class="fa-solid fa-link "></i>
                                                <span class="hidden ml-1 sm:inline">限定公開</span>
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <h1 class="text-xl font-bold text-gray-900 hidden sm:block">
                                {{ $space->name ?? 'YouTube検索' }}
                            </h1>
                            <h1 class="text-base font-bold text-gray-900 sm:hidden truncate max-w-40">
                                {{ $space->name ?? 'YouTube検索' }}
                            </h1>
                        </div>
                    </div>

                    <!-- Search Button -->
                    <div class="flex items-center">
                        <button id="search-toggle" onclick="toggleSearchPanel()"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <i class="fa-solid fa-search"></i>
                            <span class="ml-2 hidden sm:inline">検索</span>
                        </button>
                    </div>

                </div>
            </div>
        </nav>

        <!-- Search Panel (Hidden by default) -->
        <div id="search-panel"
            class="fixed top-16 left-0 right-0 bg-white border-b border-gray-200 shadow-lg z-40 transform -translate-y-full transition-all duration-300 ease-in-out"
            style="display: none;">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:max-h-full max-h-120 overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fa-solid fa-search mr-2"></i>詳細検索
                    </h3>
                    <button onclick="toggleSearchPanel()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>

                <form action="@if(isset($space))
                    @if($space->visibility === 2)
                        {{ route('guest.space.public', $space->slug) }}
                    @else
                        {{ route('guest.space.invite', [$space->slug, $space->invite_token]) }}
                    @endif
                @else
                    #
                @endif" method="GET" class="space-y-4">

                    <!-- キーワード検索 -->
                    <div>
                        <label for="search-query" class="block text-sm font-medium text-gray-700 mb-2">キーワード検索</label>
                        <input type="text" name="keyword" id="search-query" value="{{ request('keyword') }}"
                            class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="キーワードを入力してください...">

                        <!-- 検索対象選択 -->
                        <div class="mt-3">
                            <label class="block text-sm font-medium text-gray-700 mb-2">キーワード検索対象</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <label class="flex items-center">
                                    <input type="checkbox" name="search_targets[]" value="dialogue"
                                        {{ in_array('dialogue', request('search_targets', ['title', 'description', 'dialogue', 'playlist'])) ? 'checked' : '' }}
                                        class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 focus:ring-2">
                                    <span class="ml-2 text-sm text-gray-700">字幕</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="search_targets[]" value="title"
                                        {{ in_array('title', request('search_targets', ['title', 'description', 'dialogue', 'playlist'])) ? 'checked' : '' }}
                                        class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 focus:ring-2">
                                    <span class="ml-2 text-sm text-gray-700">タイトル</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="search_targets[]" value="description"
                                        {{ in_array('description', request('search_targets', ['title', 'description', 'dialogue', 'playlist'])) ? 'checked' : '' }}
                                        class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 focus:ring-2">
                                    <span class="ml-2 text-sm text-gray-700">説明</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="search_targets[]" value="playlist"
                                        {{ in_array('playlist', request('search_targets', ['title', 'description', 'dialogue', 'playlist'])) ? 'checked' : '' }}
                                        class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 focus:ring-2">
                                    <span class="ml-2 text-sm text-gray-700">再生リスト名</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 再生リストフィルター -->
                    @if(isset($space) && $space->playlists && $space->playlists->count() > 0)
                        <div>
                            <label for="playlist-filter" class="block text-sm font-medium text-gray-700 mb-2">再生リスト</label>
                            <select id="playlist-filter" name="playlist_id"
                                class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">すべての再生リスト</option>
                                @foreach($space->playlists as $playlist)
                                    <option value="{{ $playlist->id }}" {{ request('playlist_id') == $playlist->id ? 'selected' : '' }}>
                                        {{ $playlist->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- 動画種別フィルター -->
                    <div>
                        <label for="video-type-filter" class="block text-sm font-medium text-gray-700 mb-2">動画種別</label>
                        <select id="video-type-filter" name="video_type"
                            class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="">すべて</option>
                            <option value="video" {{ request('video_type') === 'video' ? 'selected' : '' }}>通常動画</option>
                            <option value="short" {{ request('video_type') === 'short' ? 'selected' : '' }}>ショート</option>
                        </select>
                    </div>

                    <!-- 公開日フィルター -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">公開日</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="date-from" class="block text-xs font-medium text-gray-600 mb-1">開始日</label>
                                <input type="date" name="date_from" id="date-from" value="{{ request('date_from') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                            </div>
                            <div>
                                <label for="date-to" class="block text-xs font-medium text-gray-600 mb-1">終了日</label>
                                <input type="date" name="date_to" id="date-to" value="{{ request('date_to') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                            </div>
                        </div>
                        <div class="mt-2">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="all_period" id="all-period" value="1"
                                    {{ !request('date_from') && !request('date_to') ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">全期間</span>
                            </label>
                        </div>
                    </div>

                    <!-- 発言者フィルター -->
                    @if(isset($space) && isset($speakers) && $speakers->count() > 0)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">発言者</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach($speakers as $speaker)
                                    <button type="button"
                                        onclick="toggleSpeaker('{{ addslashes($speaker) }}')"
                                        class="speaker-badge inline-flex items-center px-3 py-1 rounded-full text-xs font-medium transition-colors {{ request('speaker') === $speaker ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-800 hover:bg-blue-200' }}"
                                        data-speaker="{{ $speaker }}">
                                        <i class="fa-solid fa-user mr-1"></i>
                                        {{ $speaker }}
                                    </button>
                                @endforeach
                            </div>
                            <input type="hidden" name="speaker" id="speaker-input" value="{{ request('speaker') }}">
                        </div>
                    @endif

                    <!-- 検索実行ボタン -->
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="clearSearch()"
                            class="px-4 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                            クリア
                        </button>
                        <button type="submit"
                            class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <i class="fa-solid fa-search mr-2"></i>検索実行
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Overlay for search panel -->
        <div id="search-overlay"
            class="fixed inset-0 bg-black bg-opacity-25 z-30 opacity-0 pointer-events-none transition-opacity duration-300 ease-in-out"
            onclick="toggleSearchPanel()"></div>

        <!-- Page Header (if needed for specific pages) -->
        @hasSection('header')
            <header class="bg-white shadow mt-16">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    @yield('header')
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main class="@hasSection('header') pt-0 @else pt-16 @endif">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class=" border-t border-gray-200 mt-12">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="text-center text-sm text-gray-500">
                    <p>&copy; {{ date('Y') }} {{ $space->name ?? config('app.name') }}. All rights reserved.</p>
                    @if(isset($space) && $space->user)
                        <p class="mt-1">Powered by {{ $space->user->name }}</p>
                    @endif
                </div>
            </div>
        </footer>
    </div>

    <script>
        // グローバル変数の初期化
        let lastScrollTop = 0;
        let isScrollingDown = false;
        let isSearchPanelOpen = false;
        let navbar;
        const scrollThreshold = 60;

        // 検索パネルの開閉制御
        function toggleSearchPanel() {
            if (!isSearchPanelOpen) {
                openSearchPanel();
            } else {
                closeSearchPanel();
            }
        }

        // 検索パネルを開く
        function openSearchPanel() {
            const searchPanel = document.getElementById('search-panel');
            const searchOverlay = document.getElementById('search-overlay');
            const body = document.body;

            if (!searchPanel || !searchOverlay) {
                console.warn('Search panel elements not found');
                return;
            }

            // パネルを表示してからアニメーション
            searchPanel.style.display = 'block';
            searchOverlay.style.opacity = '1';
            searchOverlay.style.pointerEvents = 'auto';
            body.style.overflow = 'hidden';

            // 少し遅らせてtransformアニメーション開始
            setTimeout(() => {
                searchPanel.style.transform = 'translateY(0)';
            }, 10);

            isSearchPanelOpen = true;

            // 検索フィールドにフォーカス
            setTimeout(() => {
                const searchQuery = document.getElementById('search-query');
                if (searchQuery) searchQuery.focus();
            }, 300);
        }

        // 検索パネルを閉じる
        function closeSearchPanel() {
            const searchPanel = document.getElementById('search-panel');
            const searchOverlay = document.getElementById('search-overlay');
            const body = document.body;

            if (!searchPanel || !searchOverlay) {
                return;
            }

            searchPanel.style.transform = 'translateY(-100%)';
            searchOverlay.style.opacity = '0';
            searchOverlay.style.pointerEvents = 'none';
            body.style.overflow = '';
            isSearchPanelOpen = false;

            // アニメーション完了後にdisplay:noneを適用
            setTimeout(() => {
                if (!isSearchPanelOpen) {
                    searchPanel.style.display = 'none';
                }
            }, 300);
        }

        // 検索フォームをクリア
        function clearSearch() {
            const searchQuery = document.getElementById('search-query');
            const playlistFilter = document.getElementById('playlist-filter');
            const videoTypeFilter = document.getElementById('video-type-filter');
            const speakerInput = document.getElementById('speaker-input');
            const dateFrom = document.getElementById('date-from');
            const dateTo = document.getElementById('date-to');
            const allPeriod = document.getElementById('all-period');
            const sortFilter = document.getElementById('sort-filter');

            if (searchQuery) searchQuery.value = '';
            if (playlistFilter) playlistFilter.value = '';
            if (videoTypeFilter) videoTypeFilter.value = '';
            if (speakerInput) speakerInput.value = '';
            if (dateFrom) dateFrom.value = '';
            if (dateTo) dateTo.value = '';
            if (allPeriod) allPeriod.checked = true;
            if (sortFilter) sortFilter.value = 'newest';

            // 発言者バッジをリセット
            const speakerBadges = document.querySelectorAll('.speaker-badge');
            speakerBadges.forEach(badge => {
                badge.className = badge.className.replace('bg-blue-600 text-white', 'bg-blue-100 text-blue-800 hover:bg-blue-200');
            });

            // 期間検索の制御も更新
            toggleDateInputs();
        }

        // 期間検索の制御
        function toggleDateInputs() {
            const allPeriodCheckbox = document.getElementById('all-period');
            const dateFromInput = document.getElementById('date-from');
            const dateToInput = document.getElementById('date-to');

            if (!allPeriodCheckbox || !dateFromInput || !dateToInput) return;

            const isAllPeriod = allPeriodCheckbox.checked;
            dateFromInput.disabled = isAllPeriod;
            dateToInput.disabled = isAllPeriod;

            if (isAllPeriod) {
                dateFromInput.value = '';
                dateToInput.value = '';
                dateFromInput.classList.add('bg-gray-100');
                dateToInput.classList.add('bg-gray-100');
            } else {
                dateFromInput.classList.remove('bg-gray-100');
                dateToInput.classList.remove('bg-gray-100');
            }
        }

        // スクロール処理
        function handleScroll() {
            if (!navbar) return;

            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            const searchPanel = document.getElementById('search-panel');

            // 最上部にいる場合は常に表示
            if (currentScroll <= scrollThreshold) {
                navbar.style.transform = 'translateY(0)';
                navbar.style.opacity = '1';
                if (searchPanel) {
                    searchPanel.style.transform = isSearchPanelOpen ? 'translateY(0)' : 'translateY(-100%)';
                }
                lastScrollTop = currentScroll;
                return;
            }

            // スクロール方向を判定
            if (currentScroll > lastScrollTop && currentScroll > scrollThreshold) {
                // 下向きスクロール: ヘッダーを隠す
                if (!isScrollingDown) {
                    isScrollingDown = true;
                    navbar.style.transform = 'translateY(-100%)';
                    // 検索パネルが開いている場合は閉じる
                    if (isSearchPanelOpen) {
                        closeSearchPanel();
                    }
                    // 検索パネルも隠す
                    if (searchPanel) {
                        searchPanel.style.transform = 'translateY(-100%)';
                    }
                }
            } else if (currentScroll < lastScrollTop) {
                // 上向きスクロール: ヘッダーを表示
                if (isScrollingDown) {
                    isScrollingDown = false;
                    navbar.style.transform = 'translateY(0)';
                    // 検索パネルの位置を復元
                    if (searchPanel) {
                        searchPanel.style.transform = isSearchPanelOpen ? 'translateY(0)' : 'translateY(-100%)';
                    }
                }
            }

            lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
        }

        // ページ読み込み時の初期化
        document.addEventListener('DOMContentLoaded', function () {
            // ナビゲーションバーの参照を取得
            navbar = document.getElementById('navbar');

            if (navbar) {
                navbar.style.transform = 'translateY(0)';
                navbar.style.opacity = '1';
            }

            // スクロールイベントをスロットリング
            let ticking = false;
            window.addEventListener('scroll', function () {
                if (!ticking) {
                    requestAnimationFrame(function () {
                        handleScroll();
                        ticking = false;
                    });
                    ticking = true;
                }
            });

            // ESCキーでパネルを閉じる
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && isSearchPanelOpen) {
                    toggleSearchPanel();
                }
            });
        });

        // 発言者バッジの切り替え
        function toggleSpeaker(speaker) {
            const speakerInput = document.getElementById('speaker-input');
            const speakerBadges = document.querySelectorAll('.speaker-badge');

            // 現在選択されている発言者を取得
            const currentSpeaker = speakerInput.value;

            if (currentSpeaker === speaker) {
                // 既に選択されている場合は選択解除
                speakerInput.value = '';
                speakerBadges.forEach(badge => {
                    badge.className = badge.className.replace('bg-blue-600 text-white', 'bg-blue-100 text-blue-800 hover:bg-blue-200');
                });
            } else {
                // 新しい発言者を選択
                speakerInput.value = speaker;
                speakerBadges.forEach(badge => {
                    if (badge.dataset.speaker === speaker) {
                        badge.className = badge.className.replace('bg-blue-100 text-blue-800 hover:bg-blue-200', 'bg-blue-600 text-white');
                    } else {
                        badge.className = badge.className.replace('bg-blue-600 text-white', 'bg-blue-100 text-blue-800 hover:bg-blue-200');
                    }
                });
            }
        }

        // ページ読み込み時の初期化処理を拡張
        document.addEventListener('DOMContentLoaded', function () {
            // 期間検索の制御を初期化
            const allPeriodCheckbox = document.getElementById('all-period');
            const dateFromInput = document.getElementById('date-from');
            const dateToInput = document.getElementById('date-to');

            if (allPeriodCheckbox && dateFromInput && dateToInput) {
                // 初期状態を設定
                toggleDateInputs();

                // 全期間チェックボックスの変更イベント
                allPeriodCheckbox.addEventListener('change', toggleDateInputs);

                // 日付入力があった場合は全期間チェックを外す
                [dateFromInput, dateToInput].forEach(input => {
                    input.addEventListener('change', function() {
                        if (this.value) {
                            allPeriodCheckbox.checked = false;
                            toggleDateInputs();
                        }
                    });
                });
            }
        });
    </script>

    <!-- Back to Top Button -->
    <button id="back-to-top" aria-label="トップへ戻る"
        class="fixed right-4 bottom-6 z-50 w-12 h-12 rounded-full bg-indigo-600 text-white shadow-lg flex items-center justify-center transition-opacity duration-200 opacity-0 pointer-events-none">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
        </svg>
    </button>

    <script>
        // Back to top behavior: show when scrolled down, hide at top
        (function() {
            const btn = document.getElementById('back-to-top');
            if (!btn) return;

            function updateVisibility() {
                const scrollY = window.pageYOffset || document.documentElement.scrollTop;
                if (scrollY > 200) {
                    btn.style.opacity = '1';
                    btn.style.pointerEvents = 'auto';
                } else {
                    btn.style.opacity = '0';
                    btn.style.pointerEvents = 'none';
                }
            }

            btn.addEventListener('click', function(e) {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            document.addEventListener('scroll', function() {
                updateVisibility();
            }, { passive: true });

            // initialize
            updateVisibility();
        })();
    </script>

    @stack('scripts')
</body>

</html>
