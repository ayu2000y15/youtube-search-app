@forelse ($videos as $video)
    {{-- 表示方法に応じたレイアウト --}}
    @if(request('view', 'list') != 'card')
        {{-- リスト表示 --}}
        <li class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <a href="{{ route('videos.show', $video) }}" class="block">
                <div class="flex items-center p-3 hover:bg-gray-50">
                    {{-- サムネイル（小さめ） --}}
                    <div class="flex-shrink-0 relative cursor-pointer"
                        onclick="event.stopPropagation(); window.open('https://www.youtube.com/watch?v={{ $video->youtube_video_id }}', '_blank');"
                        title="YouTubeで動画を開く">
                        <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}" class="w-24 h-16 object-cover rounded">
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
                            </div>

                            {{-- バッジエリア --}}
                            <div class="ml-4 flex items-center">
                                @if ($video->video_type === 'short')
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-pink-100 text-pink-800">
                                        ショート
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </li>
    @else
        {{-- カード表示（従来のレイアウト） --}}
        <li class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow relative">
            <a href="{{ route('videos.show', $video) }}" class="block">
                <div class="flex flex-col sm:flex-row items-start p-3 sm:p-4 hover:bg-gray-50">
                    {{-- サムネイル画像 --}}
                    <div class="flex-shrink-0 w-full sm:w-48 mb-3 sm:mb-0 relative cursor-pointer"
                        onclick="event.stopPropagation(); window.open('https://www.youtube.com/watch?v={{ $video->youtube_video_id }}', '_blank');"
                        title="YouTubeで動画を開く">
                        <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}"
                            class="w-full sm:w-48 h-32 sm:h-27 object-cover rounded">
                        {{-- 再生アイコンオーバーレイ --}}
                        <div
                            class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30 opacity-0 hover:opacity-100 transition-opacity rounded">
                            <i class="fa-solid fa-play text-white text-2xl"></i>
                        </div>
                        {{-- YouTube外部リンクアイコン --}}
                        <button type="button"
                            class="absolute top-2 right-2 bg-black bg-opacity-70 text-white p-2 rounded hover:bg-opacity-90 transition-opacity"
                            onclick="event.stopPropagation(); window.open('https://www.youtube.com/watch?v={{ $video->youtube_video_id }}', '_blank');"
                            title="YouTubeで開く">
                            <i class="fa-solid fa-external-link-alt"></i>
                        </button>
                    </div>

                    {{-- 動画情報 --}}
                    <div class="sm:ml-4 flex-1 min-w-0 w-full">
                        <h3
                            class="font-bold text-base sm:text-lg text-gray-900 line-clamp-2 leading-snug hover:text-blue-600 transition-colors">
                            {{ $video->title }}
                        </h3>

                        {{-- タグ表示エリア --}}
                        <div class="mt-2 flex flex-wrap items-center gap-1 sm:gap-2" onclick="event.stopPropagation();">
                            @if ($video->video_type === 'short')
                                <span
                                    class="inline-flex items-center px-2 sm:px-2.5 py-0.5 rounded-full text-xs font-medium bg-pink-100 text-pink-800">
                                    <i class="fa-solid fa-wand-magic-sparkles mr-1"></i><span class="hidden sm:inline">
                                        ショート</span><span class="sm:hidden">ショート</span>
                                </span>
                            @endif
                            @foreach ($video->playlists as $playlist)
                                <button type="button"
                                    class="inline-flex items-center px-2 sm:px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 hover:bg-purple-200 transition-colors cursor-pointer relative z-50"
                                    onclick="event.preventDefault(); event.stopPropagation(); event.stopImmediatePropagation(); window.location.href='{{ route('videos.index', array_merge(['space' => $video->channel->space, 'playlist_id' => $playlist->id], request()->only(['view', 'keyword', 'video_type']))) }}';"
                                    onmousedown="event.stopPropagation();" onmouseup="event.stopPropagation();"
                                    title="この再生リストで絞り込む">
                                    <i class="fa-solid fa-list mr-1"></i><span
                                        class="hidden sm:inline">{{ $playlist->title }}</span><span
                                        class="sm:hidden">{{ Str::limit($playlist->title, 10) }}</span>
                                </button>
                            @endforeach
                        </div>

                        {{-- 公開日・チャンネル名 --}}
                        <div class="text-xs sm:text-sm text-gray-500 mt-2 sm:mt-3 space-y-1">
                            <p class="flex items-center">
                                <i class="fa-solid fa-calendar-alt w-3 sm:w-4 mr-1 text-center flex-shrink-0"></i>
                                <span>{{ optional($video->published_at)->format('Y/m/d H:i') ?? '' }}</span>
                            </p>
                            <p class="flex items-center truncate">
                                <i class="fa-solid fa-tv w-3 sm:w-4 mr-1 text-center flex-shrink-0"></i>
                                <span class="truncate">{{ $video->channel->name }}</span>
                            </p>
                        </div>

                        {{-- 統計情報 --}}
                        @if($video->view_count || $video->like_count || $video->comment_count)
                            <div class="mt-2 space-y-1">
                                <div class="flex items-center flex-wrap gap-1 sm:gap-2">
                                    @if($video->view_count)
                                        <span
                                            class="inline-flex items-center px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200">
                                            <i class="fa-solid fa-eye mr-1"></i>
                                            <span class="hidden sm:inline">{{ number_format($video->view_count) }}</span>
                                            <span class="sm:hidden">{{ number_format($video->view_count / 1000) }}k</span>
                                        </span>
                                    @endif
                                    @if($video->like_count)
                                        <span
                                            class="inline-flex items-center px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700 border border-blue-200">
                                            <i class="fa-solid fa-thumbs-up mr-1"></i>
                                            <span class="hidden sm:inline">{{ number_format($video->like_count) }}</span>
                                            <span
                                                class="sm:hidden">{{ $video->like_count >= 1000 ? number_format($video->like_count / 1000, 1) . 'k' : number_format($video->like_count) }}</span>
                                        </span>
                                    @endif
                                    @if($video->comment_count)
                                        <span
                                            class="inline-flex items-center px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                                            <i class="fa-solid fa-comment mr-1"></i>
                                            <span class="hidden sm:inline">{{ number_format($video->comment_count) }}</span>
                                            <span
                                                class="sm:hidden">{{ $video->comment_count >= 1000 ? number_format($video->comment_count / 1000, 1) . 'k' : number_format($video->comment_count) }}</span>
                                        </span>
                                    @endif
                                </div>
                                @if($video->statistics_updated_at)
                                    <div class="flex items-center text-xs sm:text-sm text-gray-500">
                                        <i class="fa-solid fa-clock mr-1 flex-shrink-0"></i>
                                        <span class="hidden sm:inline">統計更新:
                                            {{ optional($video->statistics_updated_at)->format('Y/m/d H:i') ?? '' }}</span>
                                        <span
                                            class="sm:hidden">{{ optional($video->statistics_updated_at)->format('m/d H:i') ?? '' }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </a>
        </li>
    @endif
@empty
    {{-- ページが2ページ目以降の場合は何も表示しない --}}
    @if ($videos->currentPage() == 1)
        <li class="text-center text-gray-500 py-8">
            @if(request()->hasAny(['playlist_id', 'video_type', 'keyword']))
                指定された条件の動画は見つかりませんでした。
            @else
                まだ動画が同期されていません。「チャンネル管理」画面から動画を同期してください。
            @endif
        </li>
    @endif
@endforelse