<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\DialogueController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\TimelineController;
use App\Http\Controllers\HistoryCategoryController;
use App\Http\Controllers\HistoryEntryController;
use App\Http\Controllers\EventCategoryController;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('spaces', SpaceController::class);
    Route::post('/spaces/{space}/generate-invite-token', [SpaceController::class, 'generateInviteToken'])->name('spaces.generate-invite-token');

    Route::post('/channels/find-id', [ChannelController::class, 'findIdByUrl'])->name('channels.findId');
    Route::post('/spaces/{space}/channels/estimate-count', [ChannelController::class, 'estimateCount'])->name('channels.estimate-count');
    Route::resource('spaces.channels', ChannelController::class)->shallow();
    Route::resource('spaces.events', EventController::class)->except(['index', 'show']);

    Route::get('/spaces/{space}/videos', [VideoController::class, 'index'])->name('videos.index');
    Route::get('/videos/{video}', [VideoController::class, 'show'])->name('videos.show');
    Route::post('/spaces/{space}/videos/sync', [VideoController::class, 'sync'])->name('videos.sync');
    Route::post('/spaces/{space}/videos/sync-background', [VideoController::class, 'syncBackground'])->name('videos.sync-background');
    Route::get('/spaces/{space}/videos/sync-progress', [VideoController::class, 'syncProgress'])->name('videos.sync-progress');
    Route::delete('/spaces/{space}/videos/sync-progress', [VideoController::class, 'clearSyncProgress'])->name('videos.clear-sync-progress');

    // バルク操作のルートを先に定義（resourceルートより前に配置）
    Route::post('/dialogues/bulk-delete', [DialogueController::class, 'bulkDelete'])->name('dialogues.bulk-delete');
    Route::post('/dialogues/bulk-update-speaker', [DialogueController::class, 'bulkUpdateSpeaker'])->name('dialogues.bulk-update-speaker');

    Route::resource('videos.dialogues', DialogueController::class)->except(['index', 'show'])->shallow();
    Route::post('/videos/{video}/dialogues/import', [DialogueController::class, 'import'])->name('videos.dialogues.import');
    Route::post('/dialogues/{dialogue}/update', [DialogueController::class, 'update'])->name('dialogues.update');

    // 年表カテゴリ管理
    Route::resource('spaces.history-categories', HistoryCategoryController::class)->shallow()->only(['index', 'store', 'update', 'destroy']);

    // 年表管理
    Route::resource('spaces.timelines', TimelineController::class)->shallow();

    // 年表項目管理 (年表に紐づく)
    Route::resource('timelines.history-entries', HistoryEntryController::class)->shallow()->except(['index']);

    Route::resource('event-categories', EventCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('event-categories/reorder', [EventCategoryController::class, 'reorder'])->name('event-categories.reorder');
});

// ゲスト用ルート（認証不要）
use App\Http\Controllers\GuestController;

// 全体公開スペース表示（slug使用）
Route::get('/public/{space:slug}', [GuestController::class, 'showPublicSpace'])->name('guest.space.public')->where('space', '[a-zA-Z0-9\-]+');

// ゲスト向け：コンテンツ情報（イベント一覧・年表）表示（全体公開）
Route::get('/public/{space:slug}/content', [GuestController::class, 'showPublicContent'])->name('guest.space.content.public')->where('space', '[a-zA-Z0-9\-]+');

// 動画詳細（全体公開）
Route::get('/public/{space:slug}/video/{video}', [GuestController::class, 'showVideo'])->name('guest.video.public')->where(['space' => '[a-zA-Z0-9\-]+']);

// 検索機能（全体公開）
Route::get('/public/{space:slug}/search', [GuestController::class, 'search'])->name('guest.search.public')->where(['space' => '[a-zA-Z0-9\-]+']);

// 動画一覧の追加読み込み（全体公開）
Route::get('/public/{space:slug}/videos/load-more', [GuestController::class, 'loadMoreVideosPublic'])->name('guest.videos.load-more.public')->where(['space' => '[a-zA-Z0-9\-]+']);

// 限定公開スペース表示（slug + invite_token使用）- 管理画面のルートと衝突しないよう、より具体的なパターンに変更
Route::get('/invite/{space:slug}/{token}', [GuestController::class, 'showInviteSpace'])->name('guest.space.invite')->where(['space' => '[a-zA-Z0-9\-]+', 'token' => '[a-zA-Z0-9]+']);

// ゲスト向け：コンテンツ情報（イベント一覧・年表）表示（限定公開）
Route::get('/invite/{space:slug}/{token}/content', [GuestController::class, 'showInviteContent'])->name('guest.space.content.invite')->where(['space' => '[a-zA-Z0-9\-]+', 'token' => '[a-zA-Z0-9]+']);

// 動画詳細（限定公開）
Route::get('/invite/{space:slug}/{token}/video/{video}', [GuestController::class, 'showVideo'])->name('guest.video.invite')->where(['space' => '[a-zA-Z0-9\-]+', 'token' => '[a-zA-Z0-9]+']);

// 検索機能（限定公開）
Route::get('/invite/{space:slug}/{token}/search', [GuestController::class, 'search'])->name('guest.search.invite')->where(['space' => '[a-zA-Z0-9\-]+', 'token' => '[a-zA-Z0-9]+']);

// 動画一覧の追加読み込み（限定公開）
Route::get('/invite/{space:slug}/{token}/videos/load-more', [GuestController::class, 'loadMoreVideosInvite'])->name('guest.videos.load-more.invite')->where(['space' => '[a-zA-Z0-9\-]+', 'token' => '[a-zA-Z0-9]+']);

// 1. Googleの認証ページにリダイレクトさせるためのルート
Route::get('auth/google', [LoginController::class, 'redirectToGoogle'])->name('login.google');

// 2. Googleでの認証後に戻ってくるルート（コールバック）
Route::get('auth/google/callback', [LoginController::class, 'handleGoogleCallback']);

require __DIR__ . '/auth.php';
