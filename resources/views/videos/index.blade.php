<x-app-layout>
    <x-slot name="header">
        {{-- パンくずリスト (変更なし) --}}
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
                        <a href="{{ route('spaces.channels.index', $space) }}"
                            class="text-sm font-medium text-gray-700 hover:text-blue-600">
                            {{ $space->name }}
                        </a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fa-solid fa-chevron-right text-gray-400 mx-2"></i>
                        <span class="text-sm font-medium text-gray-500"> 動画一覧 </span>
                    </div>
                </li>
            </ol>
        </nav>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{-- フィルター展開ボタン --}}
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">
                                <i class="fa-solid fa-video mr-2"></i>動画一覧
                            </h3>
                        </div>
                        <button id="filter-toggle-btn" type="button"
                            class="inline-flex items-center justify-center h-10 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            <i class="fa-solid fa-filter mr-2"></i>
                            <span id="filter-toggle-text">フィルター</span>
                            <i id="filter-toggle-icon" class="fa-solid fa-chevron-down ml-2 transition-transform"></i>
                        </button>
                    </div>

                    {{-- 展開式フィルターエリア --}}
                    <div id="filter-area" class="hidden mb-6">
                        {{-- 絞り込み・並び替えフォーム (展開式版) --}}
                        <form method="GET" action="{{ route('videos.index', $space) }}" class="mb-4 p-4 bg-gray-50 rounded-lg border">
                            {{-- 表示方法を保持 --}}
                            <input type="hidden" name="view" value="{{ request('view', 'list') }}">
                            <div class="space-y-4">
                                {{-- 1行目: キーワード検索 --}}
                                <div>
                                    <label for="keyword" class="block text-sm font-medium text-gray-700 mb-1">キーワード検索</label>
                                    <input type="text" name="keyword" id="keyword" value="{{ request('keyword') }}"
                                        class="block w-full h-10 px-3 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                        placeholder="タイトルで検索...">
                                </div>

                                {{-- 2行目: セレクトボックス (モバイル：縦並び、デスクトップ：横並び) --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label for="playlist_id" class="block text-sm font-medium text-gray-700 mb-1">再生リスト</label>
                                        <select name="playlist_id" id="playlist_id"
                                            class="block w-full h-10 px-3 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="">すべて</option>
                                            @foreach ($playlists as $playlist)
                                                <option value="{{ $playlist->id }}" @selected(request('playlist_id') == $playlist->id)>
                                                    {{ $playlist->title }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="video_type" class="block text-sm font-medium text-gray-700 mb-1">動画種別</label>
                                        <select name="video_type" id="video_type"
                                            class="block w-full h-10 px-3 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="">すべて</option>
                                            <option value="video" @selected(request('video_type') == 'video')>通常動画</option>
                                            <option value="short" @selected(request('video_type') == 'short')>ショート</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- 3行目: 期間検索 --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">公開日</label>
                                    <div class="flex items-center gap-2">
                                        <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                                            class="flex-1 min-w-0 h-10 px-3 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                        <span class="text-gray-500 text-sm flex-shrink-0">～</span>
                                        <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                                            class="flex-1 min-w-0 h-10 px-3 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                    </div>
                                    <div class="mt-2 flex items-center">
                                        <input type="checkbox" name="all_period" id="all_period" value="1"
                                            {{ !request('date_from') && !request('date_to') ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <label for="all_period" class="ml-2 text-sm font-medium text-gray-700">全期間</label>
                                    </div>
                                </div>

                                {{-- 4行目: アクションボタン --}}
                                <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-2">
                                    <x-secondary-button type="button" onclick="location.href='{{ route('videos.index', $space) }}'"
                                        class="flex-1 sm:flex-none justify-center h-10 px-4 py-2 text-sm">
                                        <i class="fa-solid fa-refresh mr-2"></i>
                                        <span>リセット</span>
                                    </x-secondary-button>
                                    <x-primary-button class="flex-1 sm:flex-none justify-center h-10 px-4 py-2 text-sm">
                                        <i class="fa-solid fa-filter mr-2"></i>
                                        <span>絞り込む</span>
                                    </x-primary-button>
                                </div>
                            </div>
                        </form>

                        {{-- 並び順ボタンエリア (展開式版) --}}
                        <div class="p-4 bg-gray-50 rounded-lg border">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">
                                <i class="fa-solid fa-sort mr-2"></i>並び順
                            </h3>
                            <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
                                {{-- 公開日 --}}
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'newest', 'view' => request('view', 'list')]) }}"
                                    class="inline-flex items-center justify-center h-10 px-3 py-2 text-sm font-medium rounded-md transition-colors min-w-0
                                    {{ request('sort', 'newest') == 'newest' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                                    <i class="fa-solid fa-clock mr-1 sm:mr-2"></i>
                                    <span class="truncate">公開日（新）</span>
                                </a>
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'oldest', 'view' => request('view', 'list')]) }}"
                                    class="inline-flex items-center justify-center h-10 px-3 py-2 text-sm font-medium rounded-md transition-colors min-w-0
                                    {{ request('sort') == 'oldest' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                                    <i class="fa-solid fa-clock mr-1 sm:mr-2"></i>
                                    <span class="truncate">公開日（古）</span>
                                </a>

                                {{-- 統計情報 --}}
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'view_count_desc', 'view' => request('view', 'list')]) }}"
                                    class="inline-flex items-center justify-center h-10 px-3 py-2 text-sm font-medium rounded-md transition-colors min-w-0
                                    {{ request('sort') == 'view_count_desc' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100' }}">
                                    <i class="fa-solid fa-eye mr-1 sm:mr-2"></i>
                                    <span class="truncate">再生回数↓</span>
                                </a>
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'view_count_asc', 'view' => request('view', 'list')]) }}"
                                    class="inline-flex items-center justify-center h-10 px-3 py-2 text-sm font-medium rounded-md transition-colors min-w-0
                                    {{ request('sort') == 'view_count_asc' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100' }}">
                                    <i class="fa-solid fa-eye mr-1 sm:mr-2"></i>
                                    <span class="truncate">再生回数↑</span>
                                </a>

                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'like_count_desc', 'view' => request('view', 'list')]) }}"
                                    class="inline-flex items-center justify-center h-10 px-3 py-2 text-sm font-medium rounded-md transition-colors min-w-0
                                    {{ request('sort') == 'like_count_desc' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100' }}">
                                    <i class="fa-solid fa-thumbs-up mr-1 sm:mr-2"></i>
                                    <span class="truncate">いいね数↓</span>
                                </a>
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'like_count_asc', 'view' => request('view', 'list')]) }}"
                                    class="inline-flex items-center justify-center h-10 px-3 py-2 text-sm font-medium rounded-md transition-colors min-w-0
                                    {{ request('sort') == 'like_count_asc' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100' }}">
                                    <i class="fa-solid fa-thumbs-up mr-1 sm:mr-2"></i>
                                    <span class="truncate">いいね数↑</span>
                                </a>

                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'comment_count_desc', 'view' => request('view', 'list')]) }}"
                                    class="inline-flex items-center justify-center h-10 px-3 py-2 text-sm font-medium rounded-md transition-colors min-w-0
                                    {{ request('sort') == 'comment_count_desc' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-700 border border-green-200 hover:bg-green-100' }}">
                                    <i class="fa-solid fa-comments mr-1 sm:mr-2"></i>
                                    <span class="truncate">コメント数↓</span>
                                </a>
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'comment_count_asc', 'view' => request('view', 'list')]) }}"
                                    class="inline-flex items-center justify-center h-10 px-3 py-2 text-sm font-medium rounded-md transition-colors min-w-0
                                    {{ request('sort') == 'comment_count_asc' ? 'bg-green-600 text-white' : 'bg-green-50 text-green-700 border border-green-200 hover:bg-green-100' }}">
                                    <i class="fa-solid fa-comments mr-1 sm:mr-2"></i>
                                    <span class="truncate">コメント数↑</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- 表示切り替えボタン --}}
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700">
                                <i class="fa-solid fa-eye mr-2"></i>表示方法
                            </h3>
                            <div class="mt-2 flex gap-2">
                                <button onclick="switchView('list')" id="list-view-btn"
                                    class="inline-flex items-center justify-center h-10 px-3 py-2 text-sm font-medium rounded-md transition-colors
                                    {{ request('view', 'list') != 'card' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                                    <i class="fa-solid fa-list mr-2"></i>リスト表示
                                </button>
                                <button onclick="switchView('card')" id="card-view-btn"
                                    class="inline-flex items-center justify-center h-10 px-3 py-2 text-sm font-medium rounded-md transition-colors
                                    {{ request('view', 'list') == 'card' ? 'bg-emerald-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                                    <i class="fa-solid fa-th-large mr-2"></i>カード表示
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- 検索結果件数表示 --}}
                    <div class="mb-4 p-3 bg-blue-50 border-l-4 border-blue-400 rounded">
                        <div class="flex items-center">
                            <i class="fa-solid fa-info-circle text-blue-500 mr-2"></i>
                            <span class="text-sm text-blue-700">
                                <strong>{{ number_format($videos->total()) }}</strong> 件の動画が見つかりました
                                @if(request('keyword'))
                                    （キーワード: <strong>{{ request('keyword') }}</strong>）
                                @endif
                                @if(request('playlist_id'))
                                    @php
                                        $selectedPlaylist = $playlists->firstWhere('id', request('playlist_id'));
                                    @endphp
                                    （再生リスト: <strong>{{ $selectedPlaylist->title ?? '' }}</strong>）
                                @endif
                                @if(request('video_type'))
                                    （種別: <strong>{{ request('video_type') == 'video' ? '通常動画' : 'ショート' }}</strong>）
                                @endif
                                @if(request('date_from') || request('date_to'))
                                    （期間:
                                    @if(request('date_from'))
                                        <strong>{{ request('date_from') }}</strong>以降
                                    @endif
                                    @if(request('date_from') && request('date_to'))
                                        ～
                                    @endif
                                    @if(request('date_to'))
                                        <strong>{{ request('date_to') }}</strong>以前
                                    @endif
                                    ）
                                @endif
                            </span>
                        </div>
                    </div>

                    {{-- ★★★ 動画一覧 ★★★ --}}
                    <div id="video-list" class="{{ request('view', 'list') == 'card' ? 'grid grid-cols-1 md:grid-cols-2 gap-6' : 'space-y-2' }}">
                        {{-- 最初のページの動画を読み込む --}}
                        @include('videos._video_list', ['videos' => $videos])
                    </div>

                    {{-- ★★★ もっとみるボタン ★★★ --}}
                    <div id="load-more-container" class="text-center mt-8">
                        {{-- 次のページがあればボタンを表示 --}}
                        @if ($videos->hasMorePages())
                            <x-primary-button id="load-more-button" type="button" class="bg-gray-800 hover:bg-gray-700">
                                <span id="load-more-text">もっとみる</span>
                                <span id="loading-indicator" class="hidden">
                                    <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                                    読み込み中...
                                </span>
                            </x-primary-button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- JavaScriptを直接埋め込み --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // フィルター展開機能
            const filterToggleBtn = document.getElementById('filter-toggle-btn');
            const filterArea = document.getElementById('filter-area');
            const filterToggleIcon = document.getElementById('filter-toggle-icon');
            const filterToggleText = document.getElementById('filter-toggle-text');

            // フィルターが適用されているかチェック
            const hasActiveFilters = {{
                request('keyword') || request('playlist_id') || request('video_type') ||
                request('date_from') || request('date_to') || request('sort') ? 'true' : 'false'
            }};

            // 初期状態: フィルターが適用されている場合は展開
            if (hasActiveFilters) {
                filterArea.classList.remove('hidden');
                filterToggleIcon.classList.add('rotate-180');
                filterToggleText.textContent = 'フィルターを閉じる';
            }

            // フィルター展開ボタンのクリックイベント
            filterToggleBtn.addEventListener('click', function() {
                const isHidden = filterArea.classList.contains('hidden');

                if (isHidden) {
                    // 展開
                    filterArea.classList.remove('hidden');
                    filterToggleIcon.classList.add('rotate-180');
                    filterToggleText.textContent = 'フィルターを閉じる';
                } else {
                    // 閉じる
                    filterArea.classList.add('hidden');
                    filterToggleIcon.classList.remove('rotate-180');
                    filterToggleText.textContent = 'フィルター';
                }
            });

            // 期間検索の制御
            const allPeriodCheckbox = document.getElementById('all_period');
            const dateFromInput = document.getElementById('date_from');
            const dateToInput = document.getElementById('date_to');

            // 全期間チェックボックスの状態に応じて日付入力を制御
            function toggleDateInputs() {
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
            const loadMoreButton = document.getElementById('load-more-button');
            if (!loadMoreButton) return;

            const loadMoreText = document.getElementById('load-more-text');
            const loadingIndicator = document.getElementById('loading-indicator');
            const videoList = document.getElementById('video-list');
            const loadMoreContainer = document.getElementById('load-more-container');

            let nextPageUrl = '{{ $videos->nextPageUrl() }}';

            loadMoreButton.addEventListener('click', async function () {
                if (!nextPageUrl) return;                // ボタンをローディング状態に
                loadMoreButton.disabled = true;
                loadMoreText.classList.add('hidden');
                loadingIndicator.classList.remove('hidden');

                try {
                    const response = await fetch(nextPageUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('読み込みに失敗しました。');
                    }

                    const data = await response.json();

                    // 新しい動画のHTMLを追加
                    if (data.html && data.html.trim() !== '') {
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = data.html;
                        const newVideos = Array.from(tempDiv.children);

                        newVideos.forEach(video => {
                            videoList.appendChild(video);
                        });
                    }

                    // 次のページの情報を更新
                    nextPageUrl = data.next_page_url;

                    // 次のページがない場合はボタンを非表示
                    if (!nextPageUrl) {
                        loadMoreContainer.style.display = 'none';
                    }

                } catch (error) {
                    console.error('Error loading more videos:', error);
                    loadMoreContainer.innerHTML = `<p class="text-red-500">エラー: ${error.message}</p>`;
                } finally {
                    // ボタンを通常状態に戻す
                    loadMoreButton.disabled = false;
                    loadMoreText.classList.remove('hidden');
                    loadingIndicator.classList.add('hidden');
                }
            });
        });

        // 表示切り替え機能
        function switchView(viewType) {
            const url = new URL(window.location);
            url.searchParams.set('view', viewType);
            window.location.href = url.toString();
        }

        // 表示切り替えボタンのスタイル更新
        function updateViewButtons() {
            const currentView = new URLSearchParams(window.location.search).get('view') || 'list';
            const cardBtn = document.getElementById('card-view-btn');
            const listBtn = document.getElementById('list-view-btn');

            if (currentView === 'card') {
                cardBtn.className = 'inline-flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors bg-emerald-600 text-white';
                listBtn.className = 'inline-flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors bg-white text-gray-700 border border-gray-300 hover:bg-gray-50';
            } else {
                cardBtn.className = 'inline-flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors bg-white text-gray-700 border border-gray-300 hover:bg-gray-50';
                listBtn.className = 'inline-flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors bg-emerald-600 text-white';
            }
        }

        // ページ読み込み時にボタンスタイルを更新
        updateViewButtons();
    </script>

</x-app-layout>
