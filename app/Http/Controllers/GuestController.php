<?php

namespace App\Http\Controllers;

use App\Models\Space;
use App\Models\Video;
use App\Models\Dialogue;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GuestController extends Controller
{
    /**
     * 全体公開スペースの表示（slug使用）
     */
    public function showPublicSpace(Request $request, Space $space)
    {
        // visibility=2（全体公開）でない場合は404
        if ($space->visibility !== 2) {
            abort(404);
        }

        // 検索条件の取得
        $keyword = $request->get('keyword');
        $searchTargets = $request->get('search_targets', []);
        $playlistId = $request->get('playlist_id');
        $videoType = $request->get('video_type');
        $speaker = $request->get('speaker');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $sort = $request->get('sort', 'newest');

        // 動画クエリの構築
        $videosQuery = $this->buildVideoQuery($space, $keyword, $searchTargets, $playlistId, $videoType, $speaker, $dateFrom, $dateTo, $sort);

        // 最新動画を取得（初期表示分）
        $recentVideos = $videosQuery
            ->limit(12)
            ->get();

        // プレイリスト一覧を取得（検索フィルター用）
        $playlists = $space->playlists()->orderBy('title')->get();

        // 発言者一覧を取得（検索フィルター用）
        $speakers = DB::table('dialogues')
            ->join('videos', 'dialogues.video_id', '=', 'videos.id')
            ->where('videos.space_id', $space->id)
            ->whereNotNull('dialogues.speaker')
            ->where('dialogues.speaker', '!=', '')
            ->distinct()
            ->orderBy('dialogues.speaker')
            ->pluck('dialogues.speaker');

        // マッチした字幕データを取得
        $matchedDialogues = $this->getMatchedDialogues($space, $keyword, $searchTargets, $speaker);

        return view('guest.space', compact('space', 'recentVideos', 'playlists', 'speakers', 'matchedDialogues'));
    }

    /**
     * 限定公開スペースの表示（slug + invite_token使用）
     */
    public function showInviteSpace(Request $request, Space $space, $token)
    {
        // invite_tokenが一致するかチェック
        if ($space->invite_token !== $token) {
            abort(404);
        }

        // visibility=1（限定公開）でない場合は404
        if ($space->visibility !== 1) {
            abort(404);
        }

        // 検索条件の取得
        $keyword = $request->get('keyword');
        $searchTargets = $request->get('search_targets', []);
        $playlistId = $request->get('playlist_id');
        $videoType = $request->get('video_type');
        $speaker = $request->get('speaker');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $sort = $request->get('sort', 'newest');

        // 動画クエリの構築
        $videosQuery = $this->buildVideoQuery($space, $keyword, $searchTargets, $playlistId, $videoType, $speaker, $dateFrom, $dateTo, $sort);

        // 最新動画を取得（初期表示分）
        $recentVideos = $videosQuery
            ->limit(12)
            ->get();

        // プレイリスト一覧を取得（検索フィルター用）
        $playlists = $space->playlists()->orderBy('title')->get();

        // 発言者一覧を取得（検索フィルター用）
        $speakers = DB::table('dialogues')
            ->join('videos', 'dialogues.video_id', '=', 'videos.id')
            ->where('videos.space_id', $space->id)
            ->whereNotNull('dialogues.speaker')
            ->where('dialogues.speaker', '!=', '')
            ->distinct()
            ->orderBy('dialogues.speaker')
            ->pluck('dialogues.speaker');

        // マッチした字幕データを取得
        $matchedDialogues = $this->getMatchedDialogues($space, $keyword, $searchTargets, $speaker);

        return view('guest.space', compact('space', 'recentVideos', 'playlists', 'speakers', 'matchedDialogues'));
    }

    /**
     * 動画詳細の表示（全体公開・限定公開対応）
     */
    public function showVideo(Request $request, Space $space, Video $video, $token = null)
    {
        // 限定公開の場合はトークンをチェック
        if ($token !== null) {
            if ($space->invite_token !== $token || $space->visibility !== 1) {
                abort(404);
            }
        } else {
            // 全体公開の場合
            if ($space->visibility !== 2) {
                abort(404);
            }
        }

        // 動画がスペースに属しているかチェック
        if ($video->space_id !== $space->id) {
            abort(404);
        }

        // 再生リスト情報と文字起こしデータを取得
        $video->load('playlists');
        $dialogues = $video->dialogues()
            ->orderBy('timestamp')
            ->get();

        return view('guest.video', compact('space', 'video', 'dialogues'));
    }

    /**
     * 検索機能（全体公開・限定公開対応）
     */
    public function search(Request $request, Space $space, $token = null)
    {
        // 限定公開の場合はトークンをチェック
        if ($token !== null) {
            if ($space->invite_token !== $token || $space->visibility !== 1) {
                abort(404);
            }
        } else {
            // 全体公開の場合
            if ($space->visibility !== 2) {
                abort(404);
            }
        }

        $query = $request->get('q', '');
        $results = collect();

        if ($query) {
            // 文字起こしデータから検索
            $dialogues = Dialogue::whereHas('video', function (Builder $videoQuery) use ($space) {
                $videoQuery->where('space_id', $space->id);
            })
                ->where('dialogue', 'like', '%' . $query . '%')
                ->with(['video.channel'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $results = $dialogues;
        }

        // スペース識別子を渡す（ビューでのURL生成用）
        $spaceIdentifier = $token ? $space->slug . '/' . $token : $space->slug;

        return view('guest.search', compact('space', 'query', 'results', 'spaceIdentifier'));
    }

    // app/Http/Controllers/GuestController.php

    /**
     * ゲスト向けコンテンツ情報（イベント一覧・年表）表示（全体公開）
     */
    public function showPublicContent(Request $request, Space $space)
    {
        if ($space->visibility !== 2) {
            abort(404);
        }

        // イベントごとの最新の公演日(performance_date)を取得して、その最大値で降順ソートする
        $events = $space->events()
            ->with(['schedules', 'ticketSales', 'category'])
            ->withMax('schedules', 'performance_date')
            // withMax によって生成される alias は `relation_max_column` 形式になります
            ->orderByDesc('schedules_max_performance_date')
            ->get();
        $timelines = $space->timelines()->orderBy('id', 'desc')->get();

        // 各年表に、カテゴリでグループ分けした項目データを追加する
        $timelines->each(function ($timeline) {
            $entries = $timeline->historyEntries()
                ->with(['category', 'verticalAxis'])
                ->get()
                ->sortBy(fn($entry) => $entry->axis_type === 'date' ? $entry->axis_date : $entry->axis_custom_value);

            $timeline->groupedEntries = $entries->groupBy('category.name');
        });

        return view('guest.content', compact('space', 'events', 'timelines'));
    }

    /**
     * ゲスト向けコンテンツ情報（イベント一覧・年表）表示（限定公開）
     */
    public function showInviteContent(Request $request, Space $space, $token)
    {
        if ($space->invite_token !== $token || $space->visibility !== 1) {
            abort(404);
        }

        // イベントごとの最新の公演日(performance_date)を取得して、その最大値で降順ソートする
        $events = $space->events()
            ->with(['schedules', 'ticketSales', 'category'])
            ->withMax('schedules', 'performance_date')
            // withMax によって生成される alias は `relation_max_column` 形式になります
            ->orderByDesc('schedules_max_performance_date')
            ->get();
        $timelines = $space->timelines()->orderBy('id', 'desc')->get();

        // 各年表に、カテゴリでグループ分けした項目データを追加する
        $timelines->each(function ($timeline) {
            $entries = $timeline->historyEntries()
                ->with(['category', 'verticalAxis'])
                ->get()
                ->sortBy(fn($entry) => $entry->axis_type === 'date' ? $entry->axis_date : $entry->axis_custom_value);

            $timeline->groupedEntries = $entries->groupBy('category.name');
        });

        return view('guest.content', compact('space', 'events', 'timelines'));
    }

    /**
     * 動画一覧の追加読み込み（全体公開用）
     */
    public function loadMoreVideosPublic(Request $request, Space $space)
    {
        // visibility=2（全体公開）でない場合は404
        if ($space->visibility !== 2) {
            abort(404);
        }

        $offset = $request->get('offset', 0);
        $limit = 12;

        // 検索条件の取得
        $keyword = $request->get('keyword');
        $searchTargets = $request->get('search_targets', []);
        $playlistId = $request->get('playlist_id');
        $videoType = $request->get('video_type');
        $speaker = $request->get('speaker');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $sort = $request->get('sort', 'newest');

        // 動画クエリの構築
        $videosQuery = $this->buildVideoQuery($space, $keyword, $searchTargets, $playlistId, $videoType, $speaker, $dateFrom, $dateTo, $sort);

        $videos = $videosQuery
            ->skip($offset)
            ->limit($limit)
            ->get();

        // 全件数を取得してhasMoreを判定
        $totalCount = $this->buildVideoQuery($space, $keyword, $searchTargets, $playlistId, $videoType, $speaker, $dateFrom, $dateTo, $sort)->count();
        $hasMore = $totalCount > ($offset + $limit);

        return response()->json([
            'videos' => $videos->map(function ($video) use ($space) {
                return [
                    'id' => $video->id,
                    'title' => $video->title,
                    'thumbnail_url' => $video->thumbnail_url,
                    'youtube_video_id' => $video->youtube_video_id,
                    'published_at' => $video->published_at->format('Y/m/d'),
                    'channel' => [
                        'name' => $video->channel->name
                    ],
                    'view_count' => $video->view_count,
                    'like_count' => $video->like_count,
                    'comment_count' => $video->comment_count,
                    'video_type' => $video->video_type,
                    'playlists' => $video->playlists->map(function ($playlist) {
                        return [
                            'id' => $playlist->id,
                            'title' => $playlist->title
                        ];
                    }),
                    'dialogues' => $video->dialogues ? $video->dialogues->map(function ($dialogue) {
                        return [
                            'timestamp' => $dialogue->timestamp,
                            'speaker' => $dialogue->speaker,
                            'dialogue' => $dialogue->dialogue
                        ];
                    }) : [],
                    'url' => route('guest.video.public', [$space->slug, $video])
                ];
            }),
            'hasMore' => $hasMore
        ]);
    }

    /**
     * 動画一覧の追加読み込み（限定公開用）
     */
    public function loadMoreVideosInvite(Request $request, Space $space, $token)
    {
        // invite_tokenが一致するかチェック
        if ($space->invite_token !== $token || $space->visibility !== 1) {
            abort(404);
        }

        $offset = $request->get('offset', 0);
        $limit = 12;

        // 検索条件の取得
        $keyword = $request->get('keyword');
        $searchTargets = $request->get('search_targets', []);
        $playlistId = $request->get('playlist_id');
        $videoType = $request->get('video_type');
        $speaker = $request->get('speaker');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $sort = $request->get('sort', 'newest');

        // 動画クエリの構築
        $videosQuery = $this->buildVideoQuery($space, $keyword, $searchTargets, $playlistId, $videoType, $speaker, $dateFrom, $dateTo, $sort);

        $videos = $videosQuery
            ->skip($offset)
            ->limit($limit)
            ->get();

        // 全件数を取得してhasMoreを判定
        $totalCount = $this->buildVideoQuery($space, $keyword, $searchTargets, $playlistId, $videoType, $speaker, $dateFrom, $dateTo, $sort)->count();
        $hasMore = $totalCount > ($offset + $limit);

        return response()->json([
            'videos' => $videos->map(function ($video) use ($space, $token) {
                return [
                    'id' => $video->id,
                    'title' => $video->title,
                    'thumbnail_url' => $video->thumbnail_url,
                    'youtube_video_id' => $video->youtube_video_id,
                    'published_at' => $video->published_at->format('Y/m/d'),
                    'channel' => [
                        'name' => $video->channel->name
                    ],
                    'view_count' => $video->view_count,
                    'like_count' => $video->like_count,
                    'comment_count' => $video->comment_count,
                    'video_type' => $video->video_type,
                    'playlists' => $video->playlists->map(function ($playlist) {
                        return [
                            'id' => $playlist->id,
                            'title' => $playlist->title
                        ];
                    }),
                    'dialogues' => $video->dialogues ? $video->dialogues->map(function ($dialogue) {
                        return [
                            'timestamp' => $dialogue->timestamp,
                            'speaker' => $dialogue->speaker,
                            'dialogue' => $dialogue->dialogue
                        ];
                    }) : [],
                    'url' => route('guest.video.invite', [$space->slug, $token, $video])
                ];
            }),
            'hasMore' => $hasMore
        ]);
    }

    /**
     * 動画検索クエリの構築
     */
    private function buildVideoQuery($space, $keyword = null, $searchTargets = null, $playlistId = null, $videoType = null, $speaker = null, $dateFrom = null, $dateTo = null, $sort = 'newest')
    {
        $query = $space->videos()->with(['channel', 'playlists']);

        // 字幕や発言者での検索が含まれる場合はdialoguesもロード
        $includeDialogues = false;
        if ($keyword && $searchTargets && in_array('dialogue', $searchTargets)) {
            $includeDialogues = true;
        }
        if ($speaker) {
            $includeDialogues = true;
        }

        if ($includeDialogues) {
            // 字幕表示用に全ての字幕を取得（表示時にフィルタリング）
            $query->with(['dialogues' => function ($dialogueQuery) {
                $dialogueQuery->orderBy('timestamp');
            }]);
        }

        // キーワード検索
        if ($keyword) {
            // 検索対象が指定されていない場合はデフォルトで全て対象
            if (!$searchTargets || empty($searchTargets)) {
                $searchTargets = ['title', 'description', 'dialogue', 'playlist'];
            }

            $query->where(function ($q) use ($keyword, $searchTargets) {
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
            $query->whereHas('dialogues', function ($q) use ($speaker) {
                $q->where('speaker', $speaker);
            });
        }

        // 再生リストフィルター
        if ($playlistId) {
            $query->whereHas('playlists', function ($q) use ($playlistId) {
                $q->where('playlist_id', $playlistId);
            });
        }

        // 動画種別フィルター
        if ($videoType) {
            $query->where('video_type', $videoType);
        }

        // 公開日フィルター
        if ($dateFrom) {
            $query->where('published_at', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('published_at', '<=', $dateTo . ' 23:59:59');
        }

        // 並び順
        switch ($sort) {
            case 'oldest':
                $query->orderBy('published_at', 'asc');
                break;
            case 'views_desc':
                $query->orderBy('view_count', 'desc');
                break;
            case 'views_asc':
                $query->orderBy('view_count', 'asc');
                break;
            case 'likes_desc':
                $query->orderBy('like_count', 'desc');
                break;
            case 'likes_asc':
                $query->orderBy('like_count', 'asc');
                break;
            case 'title_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'title_desc':
                $query->orderBy('title', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('published_at', 'desc');
                break;
        }

        return $query;
    }

    /**
     * スペース識別子（slugまたはinvite_token）からSpaceを取得
     */
    private function getSpaceByIdentifier($identifier)
    {
        // まずslugで検索（全体公開用）
        $space = Space::where('slug', $identifier)
            ->where('visibility', 2)
            ->first();

        // 見つからなければinvite_tokenで検索（限定公開用）
        if (!$space) {
            $space = Space::where('invite_token', $identifier)
                ->whereIn('visibility', [1, 2])
                ->first();
        }

        if (!$space) {
            abort(404);
        }

        return $space;
    }

    /**
     * マッチした字幕データを取得
     */
    private function getMatchedDialogues($space, $keyword = null, $searchTargets = null, $speaker = null)
    {
        $matchedDialogues = collect();

        // 字幕検索または発言者検索の条件がある場合のみ処理
        if (($keyword && $searchTargets && in_array('dialogue', $searchTargets)) || $speaker) {
            $query = DB::table('dialogues')
                ->join('videos', 'dialogues.video_id', '=', 'videos.id')
                ->join('channels', 'videos.channel_id', '=', 'channels.id')
                ->where('videos.space_id', $space->id)
                ->select(
                    'dialogues.*',
                    'videos.title as video_title',
                    'videos.youtube_video_id',
                    'videos.thumbnail_url',
                    'videos.published_at',
                    'channels.name as channel_name'
                );

            // 字幕内容での検索
            if ($keyword && $searchTargets && in_array('dialogue', $searchTargets)) {
                $query->where('dialogues.dialogue', 'like', '%' . $keyword . '%');
            }

            // 発言者での検索
            if ($speaker) {
                if ($keyword && $searchTargets && in_array('dialogue', $searchTargets)) {
                    $query->orWhere('dialogues.speaker', $speaker);
                } else {
                    $query->where('dialogues.speaker', $speaker);
                }
            }

            $matchedDialogues = $query->orderBy('videos.published_at', 'desc')
                ->orderBy('dialogues.timestamp')
                ->get();
        }

        return $matchedDialogues;
    }
}
