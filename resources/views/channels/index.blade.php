<x-app-layout>
    <x-slot name="header">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('spaces.index') }}"
                        class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                        <i class="fa-solid fa-home mr-2"></i>
                        マイスペース
                    </a>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fa-solid fa-chevron-right text-gray-400 mx-2"></i>
                        <span class="text-sm font-medium text-gray-500">
                            {{ $space->name }}
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
                    <div
                        class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-4 border-b pb-4 space-y-4 sm:space-y-0">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">
                                <i class="fa-solid fa-tv mr-2"></i>{{ $space->name }}のチャンネル一覧
                            </h3>
                            @if($channels->count() > 0 && isset($totalEstimatedVideos))
                                <div class="mt-2 text-sm text-gray-600">
                                    @if(isset($hasApiError) && $hasApiError)
                                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                                            <div class="flex items-center">
                                                <i class="fa-solid fa-exclamation-triangle text-yellow-600 mr-2"></i>
                                                <div>
                                                    <div class="font-medium text-yellow-800">処理件数を取得中</div>
                                                    <div class="text-yellow-700 text-sm">
                                                        一部のチャンネル情報の取得に失敗しました。同期実行時に正確な件数が表示されます。
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                            <div class="flex items-center">
                                                <i class="fa-solid fa-info-circle text-blue-600 mr-2"></i>
                                                <div>
                                                    <div class="font-medium text-blue-800">同期予定動画数</div>
                                                    <div class="text-blue-700">
                                                        <span
                                                            class="font-bold text-lg">{{ number_format($totalEstimatedVideos) }}</span>件
                                                        @php
                                                            $maxVideosPerSync = config('services.youtube.max_videos_per_sync', 1000);
                                                            $syncRounds = ceil($totalEstimatedVideos / $maxVideosPerSync);
                                                        @endphp
                                                        @if($totalEstimatedVideos > $maxVideosPerSync)
                                                            <span class="text-sm">（約{{ $syncRounds }}回の同期が必要）</span>
                                                        @endif
                                                        @if($totalEstimatedVideos > 1500)
                                                            <div class="mt-2 text-red-700 text-sm font-medium">
                                                                <i class="fa-solid fa-exclamation-triangle mr-1"></i>
                                                                同期制限（1,500件）を超過しています。絞り込み機能を使用してください。
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            @if($channels->count() > 0)
                                <a href="{{ route('videos.index', $space) }}"
                                    class="inline-flex items-center px-4 py-2 bg-green-500 border border-transparent rounded-md font-bold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition ease-in-out duration-150 justify-center sm:justify-start">
                                    <i class="fa-solid fa-clapperboard mr-2"></i>
                                    <span class="hidden sm:inline">動画・文字起こし管理</span>
                                    <span class="sm:hidden">動画管理</span>
                                </a>
                                <div class="flex flex-col sm:flex-row gap-2">
                                    @php
                                        $syncLimit = 1500;
                                        $canSync = !isset($totalEstimatedVideos) || $totalEstimatedVideos <= $syncLimit;
                                    @endphp

                                    @if($canSync)
                                        {{-- 通常同期 --}}
                                        <form action="{{ route('videos.sync', $space) }}" method="POST" id="sync-form"
                                            class="hidden">
                                            @csrf
                                            <x-primary-button id="sync-button" type="button"
                                                class="bg-indigo-500 hover:bg-indigo-700 justify-center sm:justify-start w-full sm:w-auto">
                                                <span id="sync-button-icon">
                                                    <i class="fa-solid fa-rotate mr-2"></i>
                                                </span>
                                                <span id="sync-button-text" class="hidden sm:inline">通常同期</span>
                                                <span class="sm:hidden">通常</span>
                                            </x-primary-button>
                                        </form>

                                        {{-- バックグラウンド同期（設定付き） --}}
                                        <x-primary-button id="sync-settings-button" type="button"
                                            class="bg-indigo-600 hover:bg-indigo-700 justify-center sm:justify-start w-full sm:w-auto">
                                            <i class="fa-solid fa-cog mr-2"></i>
                                            <span class="hidden sm:inline">同期設定</span>
                                            <span class="sm:hidden">設定</span>
                                        </x-primary-button>
                                    @else
                                        {{-- 制限超過時でも設定は表示 --}}
                                        <x-primary-button id="sync-settings-button" type="button"
                                            class="bg-orange-600 hover:bg-orange-700 justify-center sm:justify-start w-full sm:w-auto">
                                            <i class="fa-solid fa-filter mr-2"></i>
                                            <span class="hidden sm:inline">絞り込み同期</span>
                                            <span class="sm:hidden">絞り込み</span>
                                        </x-primary-button>
                                    @endif

                                    {{-- 進捗クリアボタン --}}
                                    <button type="button" id="clear-progress-button"
                                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-2 rounded text-sm font-medium inline-flex items-center justify-center w-full sm:w-auto"
                                        title="同期進捗をクリアして再実行を可能にします">
                                        <i class="fa-solid fa-broom mr-2"></i>
                                        <span class="hidden sm:inline">進捗クリア</span>
                                        <span class="sm:hidden">クリア</span>
                                    </button>
                                </div>
                            @else
                                <button disabled
                                    class="inline-flex items-center justify-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-500 uppercase tracking-widest cursor-not-allowed w-full sm:w-auto">
                                    <i class="fa-solid fa-clapperboard mr-2"></i>
                                    <span class="hidden sm:inline">動画・文字起こし管理</span>
                                    <span class="sm:hidden">動画管理</span>
                                </button>
                                <button disabled
                                    class="inline-flex items-center justify-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-500 uppercase tracking-widest cursor-not-allowed w-full sm:w-auto">
                                    <i class="fa-solid fa-cloud-arrow-up mr-2"></i>
                                    <span class="hidden sm:inline">動画情報を同期</span>
                                    <span class="sm:hidden">同期</span>
                                </button>
                            @endif
                            <x-button-link-primary href="{{ route('spaces.channels.create', $space) }}"
                                class="justify-center sm:justify-start w-full sm:w-auto">
                                <i class="fa-solid fa-plus mr-2"></i>
                                <span>チャンネルを追加</span>
                            </x-button-link-primary>
                        </div>
                    </div>

                    {{-- チャンネル変更についての注意書き --}}
                    @if($channels->count() > 0)
                        <div class="mb-4 p-3 bg-amber-50 border-l-4 border-amber-400 rounded">
                            <div class="flex items-start">
                                <i class="fa-solid fa-exclamation-triangle text-amber-500 mr-2 mt-0.5"></i>
                                <div>
                                    <p class="text-sm text-amber-700">
                                        <strong>ご注意：</strong>登録済みのチャンネル情報は変更できません。
                                    </p>
                                    <p class="text-xs text-amber-600 mt-1">
                                        チャンネル情報を変更したい場合は、現在のチャンネルを削除してから新しく作成し直してください。
                                    </p>
                                    <p class="text-xs text-red-600 mt-2 font-medium">
                                        <i class="fa-solid fa-exclamation-circle mr-1"></i>
                                        <strong>重要：</strong>チャンネルを削除すると、そのチャンネルに関連する動画データや文字起こしデータもすべて削除されます。
                                    </p>
                                    <p class="text-xs text-blue-600 mt-2">
                                        <i class="fa-solid fa-info-circle mr-1"></i>
                                        <strong>補足：</strong>このアプリからチャンネルを削除しても、YouTube上の動画やチャンネル自体は影響を受けません。
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <ul class="space-y-4">
                        @forelse ($channels as $channel)
                            <li
                                class="border p-4 rounded-lg flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-3 sm:space-y-0">
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold">{{ $channel->name }}</h3>
                                    <p class="text-sm text-gray-500 break-all">{{ $channel->youtube_channel_id }}</p>
                                    @if(isset($channelStats[$channel->id]))
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @if(isset($channelStats[$channel->id]['error']))
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <i class="fa-solid fa-clock mr-1"></i>
                                                    統計情報取得中...
                                                </span>
                                            @else
                                                @if(isset($channelStats[$channel->id]['videoCount']))
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <i class="fa-solid fa-video mr-1"></i>
                                                        {{ number_format($channelStats[$channel->id]['videoCount']) }}件の動画
                                                    </span>
                                                @endif
                                                @if(isset($channelStats[$channel->id]['subscriberCount']))
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        <i class="fa-solid fa-users mr-1"></i>
                                                        {{ number_format($channelStats[$channel->id]['subscriberCount']) }}人
                                                    </span>
                                                @endif
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <div class="flex flex-col sm:flex-row gap-2 sm:gap-2">
                                    {{-- <x-button-link-secondary href="{{ route('channels.edit', $channel) }}"
                                        class="justify-center sm:justify-start">
                                        <i class="fa-solid fa-pencil mr-2"></i>
                                        <span>編集</span>
                                    </x-button-link-secondary> --}}
                                    <form action="{{ route('channels.destroy', $channel) }}" method="POST"
                                        onsubmit="return confirm('【重要】このチャンネルを削除しますか？\n\n削除すると以下のデータもすべて削除されます：\n• チャンネルに関連する動画データ\n• 動画の文字起こしデータ\n• 動画の統計情報\n\n※ YouTube上の動画やチャンネルは影響を受けません。\n\nこの操作は取り消せません。本当に削除しますか？');">
                                        @csrf
                                        @method('DELETE')
                                        <x-button-danger class="justify-center sm:justify-start w-full sm:w-auto">
                                            <i class="fa-solid fa-trash mr-2"></i>
                                            <span>削除</span>
                                        </x-button-danger>
                                    </form>
                                </div>
                            </li>
                        @empty
                            <div class="text-center py-8">
                                <div
                                    class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 mb-4">
                                    <i class="fa-solid fa-tv text-gray-400 text-xl"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">チャンネルが登録されていません</h3>
                                <p class="text-sm text-gray-500 mb-4">
                                    YouTubeチャンネルを登録すると、動画の管理や文字起こし機能を利用できます。
                                </p>
                                <p class="text-xs text-gray-400">
                                    <i class="fa-solid fa-info-circle mr-1"></i>
                                    チャンネルを追加すると、「動画・文字起こし管理」と「動画情報を同期」ボタンが有効になります。
                                </p>
                            </div>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- 同期設定モーダル --}}
    <div id="sync-settings-modal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">同期設定</h3>
                    <button id="close-settings-modal" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>

                <form id="sync-settings-form">
                    @csrf

                    {{-- 期間設定 --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">同期期間</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="date_range" value="all" class="mr-2" checked>
                                <span class="text-sm">すべての動画</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="date_range" value="last_year" class="mr-2">
                                <span class="text-sm">過去1年</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="date_range" value="last_6months" class="mr-2">
                                <span class="text-sm">過去6ヶ月</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="date_range" value="last_3months" class="mr-2">
                                <span class="text-sm">過去3ヶ月</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="date_range" value="custom" class="mr-2">
                                <span class="text-sm">カスタム期間</span>
                            </label>
                        </div>

                        {{-- カスタム期間の日付入力 --}}
                        <div id="custom-date-range" class="mt-2 space-y-2 hidden">
                            <div>
                                <label class="block text-xs text-gray-600">開始日</label>
                                <input type="date" name="start_date"
                                    class="w-full px-3 py-1 text-sm border border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600">終了日</label>
                                <input type="date" name="end_date"
                                    class="w-full px-3 py-1 text-sm border border-gray-300 rounded">
                            </div>
                        </div>
                    </div>

                    {{-- 動画タイプ設定 --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">動画タイプ</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="video_types[]" value="all" class="mr-2" checked>
                                <span class="text-sm">すべての動画</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="video_types[]" value="video" class="mr-2">
                                <span class="text-sm">通常動画のみ</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="video_types[]" value="short" class="mr-2">
                                <span class="text-sm">ショート動画のみ</span>
                            </label>
                        </div>
                    </div>

                    {{-- 最大件数表示 --}}
                    <div class="mb-4 p-3 bg-gray-50 rounded">
                        <div class="text-sm text-gray-600">
                            <div class="flex justify-between">
                                <span>予想処理件数:</span>
                                <span id="estimated-count" class="font-medium">計算中...</span>
                            </div>
                        </div>
                    </div>

                    {{-- ボタン --}}
                    <div class="flex space-x-2">
                        <button type="button" id="cancel-settings"
                            class="flex-1 px-4 py-2 text-sm bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                            キャンセル
                        </button>
                        <button type="button" id="start-sync"
                            class="flex-1 px-4 py-2 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700">
                            同期開始
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- 同期進捗モーダル --}}
    <div id="sync-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 mb-4">
                    <i class="fa-solid fa-sync-alt fa-spin text-indigo-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">動画情報を同期中</h3>

                {{-- 進捗バー --}}
                <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                    <div id="progress-bar" class="bg-indigo-600 h-2.5 rounded-full transition-all duration-300"
                        style="width: 0%"></div>
                </div>

                {{-- 進捗テキスト --}}
                <div class="text-sm text-gray-600 mb-4">
                    <div id="progress-text">準備中...</div>
                    <div id="progress-detail" class="text-xs text-gray-500 mt-1"></div>
                </div>

                {{-- 現在の処理内容 --}}
                <div class="text-xs text-gray-500 bg-gray-50 p-3 rounded">
                    <div id="current-task">同期処理を開始しています...</div>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-gray-400">この処理には数分かかる場合があります。<br>ページを閉じずにお待ちください。</p>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            // DOMの読み込みが完了したら実行
            document.addEventListener('DOMContentLoaded', function () {
                const syncButton = document.getElementById('sync-button');
                const syncBackgroundButton = document.getElementById('sync-background-button');
                const syncSettingsButton = document.getElementById('sync-settings-button');
                const syncModal = document.getElementById('sync-modal');
                const syncSettingsModal = document.getElementById('sync-settings-modal');
                const progressBar = document.getElementById('progress-bar');
                const progressText = document.getElementById('progress-text');
                const progressDetail = document.getElementById('progress-detail');
                const currentTask = document.getElementById('current-task');

                let progressCheckInterval;

                // 同期設定モーダルの制御
                if (syncSettingsButton) {
                    syncSettingsButton.addEventListener('click', function () {
                        syncSettingsModal.classList.remove('hidden');
                    });
                }

                // 設定モーダルを閉じる
                document.getElementById('close-settings-modal').addEventListener('click', function () {
                    syncSettingsModal.classList.add('hidden');
                });

                document.getElementById('cancel-settings').addEventListener('click', function () {
                    syncSettingsModal.classList.add('hidden');
                });

                // 進捗クリアボタンの制御
                const clearProgressButton = document.getElementById('clear-progress-button');
                if (clearProgressButton) {
                    clearProgressButton.addEventListener('click', function () {
                        if (confirm('同期進捗をクリアしますか？\n進行中の同期がある場合、状態がリセットされます。')) {
                            clearSyncProgress();
                        }
                    });
                }

                // カスタム期間の表示切り替えと件数更新
                document.querySelectorAll('input[name="date_range"]').forEach(radio => {
                    radio.addEventListener('change', function () {
                        const customDateRange = document.getElementById('custom-date-range');
                        if (this.value === 'custom') {
                            customDateRange.classList.remove('hidden');
                        } else {
                            customDateRange.classList.add('hidden');
                        }
                        updateEstimatedCount();
                    });
                });

                // 動画タイプ変更時に件数更新
                document.querySelectorAll('input[name="video_types[]"]').forEach(checkbox => {
                    checkbox.addEventListener('change', updateEstimatedCount);
                });

                // カスタム日付変更時に件数更新
                document.querySelectorAll('input[name="start_date"], input[name="end_date"]').forEach(input => {
                    input.addEventListener('change', updateEstimatedCount);
                });

                // 初回表示時に件数を計算
                setTimeout(updateEstimatedCount, 100);

                // 同期設定からの開始
                document.getElementById('start-sync').addEventListener('click', function () {
                    // 設定モーダルを閉じる
                    syncSettingsModal.classList.add('hidden');

                    // 進捗モーダルを表示
                    syncModal.classList.remove('hidden');

                    // 初期状態を設定
                    updateProgress(0, 'バックグラウンド同期を開始しています...', '', '準備中...');

                    startBackgroundSyncWithSettings();
                });

                // 通常同期ボタンクリック時の処理（従来通り）
                if (syncButton) {
                    syncButton.addEventListener('click', function () {
                        // モーダルを表示
                        syncModal.classList.remove('hidden');

                        // ボタンを無効化
                        syncButton.disabled = true;

                        // 初期状態を設定
                        updateProgress(0, '同期を開始しています...', '', '準備中...');

                        // AJAX で同期処理を実行
                        startSync();
                    });
                }

                function updateProgress(percentage, text, detail, task) {
                    progressBar.style.width = percentage + '%';
                    progressText.textContent = text;
                    progressDetail.textContent = detail;
                    currentTask.textContent = task;
                }

                function startSync() {
                    const formData = new FormData();
                    formData.append('_token', document.querySelector('input[name="_token"]').value);

                    fetch('{{ route("videos.sync", $space) }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(response => {
                            if (!response.ok) {
                                // ステータスコードに応じた分かりやすいエラーメッセージ
                                let errorMessage = 'YouTube動画の同期に失敗しました';
                                if (response.status === 403) {
                                    errorMessage = 'YouTube APIの利用制限に達しました。しばらく時間をおいてから再度お試しください。';
                                } else if (response.status === 404) {
                                    errorMessage = 'チャンネルが見つかりませんでした。チャンネルIDをご確認ください。';
                                } else if (response.status === 500) {
                                    errorMessage = 'サーバーで問題が発生しました。時間をおいてから再度お試しください。';
                                } else if (response.status === 429) {
                                    errorMessage = 'YouTubeからのデータ取得頻度が上限に達しました。しばらく時間をおいてから再度お試しください。';
                                } else if (response.status >= 400 && response.status < 500) {
                                    errorMessage = 'リクエストに問題があります。ページを更新してから再度お試しください。';
                                } else {
                                    errorMessage = 'YouTube動画の同期中に問題が発生しました。インターネット接続をご確認の上、再度お試しください。';
                                }
                                throw new Error(errorMessage);
                            }
                            return response.json();
                        })
                        .then(data => {
                            // 進捗シミュレーションを停止
                            if (window.progressInterval) {
                                clearInterval(window.progressInterval);
                            }

                            // 成功時の処理 - 100%まで段階的に完了
                            let finalProgress = 98;
                            const completionInterval = setInterval(() => {
                                finalProgress += 0.5;
                                if (finalProgress >= 100) {
                                    finalProgress = 100;
                                    clearInterval(completionInterval);
                                    updateProgress(100, '同期完了', data.message || '正常に完了しました', '完了');

                                    setTimeout(() => {
                                        syncModal.classList.add('hidden');
                                        syncButton.disabled = false;

                                        // 従来通りsessionメッセージでリダイレクト
                                        window.location.href = '{{ route("spaces.channels.index", $space) }}?success=' + encodeURIComponent(data.message);
                                    }, 2000);
                                } else {
                                    updateProgress(finalProgress, `完了処理中 (${Math.round(finalProgress)}%)`, 'データの保存を完了しています', '完了処理中...');
                                }
                            }, 100);
                        })
                        .catch(error => {
                            // 進捗シミュレーションを停止
                            if (window.progressInterval) {
                                clearInterval(window.progressInterval);
                            }

                            console.error('同期エラー:', error);

                            // ユーザー向けのエラーメッセージを整理
                            let userMessage = error.message;
                            let errorTitle = '同期に失敗しました';

                            // ネットワークエラーの場合
                            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                                userMessage = 'インターネット接続に問題があります。接続をご確認の上、再度お試しください。';
                                errorTitle = '接続エラー';
                            }
                            // タイムアウトエラーの場合
                            else if (error.message.includes('timeout')) {
                                userMessage = '処理に時間がかかりすぎています。しばらく時間をおいてから再度お試しください。';
                                errorTitle = 'タイムアウトエラー';
                            }
                            // 一般的なエラーで詳細がない場合
                            else if (!userMessage || userMessage === 'Failed to fetch') {
                                userMessage = 'YouTube動画の取得中に問題が発生しました。しばらく時間をおいてから再度お試しください。';
                            }

                            updateProgress(0, errorTitle, userMessage, '同期を中止しました');

                            setTimeout(() => {
                                syncModal.classList.add('hidden');
                                syncButton.disabled = false;

                                // エラーメッセージをより分かりやすく表示
                                window.location.href = '{{ route("spaces.channels.index", $space) }}?error=' + encodeURIComponent(userMessage);
                            }, 4000); // エラーメッセージを読む時間を延長
                        });

                    // 模擬的な進捗更新（実際の進捗はバックエンドから取得）
                    simulateProgress();
                }

                function simulateProgress() {
                    let progress = 0;
                    let phase = 1; // 進捗フェーズ管理

                    const interval = setInterval(() => {
                        // フェーズ1: 0-70% (通常の進捗)
                        if (phase === 1) {
                            progress += Math.random() * 6 + 2; // 2-8%ずつ増加
                            if (progress >= 70) {
                                progress = 70;
                                phase = 2;
                            }
                        }
                        // フェーズ2: 70-90% (少し遅く)
                        else if (phase === 2) {
                            progress += Math.random() * 3 + 1; // 1-4%ずつ増加
                            if (progress >= 90) {
                                progress = 90;
                                phase = 3;
                            }
                        }
                        // フェーズ3: 90-98% (最終処理、細かく動く)
                        else if (phase === 3) {
                            progress += Math.random() * 1 + 0.5; // 0.5-1.5%ずつ増加
                            if (progress >= 98) {
                                progress = 98;
                                phase = 4;
                            }
                        }
                        // フェーズ4: 98%で停止（実際の処理完了を待つ）
                        else {
                            // 98%で停止し、微細な動きだけ見せる
                            progress = 98 + Math.sin(Date.now() / 1000) * 0.5; // 微細な振動
                        }

                        // タスク名の更新
                        let task = '同期を準備中...';
                        let detail = '';

                        if (progress > 5) {
                            task = 'チャンネル情報を取得中...';
                            detail = 'YouTube APIに接続しています';
                        }
                        if (progress > 15) {
                            task = '再生リストを取得中...';
                            detail = 'チャンネルの再生リストを読み込んでいます';
                        }
                        if (progress > 30) {
                            task = '動画一覧を取得中...';
                            detail = '動画リストを収集しています';
                        }
                        if (progress > 50) {
                            task = '動画の統計情報を取得中...';
                            detail = '再生回数、いいね数などを取得しています';
                        }
                        if (progress > 70) {
                            task = '動画の詳細情報を取得中...';
                            detail = '説明文、タグなどを取得しています';
                        }
                        if (progress > 85) {
                            task = 'データベースに保存中...';
                            detail = '取得したデータを整理して保存しています';
                        }
                        if (progress > 95) {
                            task = '最終処理中...';
                            detail = 'データの整合性をチェックしています';
                        }

                        updateProgress(Math.round(progress * 10) / 10, `進行中 (${Math.round(progress)}%)`, detail, task);
                    }, 800); // より頻繁に更新 (1.5秒 → 0.8秒)

                    // 進捗シミュレーションを保存して、実際の処理完了時に停止できるようにする
                    window.progressInterval = interval;
                }

                // バックグラウンド同期開始
                function startBackgroundSync() {
                    const formData = new FormData();
                    formData.append('_token', document.querySelector('input[name="_token"]').value);

                    fetch('{{ route("videos.sync-background", $space) }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(response => {
                            if (!response.ok) {
                                let errorMessage = 'バックグラウンド同期の開始に失敗しました';
                                if (response.status === 409) {
                                    errorMessage = '既に同期処理が実行中です。完了をお待ちください。';
                                } else if (response.status >= 400 && response.status < 500) {
                                    errorMessage = 'リクエストに問題があります。ページを更新してから再度お試しください。';
                                }
                                throw new Error(errorMessage);
                            }
                            return response.json();
                        })
                        .then(data => {
                            // バックグラウンド処理開始成功
                            updateProgress(5, 'バックグラウンド処理を開始しました', 'キューに追加されました');

                            // 進捗をポーリングで確認
                            progressCheckInterval = setInterval(checkProgress, 2000); // 2秒ごと
                        })
                        .catch(error => {
                            console.error('バックグラウンド同期エラー:', error);
                            updateProgress(0, 'エラーが発生しました', error.message, 'エラー');

                            setTimeout(() => {
                                syncModal.classList.add('hidden');
                                syncBackgroundButton.disabled = false;
                            }, 3000);
                        });
                }

                // 設定付きバックグラウンド同期開始
                function startBackgroundSyncWithSettings() {
                    const formData = new FormData();
                    formData.append('_token', document.querySelector('input[name="_token"]').value);

                    // 期間設定を取得
                    const dateRange = document.querySelector('input[name="date_range"]:checked').value;
                    formData.append('date_range', dateRange);

                    if (dateRange === 'custom') {
                        const startDate = document.querySelector('input[name="start_date"]').value;
                        const endDate = document.querySelector('input[name="end_date"]').value;
                        if (startDate) formData.append('start_date', startDate);
                        if (endDate) formData.append('end_date', endDate);
                    }

                    // 動画タイプ設定を取得
                    const videoTypes = [];
                    document.querySelectorAll('input[name="video_types[]"]:checked').forEach(checkbox => {
                        videoTypes.push(checkbox.value);
                    });
                    videoTypes.forEach(type => formData.append('video_types[]', type));

                    fetch('{{ route("videos.sync-background", $space) }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(response => {
                            if (!response.ok) {
                                let errorMessage = 'バックグラウンド同期の開始に失敗しました';
                                if (response.status === 409) {
                                    errorMessage = '既に同期処理が実行中です。完了をお待ちください。';
                                } else if (response.status >= 400 && response.status < 500) {
                                    errorMessage = 'リクエストに問題があります。ページを更新してから再度お試しください。';
                                }
                                throw new Error(errorMessage);
                            }
                            return response.json();
                        })
                        .then(data => {
                            // バックグラウンド処理開始成功
                            updateProgress(5, 'バックグラウンド処理を開始しました', 'キューに追加されました');

                            // 進捗をポーリングで確認
                            progressCheckInterval = setInterval(checkProgress, 2000); // 2秒ごと
                        })
                        .catch(error => {
                            console.error('設定付きバックグラウンド同期エラー:', error);
                            updateProgress(0, 'エラーが発生しました', error.message, 'エラー');

                            setTimeout(() => {
                                syncModal.classList.add('hidden');
                            }, 3000);
                        });
                }

                // 進捗確認
                function checkProgress() {
                    fetch('{{ route("videos.sync-progress", $space) }}', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(response => {
                            if (!response.ok) {
                                if (response.status === 404) {
                                    // 進捗情報が見つからない場合は確認を停止
                                    clearInterval(progressCheckInterval);
                                    updateProgress(0, '進捗情報が見つかりません', '同期処理が完了したか、エラーが発生した可能性があります', 'エラー');
                                    return;
                                }
                                throw new Error('進捗確認に失敗しました');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (!data) return;

                            updateProgress(data.progress, data.message, '', data.current_task);

                            // 完了または失敗時の処理
                            if (data.status === 'completed') {
                                clearInterval(progressCheckInterval);
                                updateProgress(100, '同期完了', data.message, '完了');

                                setTimeout(() => {
                                    syncModal.classList.add('hidden');
                                    syncBackgroundButton.disabled = false;
                                    // 成功メッセージ付きでページをリロード
                                    window.location.href = '{{ route("spaces.channels.index", $space) }}?success=' + encodeURIComponent(data.message || 'バックグラウンド同期が正常に完了しました');
                                }, 3000);
                            } else if (data.status === 'failed') {
                                clearInterval(progressCheckInterval);
                                updateProgress(0, '同期に失敗しました', data.message, 'エラー');

                                setTimeout(() => {
                                    syncModal.classList.add('hidden');
                                    syncBackgroundButton.disabled = false;
                                    // エラーメッセージ付きでページをリロード
                                    window.location.href = '{{ route("spaces.channels.index", $space) }}?error=' + encodeURIComponent(data.message || 'バックグラウンド同期中にエラーが発生しました');
                                }, 4000);
                            }
                        })
                        .catch(error => {
                            console.error('進捗確認エラー:', error);
                            clearInterval(progressCheckInterval);
                            updateProgress(0, '進捗確認エラー', error.message, 'エラー');

                            setTimeout(() => {
                                syncModal.classList.add('hidden');
                                syncBackgroundButton.disabled = false;
                            }, 3000);
                        });
                }

                // 予想処理件数を更新
                function updateEstimatedCount() {
                    const formData = new FormData();
                    formData.append('_token', document.querySelector('input[name="_token"]').value);

                    // 期間設定を取得
                    const dateRange = document.querySelector('input[name="date_range"]:checked')?.value || 'all';
                    formData.append('date_range', dateRange);

                    if (dateRange === 'custom') {
                        const startDate = document.querySelector('input[name="start_date"]')?.value;
                        const endDate = document.querySelector('input[name="end_date"]')?.value;
                        if (startDate) formData.append('start_date', startDate);
                        if (endDate) formData.append('end_date', endDate);
                    }

                    // 動画タイプ設定を取得
                    const videoTypes = [];
                    document.querySelectorAll('input[name="video_types[]"]:checked').forEach(checkbox => {
                        videoTypes.push(checkbox.value);
                    });
                    videoTypes.forEach(type => formData.append('video_types[]', type));

                    // 件数を取得
                    fetch('{{ route("channels.estimate-count", $space) }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            const estimatedElement = document.getElementById('estimated-count');
                            if (data.success) {
                                estimatedElement.textContent = new Intl.NumberFormat('ja-JP').format(data.estimated_count) + '件';
                                estimatedElement.className = data.estimated_count > 1500 ? 'font-medium text-red-600' : 'font-medium text-green-600';
                            } else {
                                estimatedElement.textContent = 'エラー';
                                estimatedElement.className = 'font-medium text-red-600';
                            }
                        })
                        .catch(error => {
                            console.error('件数取得エラー:', error);
                            const estimatedElement = document.getElementById('estimated-count');
                            estimatedElement.textContent = '計算失敗';
                            estimatedElement.className = 'font-medium text-red-600';
                        });
                }

                // 進捗クリア関数
                function clearSyncProgress() {
                    const clearButton = document.getElementById('clear-progress-button');
                    if (clearButton) {
                        clearButton.disabled = true;
                        clearButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i><span class="hidden sm:inline">処理中...</span><span class="sm:hidden">処理中</span>';
                    }

                    fetch('{{ route("videos.clear-sync-progress", $space) }}', {
                        method: 'DELETE',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // 成功メッセージを表示
                                alert('同期進捗をクリアしました。再度同期を実行できます。');

                                // ページをリロード
                                window.location.reload();
                            } else {
                                alert('進捗クリアに失敗しました: ' + (data.message || '不明なエラー'));
                            }
                        })
                        .catch(error => {
                            console.error('進捗クリアエラー:', error);
                            alert('進捗クリア中にエラーが発生しました。');
                        })
                        .finally(() => {
                            if (clearButton) {
                                clearButton.disabled = false;
                                clearButton.innerHTML = '<i class="fa-solid fa-broom mr-2"></i><span class="hidden sm:inline">進捗クリア</span><span class="sm:hidden">クリア</span>';
                            }
                        });
                }
            });
        </script>
    @endpush
</x-app-layout>