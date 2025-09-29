@extends('layouts.guest-app')

@section('content')

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if($recentVideos->count() > 0)
                <div class="mx-2 flex flex-wrap justify-center gap-2 mb-4">
                    <button onclick="toggleSort('published_at')" id="sort-published-at-btn"
                        class="inline-flex items-center justify-center h-8 px-3 py-1 text-xs font-medium rounded-md transition-colors {{ in_array(request('sort', 'newest'), ['newest', 'oldest']) ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                        <i class="fa-solid fa-calendar mr-1"></i>投稿日時
                        @if(request('sort', 'newest') === 'oldest')
                            <i class="fa-solid fa-arrow-up ml-1 text-xs"></i>
                        @else
                            <i class="fa-solid fa-arrow-down ml-1 text-xs"></i>
                        @endif
                    </button>
                    <button onclick="toggleSort('view_count')" id="sort-view-count-btn"
                        class="inline-flex items-center justify-center h-8 px-3 py-1 text-xs font-medium rounded-md transition-colors {{ in_array(request('sort'), ['views_desc', 'views_asc']) ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                        <i class="fa-solid fa-eye mr-1"></i>再生回数
                        @if(request('sort') === 'views_asc')
                            <i class="fa-solid fa-arrow-up ml-1 text-xs"></i>
                        @else
                            <i class="fa-solid fa-arrow-down ml-1 text-xs"></i>
                        @endif
                    </button>
                    <button onclick="toggleSort('like_count')" id="sort-like-count-btn"
                        class="inline-flex items-center justify-center h-8 px-3 py-1 text-xs font-medium rounded-md transition-colors {{ in_array(request('sort'), ['likes_desc', 'likes_asc']) ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                        <i class="fa-solid fa-thumbs-up mr-1"></i>高評価
                        @if(request('sort') === 'likes_asc')
                            <i class="fa-solid fa-arrow-up ml-1 text-xs"></i>
                        @else
                            <i class="fa-solid fa-arrow-down ml-1 text-xs"></i>
                        @endif
                    </button>
                    <button onclick="toggleSort('title')" id="sort-title-btn"
                        class="inline-flex items-center justify-center h-8 px-3 py-1 text-xs font-medium rounded-md transition-colors {{ in_array(request('sort'), ['title_asc', 'title_desc']) ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                        <i class="fa-solid fa-sort-alpha-up mr-1"></i>タイトル
                        @if(request('sort') === 'title_desc')
                            <i class="fa-solid fa-arrow-down ml-1 text-xs"></i>
                        @else
                            <i class="fa-solid fa-arrow-up ml-1 text-xs"></i>
                        @endif
                    </button>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">
                                <i class="fa-solid fa-video mr-2"></i>動画一覧
                            </h3>
                            <div>
                                <a href="@if($space->visibility === 2){{ route('guest.space.content.public', $space->slug) }}@else{{ route('guest.space.content.invite', [$space->slug, $space->invite_token]) }}@endif"
                                    class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-md bg-white border border-gray-300 hover:bg-gray-50 text-gray-700">
                                    <i class="fa-solid fa-info-circle mr-2"></i>コンテンツ情報を見る
                                </a>
                                <button onclick="switchView('list')" id="list-view-btn"
                                    class="inline-flex items-center justify-center h-8 px-3 py-1 text-xs font-medium rounded-md transition-colors bg-emerald-600 text-white">
                                    <i class="fa-solid fa-list mr-1"></i>リスト
                                </button>
                                <button onclick="switchView('card')" id="card-view-btn"
                                    class="inline-flex items-center justify-center h-8 px-3 py-1 text-xs font-medium rounded-md transition-colors bg-white text-gray-700 border border-gray-300 hover:bg-gray-50">
                                    <i class="fa-solid fa-th-large mr-1"></i>カード
                                </button>
                            </div>
                        </div>

                        <div class="mb-4 p-3 bg-blue-50 border-l-4 border-blue-400 rounded">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-info-circle text-blue-500 mr-2"></i>
                                    <span class="text-sm text-blue-700">
                                        @php
                                            $keyword = request('keyword');
                                            $searchTargets = request('search_targets', []);
                                            $playlistId = request('playlist_id');
                                            $videoType = request('video_type');
                                            $speaker = request('speaker');
                                            $dateFrom = request('date_from');
                                            $dateTo = request('date_to');
                                            $sort = request('sort');

                                            // GuestControllerと同じロジックで件数を取得
                                            $totalQuery = $space->videos()->with(['channel', 'playlists']);

                                            if ($keyword) {
                                                // 検索対象が指定されていない場合はデフォルトで全て対象
                                                if (empty($searchTargets)) {
                                                    $searchTargets = ['title', 'description', 'dialogue', 'playlist'];
                                                }

                                                $totalQuery->where(function ($q) use ($keyword, $searchTargets) {
                                                    $hasCondition = false;

                                                    // タイトル検索
                                                    if (in_array('title', $searchTargets)) {
                                                        $q->where('videos.title', 'like', '%' . $keyword . '%');
                                                        $hasCondition = true;
                                                    }

                                                    // 説明検索
                                                    if (in_array('description', $searchTargets)) {
                                                        if ($hasCondition) {
                                                            $q->orWhere('videos.description', 'like', '%' . $keyword . '%');
                                                        } else {
                                                            $q->where('videos.description', 'like', '%' . $keyword . '%');
                                                            $hasCondition = true;
                                                        }
                                                    }

                                                    // 再生リスト名検索
                                                    if (in_array('playlist', $searchTargets)) {
                                                        if ($hasCondition) {
                                                            $q->orWhereHas('playlists', function ($playlistQuery) use ($keyword) {
                                                                $playlistQuery->where('title', 'like', '%' . $keyword . '%');
                                                            });
                                                        } else {
                                                            $q->whereHas('playlists', function ($playlistQuery) use ($keyword) {
                                                                $playlistQuery->where('title', 'like', '%' . $keyword . '%');
                                                            });
                                                            $hasCondition = true;
                                                        }
                                                    }

                                                    // 字幕検索（発言内容のみ、発言者は除外）
                                                    if (in_array('dialogue', $searchTargets)) {
                                                        if ($hasCondition) {
                                                            $q->orWhereHas('dialogues', function ($dialogueQuery) use ($keyword) {
                                                                $dialogueQuery->where('dialogue', 'like', '%' . $keyword . '%');
                                                            });
                                                        } else {
                                                            $q->whereHas('dialogues', function ($dialogueQuery) use ($keyword) {
                                                                $dialogueQuery->where('dialogue', 'like', '%' . $keyword . '%');
                                                            });
                                                            $hasCondition = true;
                                                        }
                                                    }
                                                });
                                            }

                                            // 発言者フィルター
                                            if ($speaker) {
                                                $totalQuery->whereHas('dialogues', function ($q) use ($speaker) {
                                                    $q->where('speaker', $speaker);
                                                });
                                            }

                                            if ($playlistId) {
                                                $totalQuery->whereHas('playlists', function ($q) use ($playlistId) {
                                                    $q->where('playlist_id', $playlistId);
                                                });
                                            }

                                            if ($videoType) {
                                                $totalQuery->where('video_type', $videoType);
                                            }

                                            if ($dateFrom) {
                                                $totalQuery->where('published_at', '>=', $dateFrom . ' 00:00:00');
                                            }
                                            if ($dateTo) {
                                                $totalQuery->where('published_at', '<=', $dateTo . ' 23:59:59');
                                            }

                                            $totalCount = $totalQuery->count();
                                            $hasSearchConditions = request()->hasAny(['keyword', 'search_targets', 'playlist_id', 'video_type', 'speaker', 'date_from', 'date_to']) || (request('sort') && request('sort') !== 'newest');
                                        @endphp
                                        <strong>{{ number_format($totalCount) }}</strong> 件の動画
                                        @if($hasSearchConditions)
                                            を表示中
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
                                            @if(request('speaker'))
                                                （発言者: <strong>{{ request('speaker') }}</strong>）
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
                                        @else
                                            を表示中
                                        @endif
                                    </span>
                                    @if($hasSearchConditions)
                                        <button type="button" onclick="resetSearch()"
                                            class="inline-flex items-center ml-2 px-3 py-1 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                                            <i class="fa-solid fa-refresh mr-1"></i>
                                            <span class="md:block hidden">リセット</span>
                                        </button>
                                    @endif
                                </div>

                            </div>
                        </div>

                        <div id="video-list" class="space-y-2">
                            @foreach($recentVideos as $video)
                                <div class="video-item list-view">
                                    {{-- リスト表示 --}}
                                    <div class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                                        <a href="@if($space->visibility === 2)
                                            {{ route('guest.video.public', [$space->slug, $video]) }}
                                        @else
                                                        {{ route('guest.video.invite', [$space->slug, $space->invite_token, $video]) }}
                                                    @endif" class="block">
                                            <div class="flex items-center p-3 hover:bg-gray-50">
                                                {{-- サムネイル（小さめ） --}}
                                                <div class="flex-shrink-0 relative cursor-pointer"
                                                    onclick="event.stopPropagation(); window.open('https://www.youtube.com/watch?v={{ $video->youtube_video_id }}', '_blank');"
                                                    title="YouTubeで動画を開く">
                                                    @if($video->thumbnail_url)
                                                        <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}"
                                                            class="w-24 h-16 object-cover rounded">
                                                    @else
                                                        <div class="w-24 h-16 bg-gray-200 flex items-center justify-center rounded">
                                                            <i class="fa-solid fa-video text-gray-400"></i>
                                                        </div>
                                                    @endif
                                                    {{-- 再生アイコンオーバーレイ --}}
                                                    <div
                                                        class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30 opacity-0 hover:opacity-100 transition-opacity rounded">
                                                        <i class="fa-solid fa-play text-white text-lg"></i>
                                                    </div>
                                                    {{-- YouTube外部リンクアイコン --}}
                                                    <button type="button"
                                                        class="absolute top-1 right-1 bg-black bg-opacity-70 text-white p-1 rounded text-xs hover:bg-opacity-90 transition-opacity"
                                                        onclick="event.stopPropagation(); window.open('https://www.youtube.com/watch?v={{ $video->youtube_video_id }}', '_blank');"
                                                        title="YouTubeで開く">
                                                        <i class="fa-solid fa-external-link-alt"></i>
                                                    </button>
                                                </div>

                                                {{-- 動画情報（コンパクト） --}}
                                                <div class="ml-4 flex-1 min-w-0">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1 min-w-0">
                                                            <h3
                                                                class="font-semibold text-sm text-gray-900 line-clamp-2 hover:text-blue-600 transition-colors">
                                                                {{ $video->title }}
                                                            </h3>

                                                            <div class="mt-1 flex items-center space-x-4 text-xs text-gray-500">
                                                                <span class="flex items-center">
                                                                    <i class="fa-solid fa-calendar-alt mr-1"></i>
                                                                    {{ optional($video->published_at)->format('Y/m/d') ?? '' }}
                                                                </span>
                                                                <span class="flex items-center truncate">
                                                                    <i class="fa-solid fa-tv mr-1"></i>
                                                                    {{ $video->channel->name }}
                                                                </span>
                                                            </div>

                                                            {{-- 統計情報（インライン） --}}
                                                            @if($video->view_count || $video->like_count || $video->comment_count)
                                                                <div class="mt-1 flex items-center space-x-3 text-xs">
                                                                    @if($video->view_count)
                                                                        <span class="flex items-center text-red-600">
                                                                            <i class="fa-solid fa-eye mr-1"></i>
                                                                            {{ number_format($video->view_count) }}
                                                                        </span>
                                                                    @endif
                                                                    @if($video->like_count)
                                                                        <span class="flex items-center text-blue-600">
                                                                            <i class="fa-solid fa-thumbs-up mr-1"></i>
                                                                            {{ number_format($video->like_count) }}
                                                                        </span>
                                                                    @endif
                                                                    @if($video->comment_count)
                                                                        <span class="flex items-center text-green-600">
                                                                            <i class="fa-solid fa-comment mr-1"></i>
                                                                            {{ number_format($video->comment_count) }}
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            @endif

                                                            {{-- 字幕有無の表示（リスト表示用） --}}
                                                            @if($video->dialogues && $video->dialogues->count() > 0)
                                                                <div class="mt-1">
                                                                    <span
                                                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                                                        title="字幕あり ({{ $video->dialogues->count() }}件)">
                                                                        <i class="fa-solid fa-closed-captioning mr-1"></i>
                                                                        字幕 ({{ $video->dialogues->count() }}件)
                                                                    </span>
                                                                </div>
                                                            @endif

                                                            {{-- 字幕・発言者検索でヒットした場合の表示 --}}
                                                            @php
                                                                $showDialogues = false;
                                                                $searchTargets = request('search_targets', []);
                                                                $keyword = request('keyword');
                                                                $speaker = request('speaker');

                                                                // 字幕検索の条件チェック
                                                                $dialogueSearch = $keyword && in_array('dialogue', $searchTargets);
                                                                // 発言者検索の条件チェック
                                                                $speakerSearch = !empty($speaker);

                                                                // 字幕検索または発言者検索でヒットした場合
                                                                if ($dialogueSearch || $speakerSearch) {
                                                                    $showDialogues = true;
                                                                }
                                                            @endphp


                                                        </div>

                                                        {{-- バッジエリア（リスト表示時は非表示） --}}
                                                        <div class="ml-4 flex flex-col items-end space-y-1 badge-area">
                                                            {{-- 字幕有無マーク --}}
                                                            @if($video->dialogues && $video->dialogues->count() > 0)
                                                                <span
                                                                    class="inline-flex items-center px-1 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800"
                                                                    title="字幕あり ({{ $video->dialogues->count() }}件)">
                                                                    <i class="fa-solid fa-closed-captioning mr-1"></i>
                                                                    字幕
                                                                </span>
                                                            @endif
                                                            @if ($video->video_type === 'short')
                                                                <span
                                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-pink-100 text-pink-800">
                                                                    ショート
                                                                </span>
                                                            @endif
                                                            {{-- 再生リストバッジ --}}
                                                            @if($video->playlists && $video->playlists->count() > 0)
                                                                @foreach($video->playlists->take(2) as $playlist)
                                                                    <button type="button"
                                                                        onclick="searchByPlaylist(event, '{{ $playlist->id }}', '{{ addslashes($playlist->title) }}');"
                                                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 hover:bg-purple-200 transition-colors"
                                                                        title="この再生リストで検索">
                                                                        <i class="fa-solid fa-list mr-1"></i>
                                                                        {{ Str::limit($playlist->title, 15) }}
                                                                    </button>
                                                                @endforeach
                                                                @if($video->playlists->count() > 2)
                                                                    <span
                                                                        class="text-xs text-gray-500">他{{ $video->playlists->count() - 2 }}件</span>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- 字幕表示エリア（動画リストから独立） --}}
                        @php
                            $showDialogues = false;
                            $searchTargets = request('search_targets', []);
                            $keyword = request('keyword');
                            $speaker = request('speaker');

                            // 字幕検索の条件チェック
                            $dialogueSearch = $keyword && in_array('dialogue', $searchTargets);
                            // 発言者検索の条件チェック
                            $speakerSearch = !empty($speaker);

                            // 字幕検索または発言者検索でヒットした場合
                            if ($dialogueSearch || $speakerSearch) {
                                $showDialogues = true;
                            }
                        @endphp

                        @if($showDialogues)
                            <div class="mt-6">
                                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4">
                                    <div class="flex items-center mb-4">
                                        <i class="fa-solid fa-closed-captioning text-blue-600 mr-2 text-lg"></i>
                                        <h4 class="text-lg font-semibold text-blue-800">マッチした字幕 ({{$matchedDialogues->count()}}件)
                                        </h4>
                                    </div>


                                    @if($matchedDialogues->count() > 0)
                                        <div class="space-y-4 max-h-96 overflow-y-auto">
                                            @foreach($matchedDialogues as $dialogue)
                                                <div class="bg-white border border-blue-100 rounded-lg p-4 shadow-sm">
                                                    {{-- 動画情報ヘッダー（クリック可能） --}}
                                                    <a href="@if($space->visibility === 2)
                                                        {{ route('guest.video.public', [$space->slug, $dialogue->video_id]) }}
                                                    @else
                                                                            {{ route('guest.video.invite', [$space->slug, $space->invite_token, $dialogue->video_id]) }}
                                                                        @endif"
                                                        class="block hover:bg-gray-50 rounded transition-colors">
                                                        <div class="flex items-start gap-3 mb-3 pb-3 border-b border-gray-100">
                                                            <div class="flex-shrink-0">
                                                                @if($dialogue->thumbnail_url)
                                                                    <img src="{{ $dialogue->thumbnail_url }}" alt="{{ $dialogue->video_title }}"
                                                                        class="w-16 h-10 object-cover rounded">
                                                                @else
                                                                    <div class="w-16 h-10 bg-gray-200 flex items-center justify-center rounded">
                                                                        <i class="fa-solid fa-video text-gray-400 text-xs"></i>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div class="flex-1 min-w-0">
                                                                <h5
                                                                    class="font-medium text-gray-900 text-sm line-clamp-1 hover:text-blue-600 transition-colors">
                                                                    {{ $dialogue->video_title }}
                                                                </h5>
                                                                <p class="text-xs text-gray-500 mt-1">
                                                                    {{ $dialogue->channel_name }} •
                                                                    {{ $dialogue->published_at ? \Carbon\Carbon::parse($dialogue->published_at)->format('Y/m/d') : '' }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </a>

                                                    {{-- 字幕内容 --}}
                                                    <div class="flex items-start gap-2">
                                                        {{-- 時間バッジ（クリック可能） --}}
                                                        <button type="button"
                                                            onclick="openVideoAtTime('{{ $dialogue->youtube_video_id }}', {{ $dialogue->timestamp }})"
                                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs bg-red-600 text-white hover:bg-red-400 transition-colors cursor-pointer flex-shrink-0"
                                                            title="この時間から動画を再生">
                                                            <i class="fa-solid fa-play mr-1"></i>
                                                            {{ gmdate('H:i:s', $dialogue->timestamp) }}
                                                        </button>

                                                        {{-- 発言者バッジ --}}
                                                        @if($dialogue->speaker)
                                                            <span
                                                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-20 text-amber-800 flex-shrink-0">
                                                                <i class="fa-solid fa-user mr-1"></i>
                                                                {{ $dialogue->speaker }}
                                                            </span>
                                                        @endif
                                                    </div>

                                                    {{-- 字幕テキスト --}}
                                                    <div class="mt-3 text-sm text-gray-700 leading-relaxed">
                                                        {{ $dialogue->dialogue }}
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="bg-white border border-gray-200 rounded-lg p-6 text-center">
                                            <div class="flex items-center justify-center text-gray-500 mb-2">
                                                <i class="fa-solid fa-info-circle mr-2"></i>
                                                <span class="text-sm">マッチする字幕がありません</span>
                                            </div>
                                            <p class="text-xs text-gray-400">
                                                検索条件を変更して再度お試しください
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div id="load-more-container" class="mt-6 text-center">
                            <button id="load-more-btn" onclick="loadMoreVideos()"
                                class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                <span id="load-more-text">もっと見る</span>
                            </button>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 mb-4">
                            <i class="fa-solid fa-video text-gray-400 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">動画が登録されていません</h3>
                        <p class="text-sm text-gray-500">
                            スペースの管理者が動画を登録すると、ここに表示されます。
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .video-item.card-view {
            display: none;
        }

        .video-list-card .video-item.list-view {
            display: none;
        }

        .video-list-card .video-item.card-view {
            display: block;
        }

        /* リスト表示時はバッジエリアを非表示 */
        .video-item.list-view .badge-area {
            display: none;
        }

        /* カード表示時はバッジエリアを表示 */
        .video-item.card-view .badge-area {
            display: flex;
        }
    </style>

    <script>
        let currentView = 'list';
        let currentOffset = {{ $recentVideos->count() }};
        let isLoading = false;
        let hasMoreVideos = true;
        // 全件数（サーバーサイドで計算済み。未定義の場合は0）
        const totalVideosCount = {{ isset($totalCount) ? $totalCount : 0 }};
        // 検索条件が存在するか（サーバーサイドで判定済み。未定義の場合は false）
        const hasSearchConditionsFlag = {{ (isset($hasSearchConditions) && $hasSearchConditions) ? 'true' : 'false' }};

        function switchView(view) {
            // URLパラメータに表示モードを保存
            const url = new URL(window.location);
            url.searchParams.set('view', view);
            window.location.href = url.toString();
        }

        function applyView(view) {
            currentView = view;
            const videoList = document.getElementById('video-list');
            const listBtn = document.getElementById('list-view-btn');
            const cardBtn = document.getElementById('card-view-btn');

            if (view === 'card') {
                // カード表示
                videoList.className = 'grid grid-cols-1 md:grid-cols-2 gap-6 video-list-card';

                // ボタンのアクティブ状態
                listBtn.className = 'inline-flex items-center justify-center h-8 px-3 py-1 text-xs font-medium rounded-md transition-colors bg-white text-gray-700 border border-gray-300 hover:bg-gray-50';
                cardBtn.className = 'inline-flex items-center justify-center h-8 px-3 py-1 text-xs font-medium rounded-md transition-colors bg-emerald-600 text-white';

                // 各動画アイテムをカード表示用に更新
                updateVideoItems('card');
            } else {
                // リスト表示
                videoList.className = 'space-y-2';

                // ボタンのアクティブ状態
                listBtn.className = 'inline-flex items-center justify-center h-8 px-3 py-1 text-xs font-medium rounded-md transition-colors bg-emerald-600 text-white';
                cardBtn.className = 'inline-flex items-center justify-center h-8 px-3 py-1 text-xs font-medium rounded-md transition-colors bg-white text-gray-700 border border-gray-300 hover:bg-gray-50';

                // 各動画アイテムをリスト表示用に更新
                updateVideoItems('list');
            }
        }

        function updateVideoItems(view) {
            const videoItems = document.querySelectorAll('.video-item');
            videoItems.forEach(item => {
                if (view === 'card') {
                    // カード表示のHTMLを生成
                    const videoData = extractVideoData(item);
                    item.innerHTML = generateCardHTML(videoData);
                    item.className = 'video-item card-view';
                } else {
                    // リスト表示のHTMLを生成
                    const videoData = extractVideoData(item);
                    item.innerHTML = generateListHTML(videoData);
                    item.className = 'video-item list-view';
                }
            });
        }

        function extractVideoData(item) {
            const link = item.querySelector('a');
            const title = item.querySelector('h3').textContent.trim();
            const img = item.querySelector('img');
            const channel = item.querySelector('.fa-tv')?.parentNode.textContent.trim() || '';
            const date = item.querySelector('.fa-calendar-alt')?.parentNode.textContent.trim() || '';
            const href = link.getAttribute('href');
            const videoId = link.getAttribute('href').split('/').pop();

            // 再生リスト情報を抽出
            const playlistButtons = item.querySelectorAll('.bg-purple-100');
            const playlists = [];
            playlistButtons.forEach(button => {
                const playlistTitle = button.textContent.trim();
                const onClick = button.getAttribute('onclick');
                const playlistIdMatch = onClick.match(/searchByPlaylist\(event,\s*'([^']+)'/);
                if (playlistIdMatch) {
                    playlists.push({
                        id: playlistIdMatch[1],
                        title: playlistTitle.replace(/^📋\s*/, '').trim()
                    });
                }
            });

            // 字幕情報を抽出
            const subtitleBadge = item.querySelector('.bg-green-100');
            const hasSubtitles = subtitleBadge !== null;
            let subtitleCount = 0;
            if (hasSubtitles) {
                const subtitleText = subtitleBadge.textContent.trim();
                const countMatch = subtitleText.match(/\((\d+)件\)/);
                subtitleCount = countMatch ? parseInt(countMatch[1]) : 0;
            }

            return {
                title,
                href,
                videoId,
                thumbnailUrl: img ? img.getAttribute('src') : null,
                channel: channel.replace(/\s+/g, ' '),
                date: date.replace(/\s+/g, ' '),
                hasStats: item.querySelector('.fa-eye') !== null,
                viewCount: item.querySelector('.fa-eye')?.parentNode.textContent.trim() || '',
                likeCount: item.querySelector('.fa-thumbs-up')?.parentNode.textContent.trim() || '',
                commentCount: item.querySelector('.fa-comment')?.parentNode.textContent.trim() || '',
                isShort: item.querySelector('.bg-pink-100') !== null,
                hasSubtitles: hasSubtitles,
                subtitleCount: subtitleCount,
                playlists: playlists
            };
        }

        // ===== HTML パーツ生成関数群 =====

        // サムネイル部分のHTML生成
        function generateThumbnailHTML(data, isCard = false) {
            const sizeClasses = isCard ? 'w-full sm:w-48 h-32 sm:h-27' : 'w-24 h-16';
            const playIconSize = isCard ? 'text-2xl' : 'text-lg';
            const buttonClasses = isCard ? 'absolute top-2 right-2 bg-black bg-opacity-70 text-white p-2 rounded hover:bg-opacity-90 transition-opacity' : 'absolute top-1 right-1 bg-black bg-opacity-70 text-white p-1 rounded text-xs hover:bg-opacity-90 transition-opacity';

            return `
                                                            <div class="flex-shrink-0 ${isCard ? 'w-full sm:w-48 mb-3 sm:mb-0 ' : ''}relative cursor-pointer"
                                                                onclick="event.stopPropagation(); window.open('https://www.youtube.com/watch?v=${data.videoId}', '_blank');"
                                                                title="YouTubeで動画を開く">
                                                                ${data.thumbnailUrl ?
                    `<img src="${data.thumbnailUrl}" alt="${data.title}" class="${sizeClasses} object-cover rounded">` :
                    `<div class="${sizeClasses} bg-gray-200 flex items-center justify-center rounded">
                                                                        <i class="fa-solid fa-video text-gray-400 ${isCard ? 'text-2xl' : ''}"></i>
                                                                    </div>`
                }
                                                                <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30 opacity-0 hover:opacity-100 transition-opacity rounded">
                                                                    <i class="fa-solid fa-play text-white ${playIconSize}"></i>
                                                                </div>
                                                                <button type="button"
                                                                    class="${buttonClasses}"
                                                                    onclick="event.stopPropagation(); window.open('https://www.youtube.com/watch?v=${data.videoId}', '_blank');"
                                                                    title="YouTubeで開く">
                                                                    <i class="fa-solid fa-external-link-alt"></i>
                                                                </button>
                                                            </div>
                                                        `;
        }

        // バッジ群のHTML生成
        function generateBadgesHTML(data, isCard = false, maxPlaylists = 2) {
            if (isCard) {
                maxPlaylists = 3;
            }

            let badges = [];

            // 字幕バッジ
            if (data.hasSubtitles) {
                const subtitleBadge = `<span class="inline-flex items-center px-2 ${isCard ? 'sm:px-2.5' : ''} py-${isCard ? '0.5' : '1'} rounded-full text-xs font-medium bg-teal-100 text-teal-800" title="字幕あり (${data.subtitleCount}件)">
                                                                <i class="fa-solid fa-closed-captioning mr-1"></i>
                                                                <span>${isCard ? '字幕' : '字幕'}</span>
                                                            </span>`;
                badges.push(subtitleBadge);
            }

            // ショートバッジ
            if (data.isShort) {
                const shortBadge = `<span class="inline-flex items-center px-2 ${isCard ? 'sm:px-2.5' : ''} py-${isCard ? '0.5' : '1'} rounded-full text-xs font-medium bg-pink-100 text-pink-800">
                                                                ${isCard ? '<i class="fa-solid fa-wand-magic-sparkles mr-1"></i>' : ''}
                                                                <span>ショート</span>
                                                            </span>`;
                badges.push(shortBadge);
            }

            // 再生リストバッジ
            if (data.playlists && data.playlists.length > 0) {
                const playlistBadges = data.playlists.slice(0, maxPlaylists).map(playlist => {
                    const titleLimit = isCard ? 20 : 15;
                    return `<button type="button"
                                                                    onclick="searchByPlaylist(event, '${playlist.id}', '${playlist.title.replace(/'/g, '\\\'')}')"
                                                                    class="inline-flex items-center px-2 ${isCard ? 'sm:px-2.5' : ''} py-${isCard ? '0.5' : '1'} rounded-full text-xs font-medium bg-purple-100 text-purple-800 hover:bg-purple-200 transition-colors"
                                                                    title="この再生リストで検索">
                                                                    <i class="fa-solid fa-list mr-1"></i>
                                                                    <span>${playlist.title.length > titleLimit ? playlist.title.substring(0, titleLimit) + '...' : playlist.title}</span>
                                                                </button>`;
                });
                badges.push(...playlistBadges);

                if (data.playlists.length > maxPlaylists) {
                    badges.push(`<span class="text-xs text-gray-500">他${data.playlists.length - maxPlaylists}件</span>`);
                }
            }

            return badges.join('');
        }

        // 統計情報のHTML生成
        function generateStatsHTML(data, isCard = false) {
            if (!data.hasStats) return '';

            const stats = [];

            if (data.viewCount) {
                const statHTML = isCard ?
                    `<span class="inline-flex items-center px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200">
                                                                    <i class="fa-solid fa-eye mr-1"></i>
                                                                    <span>${data.viewCount}</span>
                                                                </span>` :
                    `<span class="flex items-center text-red-600"><i class="fa-solid fa-eye mr-1"></i>${data.viewCount}</span>`;
                stats.push(statHTML);
            }

            if (data.likeCount) {
                const statHTML = isCard ?
                    `<span class="inline-flex items-center px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700 border border-blue-200">
                                                                    <i class="fa-solid fa-thumbs-up mr-1"></i>
                                                                    <span>${data.likeCount}</span>
                                                                </span>` :
                    `<span class="flex items-center text-blue-600"><i class="fa-solid fa-thumbs-up mr-1"></i>${data.likeCount}</span>`;
                stats.push(statHTML);
            }

            if (data.commentCount) {
                const statHTML = isCard ?
                    `<span class="inline-flex items-center px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                                                                    <i class="fa-solid fa-comment mr-1"></i>
                                                                    <span>${data.commentCount}</span>
                                                                </span>` :
                    `<span class="flex items-center text-green-600"><i class="fa-solid fa-comment mr-1"></i>${data.commentCount}</span>`;
                stats.push(statHTML);
            }

            if (stats.length === 0) return '';

            return isCard ?
                `<div class="mt-2 space-y-1">
                                                                <div class="flex items-center flex-wrap gap-1 sm:gap-2">
                                                                    ${stats.join('')}
                                                                </div>
                                                            </div>` :
                `<div class="mt-1 flex items-center space-x-3 text-xs">
                                                                ${stats.join('')}
                                                            </div>`;
        }

        // メタ情報（日付・チャンネル）のHTML生成
        function generateMetaHTML(data, isCard = false) {
            return isCard ?
                `<div class="text-xs sm:text-sm text-gray-500 mt-2 sm:mt-3 space-y-1">
                                                                <p class="flex items-center">
                                                                    <i class="fa-solid fa-calendar-alt w-3 sm:w-4 mr-1 text-center flex-shrink-0"></i>
                                                                    <span>${data.date}</span>
                                                                </p>
                                                                <p class="flex items-center truncate">
                                                                    <i class="fa-solid fa-tv w-3 sm:w-4 mr-1 text-center flex-shrink-0"></i>
                                                                    <span class="truncate">${data.channel}</span>
                                                                </p>
                                                            </div>` :
                `<div class="mt-1 flex items-center space-x-4 text-xs text-gray-500">
                                                                <span class="flex items-center">
                                                                    <i class="fa-solid fa-calendar-alt mr-1"></i>
                                                                    ${data.date}
                                                                </span>
                                                                <span class="flex items-center truncate">
                                                                    <i class="fa-solid fa-tv mr-1"></i>
                                                                    ${data.channel}
                                                                </span>
                                                            </div>`;
        }

        // ===== メイン生成関数 =====

        function generateListHTML(data) {
            return `
                                                            <div class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                                                                <a href="${data.href}" class="block">
                                                                    <div class="flex items-center p-3 hover:bg-gray-50">
                                                                        ${generateThumbnailHTML(data, false)}
                                                                        <div class="ml-4 flex-1 min-w-0">
                                                                            <div class="flex items-start justify-between">
                                                                                <div class="flex-1 min-w-0">
                                                                                    <h3 class="font-semibold text-sm text-gray-900 line-clamp-2 hover:text-blue-600 transition-colors">
                                                                                        ${data.title}
                                                                                    </h3>
                                                                                    ${generateMetaHTML(data, false)}
                                                                                    ${generateStatsHTML(data, false)}
                                                                                    ${data.hasSubtitles ? `
                                                                                        <div class="mt-1">
                                                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-teal-100 text-teal-800" title="字幕あり (${data.subtitleCount}件)">
                                                                                                <i class="fa-solid fa-closed-captioning mr-1"></i>
                                                                                                字幕 (${data.subtitleCount}件)
                                                                                            </span>
                                                                                        </div>
                                                                                    ` : ''}
                                                                                </div>
                                                                                <div class="ml-4 flex flex-col items-end space-y-1 badge-area">
                                                                                    ${generateBadgesHTML(data, false)}
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </a>
                                                            </div>
                                                        `;
        }

        function generateCardHTML(data) {
            return `
                                                            <div class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow relative">
                                                                <a href="${data.href}" class="block">
                                                                    <div class="flex flex-col sm:flex-row items-start p-3 sm:p-4 hover:bg-gray-50">
                                                                        ${generateThumbnailHTML(data, true)}
                                                                        <div class="sm:ml-4 flex-1 min-w-0 w-full">
                                                                            <h3 class="font-bold text-base sm:text-lg text-gray-900 line-clamp-2 leading-snug hover:text-blue-600 transition-colors">
                                                                                ${data.title}
                                                                            </h3>
                                                                            <div class="mt-2 flex flex-wrap items-center gap-1 sm:gap-2">
                                                                                ${generateBadgesHTML(data, true)}
                                                                            </div>
                                                                            ${generateMetaHTML(data, true)}
                                                                            ${generateStatsHTML(data, true)}
                                                                        </div>
                                                                    </div>
                                                                </a>
                                                            </div>
                                                        `;
        }


        // もっと見る機能
        function loadMoreVideos() {
            if (isLoading || !hasMoreVideos) return;

            isLoading = true;
            const loadMoreBtn = document.getElementById('load-more-btn');
            const loadMoreText = document.getElementById('load-more-text');

            // ボタンの状態を更新
            loadMoreBtn.disabled = true;
            loadMoreText.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>読み込み中...';

            // APIエンドポイントを決定
            const loadMoreUrl = @if($space->visibility === 2)
                '{{ route("guest.videos.load-more.public", $space->slug) }}'
            @else
                '{{ route("guest.videos.load-more.invite", [$space->slug, $space->invite_token]) }}'
            @endif;

            // 現在のURLパラメータを取得
            const urlParams = new URLSearchParams(window.location.search);
            const params = new URLSearchParams();
            params.set('offset', currentOffset);

            // 検索条件を追加
            if (urlParams.get('keyword')) params.set('keyword', urlParams.get('keyword'));
            // 検索対象の配列パラメータを追加
            if (urlParams.getAll('search_targets[]').length > 0) {
                urlParams.getAll('search_targets[]').forEach(target => {
                    params.append('search_targets[]', target);
                });
            }
            if (urlParams.get('playlist_id')) params.set('playlist_id', urlParams.get('playlist_id'));
            if (urlParams.get('video_type')) params.set('video_type', urlParams.get('video_type'));
            if (urlParams.get('speaker')) params.set('speaker', urlParams.get('speaker'));
            if (urlParams.get('date_from')) params.set('date_from', urlParams.get('date_from'));
            if (urlParams.get('date_to')) params.set('date_to', urlParams.get('date_to'));
            if (urlParams.get('sort')) params.set('sort', urlParams.get('sort'));
            if (urlParams.get('view')) params.set('view', urlParams.get('view'));

            fetch(`${loadMoreUrl}?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.videos && data.videos.length > 0) {
                        const videoList = document.getElementById('video-list');

                        data.videos.forEach(video => {
                            const videoElement = createVideoElement(video);
                            videoList.appendChild(videoElement);
                        });

                        currentOffset += data.videos.length;
                        hasMoreVideos = data.hasMore;

                        if (!hasMoreVideos) {
                            document.getElementById('load-more-container').style.display = 'none';
                        }
                    } else {
                        hasMoreVideos = false;
                        document.getElementById('load-more-container').style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error loading more videos:', error);
                    loadMoreText.textContent = 'エラーが発生しました';
                    setTimeout(() => {
                        loadMoreText.textContent = 'もっと見る';
                        loadMoreBtn.disabled = false;
                    }, 2000);
                })
                .finally(() => {
                    isLoading = false;
                    if (hasMoreVideos) {
                        loadMoreBtn.disabled = false;
                        loadMoreText.innerHTML = 'もっと見る';
                    }
                });
        }

        // 新しい動画要素を作成
        function createVideoElement(video) {
            const videoElement = document.createElement('div');
            videoElement.className = 'video-item list-view';

            const videoData = {
                title: video.title,
                href: video.url,
                videoId: video.youtube_video_id,
                thumbnailUrl: video.thumbnail_url,
                channel: video.channel.name,
                date: video.published_at,
                hasStats: video.view_count || video.like_count || video.comment_count,
                viewCount: video.view_count ? formatNumber(video.view_count) : '',
                likeCount: video.like_count ? formatNumber(video.like_count) : '',
                commentCount: video.comment_count ? formatNumber(video.comment_count) : '',
                isShort: video.video_type === 'short',
                hasSubtitles: video.dialogues_count > 0,
                subtitleCount: video.dialogues_count || 0,
                playlists: video.playlists || []
            };

            if (currentView === 'card') {
                videoElement.innerHTML = generateCardHTML(videoData);
                videoElement.className = 'video-item card-view';
            } else {
                videoElement.innerHTML = generateListHTML(videoData);
                videoElement.className = 'video-item list-view';
            }

            return videoElement;
        }

        // 数値フォーマット関数
        function formatNumber(num) {
            return new Intl.NumberFormat('ja-JP').format(num);
        }

        // 再生リスト検索関数
        function searchByPlaylist(event, playlistId, playlistTitle) {
            // イベントの伝播と既定の動作を停止
            if (event) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
            }

            const url = new URL(window.location);
            url.searchParams.set('playlist_id', playlistId);
            // 既存の検索条件をクリア
            url.searchParams.delete('keyword');
            url.searchParams.delete('search_targets[]');
            url.searchParams.delete('video_type');
            url.searchParams.delete('speaker');
            url.searchParams.delete('date_from');
            url.searchParams.delete('date_to');
            // デフォルトの並び順に戻す
            url.searchParams.delete('sort');
            // 現在の表示モードを保持
            if (!url.searchParams.has('view')) {
                url.searchParams.set('view', currentView);
            }
            window.location.href = url.toString();
        }

        // 検索リセット関数
        function resetSearch() {
            const url = new URL(window.location);
            // 現在の表示モードを保持
            const currentViewMode = url.searchParams.get('view') || currentView;
            // 全ての検索条件をクリア
            url.searchParams.delete('keyword');
            url.searchParams.delete('search_targets[]');
            url.searchParams.delete('playlist_id');
            url.searchParams.delete('video_type');
            url.searchParams.delete('speaker');
            url.searchParams.delete('date_from');
            url.searchParams.delete('date_to');
            url.searchParams.delete('sort');
            // 表示モードを設定（listでない場合のみ）
            if (currentViewMode !== 'list') {
                url.searchParams.set('view', currentViewMode);
            }
            window.location.href = url.toString();
        }

        // 並び順トグル関数
        function toggleSort(field) {
            const url = new URL(window.location);
            const currentSort = url.searchParams.get('sort') || 'newest';
            const currentViewMode = url.searchParams.get('view') || currentView;

            let newSort;

            switch (field) {
                case 'published_at':
                    // 投稿日時: newest ⇔ oldest
                    if (currentSort === 'oldest') {
                        newSort = 'newest';
                    } else {
                        newSort = 'oldest';
                    }
                    break;
                case 'view_count':
                    // 再生回数: views_desc ⇔ views_asc
                    if (currentSort === 'views_asc') {
                        newSort = 'views_desc';
                    } else {
                        newSort = 'views_asc';
                    }
                    break;
                case 'like_count':
                    // 高評価: likes_desc ⇔ likes_asc
                    if (currentSort === 'likes_asc') {
                        newSort = 'likes_desc';
                    } else {
                        newSort = 'likes_asc';
                    }
                    break;
                case 'title':
                    // タイトル: title_asc ⇔ title_desc
                    if (currentSort === 'title_desc') {
                        newSort = 'title_asc';
                    } else {
                        newSort = 'title_desc';
                    }
                    break;
            }

            // 並び順を設定（newestの場合は削除してデフォルトにする）
            if (newSort === 'newest') {
                url.searchParams.delete('sort');
            } else {
                url.searchParams.set('sort', newSort);
            }

            // 表示モードを保持
            if (currentViewMode !== 'list') {
                url.searchParams.set('view', currentViewMode);
            }

            window.location.href = url.toString();
        }

        // 初期表示時にもっと見るボタンの表示を制御
        document.addEventListener('DOMContentLoaded', function () {
            // URLパラメータから表示モードを取得して適用
            const urlParams = new URLSearchParams(window.location.search);
            const viewMode = urlParams.get('view') || 'list';
            applyView(viewMode);

            // 並び順ボタンの状態を更新
            updateSortButtons();

            // 動画が12件未満の場合はもっと見るボタンを非表示
            if ({{ $recentVideos->count() }} < 12) {
                const totalVideos = {{ $space->videos()->count() }};
                if (totalVideos <= {{ $recentVideos->count() }}) {
                    document.getElementById('load-more-container').style.display = 'none';
                    hasMoreVideos = false;
                }
            }

            // 検索が行われている場合、サーバーで計算した全件数と現在表示件数を比較して
            // 追加のページがないと判定される場合はもっと見るボタンを非表示にする
            if (hasSearchConditionsFlag) {
                if (totalVideosCount <= {{ $recentVideos->count() }}) {
                    const container = document.getElementById('load-more-container');
                    if (container) container.style.display = 'none';
                    hasMoreVideos = false;
                }
            }
        });

        // 並び順ボタンの状態を更新
        function updateSortButtons() {
            const urlParams = new URLSearchParams(window.location.search);
            const currentSort = urlParams.get('sort') || 'newest';

            // 全てのボタンを非アクティブにリセット
            const buttons = [
                'sort-published-at-btn',
                'sort-view-count-btn',
                'sort-like-count-btn',
                'sort-title-btn'
            ];

            buttons.forEach(btnId => {
                const btn = document.getElementById(btnId);
                if (btn) {
                    btn.className = 'inline-flex items-center justify-center h-8 px-3 py-1 text-xs font-medium rounded-md transition-colors bg-white text-gray-700 border border-gray-300 hover:bg-gray-50';
                }
            });

            // 現在の並び順に応じてボタンをアクティブにし、矢印を更新
            let activeBtn, arrowDirection;

            switch (currentSort) {
                case 'newest':
                case 'oldest':
                    activeBtn = document.getElementById('sort-published-at-btn');
                    arrowDirection = currentSort === 'oldest' ? 'up' : 'down';
                    break;
                case 'views_desc':
                case 'views_asc':
                    activeBtn = document.getElementById('sort-view-count-btn');
                    arrowDirection = currentSort === 'views_asc' ? 'up' : 'down';
                    break;
                case 'likes_desc':
                case 'likes_asc':
                    activeBtn = document.getElementById('sort-like-count-btn');
                    arrowDirection = currentSort === 'likes_asc' ? 'up' : 'down';
                    break;
                case 'title_asc':
                case 'title_desc':
                    activeBtn = document.getElementById('sort-title-btn');
                    arrowDirection = currentSort === 'title_desc' ? 'down' : 'up';
                    break;
            }

            if (activeBtn) {
                activeBtn.className = 'inline-flex items-center justify-center h-8 px-3 py-1 text-xs font-medium rounded-md transition-colors bg-blue-600 text-white';

                // 矢印を更新
                const arrow = activeBtn.querySelector('.fa-arrow-up, .fa-arrow-down');
                if (arrow) {
                    arrow.className = `fa-solid fa-arrow-${arrowDirection} ml-1 text-xs`;
                }
            }
        }

        // 特定の時間から動画を開く関数
        function openVideoAtTime(youtubeVideoId, timestamp) {
            const url = `https://www.youtube.com/watch?v=${youtubeVideoId}&t=${timestamp}s`;
            window.open(url, '_blank');
        }
    </script>
@endsection