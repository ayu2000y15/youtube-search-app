<x-app-layout>
    <x-slot name="header">
        <nav class="w-full" aria-label="Breadcrumb">
            <div class="overflow-x-auto pb-2" style="scrollbar-width: none; -ms-overflow-style: none;">
                <style>
                    .overflow-x-auto::-webkit-scrollbar {
                        display: none;
                    }
                </style>
                <ol class="inline-flex items-center space-x-1 md:space-x-3 whitespace-nowrap min-w-max">
                    <li class="inline-flex items-center flex-shrink-0">
                        <a href="{{ route('spaces.index') }}"
                            class="inline-flex items-center text-xs sm:text-sm font-medium text-gray-700 hover:text-blue-600">
                            <i class="fa-solid fa-home mr-1 text-xs"></i>
                            <span class="hidden sm:inline">マイスペース</span>
                            <span class="sm:hidden">ホーム</span>
                        </a>
                    </li>
                    <li class="flex-shrink-0">
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-gray-400 mx-1 text-xs"></i>
                            <a href="{{ route('spaces.channels.index', $video->space) }}"
                                class="text-xs sm:text-sm font-medium text-gray-700 hover:text-blue-600 truncate max-w-[80px] sm:max-w-none"
                                title="{{ $video->space->name }}">
                                {{ Str::limit($video->space->name, 12, '...') }}
                            </a>
                        </div>
                    </li>
                    <li class="flex-shrink-0">
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-gray-400 mx-1 text-xs"></i>
                            <a href="{{ route('videos.index', $video->space) }}"
                                class="text-xs sm:text-sm font-medium text-gray-700 hover:text-blue-600">
                                <span class="hidden sm:inline">動画一覧</span>
                                <span class="sm:hidden">動画</span>
                            </a>
                        </div>
                    </li>
                    <li class="flex-shrink-0">
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-gray-400 mx-1 text-xs"></i>
                            <a href="{{ route('videos.show', $video) }}"
                                class="text-xs sm:text-sm font-medium text-gray-700 hover:text-blue-600 truncate max-w-[80px] sm:max-w-xs"
                                title="{{ $video->title }}">
                                <span class="hidden sm:inline">詳細：</span>{{ Str::limit($video->title, 10, '...') }}
                            </a>
                        </div>
                    </li>
                    <li aria-current="page" class="flex-shrink-0">
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-gray-400 mx-1 text-xs"></i>
                            <span class="text-xs sm:text-sm font-medium text-gray-500">
                                <span class="hidden sm:inline">文字起こし</span>
                                <span class="sm:hidden">転写</span>
                            </span>
                        </div>
                    </li>
                </ol>
            </div>
        </nav>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{-- YouTubeから文字起こし取得ボタン --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-blue-800 mb-1">YouTubeから文字起こしを取得</h3>
                                <p class="text-xs text-blue-600">YouTubeの自動生成字幕を利用して一括登録します。</p>
                                <p class="text-xs text-red-600 mt-1">
                                    <i class="fa-solid fa-exclamation-triangle mr-1"></i>
                                    既存の文字起こしデータはすべて削除されます。
                                </p>
                            </div>
                            <div class="flex justify-center sm:justify-end">
                                <form id="import-form" action="{{ route('videos.dialogues.import', $video) }}"
                                    method="POST" class="hidden">
                                    @csrf
                                </form>
                                <button id="import-text" type="button" onclick="importFromYoutube()"
                                    class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                                    <i class="fa-solid fa-download mr-2"></i>取得する
                                </button>
                            </div>
                        </div>
                    </div>
                    {{-- YouTube動画プレイヤー --}}
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-800">動画プレイヤー</h3>
                        </div>
                        <div class="relative max-w-2xl mx-auto">
                            <div id="youtube-player" class="w-full aspect-video bg-black rounded-lg shadow-lg"></div>
                        </div>
                        <div
                            class="mt-2 flex flex-wrap items-center justify-center sm:justify-end gap-2 text-xs text-gray-600">
                            <div id="current-time-display" class="font-mono bg-gray-100 px-2 py-1 rounded">00:00</div>
                            <button type="button" onclick="handlePlayerClick()"
                                class="flex items-center space-x-1 px-2 py-1 bg-green-200 hover:bg-green-300 rounded text-green-800"
                                title="現在の再生時間を入力欄に設定（動画開始時は00:00、それ以外は現在時間）">
                                <i class="fa-solid fa-clock"></i>
                                <span class="hidden sm:inline">時間取得</span>
                            </button>
                            <button type="button" onclick="togglePlayPause()"
                                class="flex items-center space-x-1 px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded">
                                <i id="play-pause-icon" class="fa-solid fa-play"></i>
                                <span id="play-pause-text" class="hidden sm:inline">再生</span>
                            </button>
                            <button type="button" onclick="seekBackward()"
                                class="flex items-center space-x-1 px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded">
                                <i class="fa-solid fa-backward-step"></i>
                                <span>-1s</span>
                            </button>
                            <button type="button" onclick="seekForward()"
                                class="flex items-center space-x-1 px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded">
                                <i class="fa-solid fa-forward-step"></i>
                                <span>+1s</span>
                            </button>
                        </div>
                    </div>

                    <form id="dialogue-form" action="{{ route('videos.dialogues.store', $video) }}" method="POST"
                        class="mb-8 p-4 border rounded-lg" onsubmit="return handleFormSubmit(event)">
                        @csrf
                        <input type="hidden" id="edit-mode" name="edit_mode" value="0">
                        <input type="hidden" id="dialogue-id" name="dialogue_id" value="">

                        {{-- 編集モード表示 --}}
                        <div id="edit-indicator"
                            class="hidden mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fa-solid fa-edit text-yellow-600 mr-2"></i>
                                    <span class="text-sm font-medium text-yellow-800">編集モード</span>
                                </div>
                                <button type="button" onclick="cancelEdit()"
                                    class="text-yellow-600 hover:text-yellow-800">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                            <div class="md:col-span-1">
                                <label for="time-display" class="block text-sm font-medium text-gray-700">時間</label>
                                <input type="text" id="time-display"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm font-mono text-center"
                                    value="00:00" placeholder="00:00" pattern="^[0-9]{1,2}:[0-9]{2}$"
                                    title="mm:ss形式で入力してください（例: 0:00, 1:23, 12:34）" oninput="updateTimestamp(this.value)"
                                    required>
                                <input type="hidden" name="timestamp" id="timestamp" value="{{ old('timestamp', 0) }}">
                                @error('timestamp') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="md:col-span-1 relative">
                                <label for="speaker" class="block text-sm font-medium text-gray-700">発言者</label>
                                <div class="relative">
                                    <input type="text" name="speaker" id="speaker" value="{{ old('speaker') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm pr-8"
                                        autocomplete="off" onkeyup="filterSpeakers(this.value)"
                                        onfocus="showSpeakerSuggestions()" onblur="hideSpeakerSuggestions()">
                                    @if(count($speakers) > 0)
                                        <button type="button" onclick="toggleSpeakerSuggestions()"
                                            class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                                            <i class="fa-solid fa-chevron-down text-xs"></i>
                                        </button>
                                    @endif

                                    <!-- 発言者候補リスト -->
                                    @if(count($speakers) > 0)
                                        <div id="speaker-suggestions"
                                            class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-40 overflow-y-auto hidden">
                                            @foreach($speakers as $speakerOption)
                                                <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm speaker-option"
                                                    onclick="selectSpeaker('{{ addslashes($speakerOption) }}')">
                                                    <i class="fa-solid fa-user mr-2 text-gray-400"></i>
                                                    {{ $speakerOption }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                @error('speaker') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="md:col-span-3">
                                <label for="dialogue" class="block text-sm font-medium text-gray-700">文字起こし</label>
                                <div class="mt-1 relative">
                                    <input type="text" name="dialogue" id="dialogue" value="{{ old('dialogue') }}"
                                        class="block w-full rounded-md border-gray-300 shadow-sm px-3 py-2" required>

                                    <!-- 句読点・記号挿入ボタン（表示は入力欄の下、だがフローに影響させない） -->
                                    <div class="absolute left-0 top-full mt-1 z-10 flex items-center gap-2">
                                        <button type="button" onclick="insertPunctuationAtCursor('――')"
                                            aria-label="二重ダッシュ挿入"
                                            class="px-2 py-0.5 text-xs bg-gray-200 border border-gray-200 rounded hover:bg-gray-300">――</button>
                                        <button type="button" onclick="insertPunctuationAtCursor('～')"
                                            aria-label="波ダッシュ挿入"
                                            class="px-2 py-0.5 text-xs bg-gray-200 border border-gray-200 rounded hover:bg-gray-300">～</button>
                                        <button type="button" onclick="insertPunctuationAtCursor('？')"
                                            aria-label="疑問符挿入"
                                            class="px-2 py-0.5 text-xs bg-gray-200 border border-gray-200 rounded hover:bg-gray-300">？</button>
                                        <button type="button" onclick="insertPunctuationAtCursor('！')"
                                            aria-label="感嘆符挿入"
                                            class="px-2 py-0.5 text-xs bg-gray-200 border border-gray-200 rounded hover:bg-gray-300">！</button>
                                        <button type="button" onclick="insertPunctuationAtCursor('…')"
                                            aria-label="三点リーダー挿入"
                                            class="px-2 py-0.5 text-xs bg-gray-200 border border-gray-200 rounded hover:bg-gray-300">…</button>
                                    </div>
                                </div>
                                @error('dialogue') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="md:col-span-1 flex space-x-2">
                                <button type="button" id="voice-input-button" onclick="toggleVoiceInput()"
                                    class="flex items-center justify-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md shadow-sm flex-1"
                                    title="音声入力（最大20秒・マイクON→時間取得+動画再生、開始時は00:00、それ以外は現在時間）">
                                    <i id="voice-icon" class="fa-solid fa-microphone"></i>
                                </button>
                                <x-primary-button id="submit-button" class="justify-center flex-1">
                                    <i id="submit-icon" class="fa-solid fa-plus mr-2"></i>
                                    <span id="submit-text">追加</span>
                                </x-primary-button>
                            </div>
                        </div>

                        {{-- 音声入力の注意書き --}}
                        <div class="mt-8 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-start space-x-2">
                                <i class="fa-solid fa-info-circle text-blue-600 mt-0.5 text-xs"></i>
                                <div class="text-xs text-blue-800">
                                    <p class="font-medium mb-1">音声入力について</p>
                                    <ul class="list-disc list-inside space-y-0.5 text-blue-700">
                                        <li>マイクボタンを押すと<strong>時間が取得</strong>され（開始時は00:00、それ以外は現在時間）、<strong>動画が自動再生</strong>されます。
                                        </li>
                                        <li>時間取得ボタンを押すと<strong>時間が取得</strong>され（開始時は00:00、それ以外は現在時間）、<strong>動画が一時停止</strong>します。
                                        </li>
                                        <li><strong class="text-red-600">音声入力は最大20秒間</strong>で自動停止します。</li>
                                        <li>音声認識が終了すると<strong>動画が自動停止</strong>します。</li>
                                        <li>Chrome、Edge、Safari（最新版）で利用可能です。</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- まとめて操作コントロール --}}
                    @if($dialogues->isNotEmpty())
                        <div id="bulk-actions" class="mb-4 p-4 bg-gray-50 border rounded-lg hidden">
                            <div class="flex flex-wrap items-center gap-4">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-700">選択中:</span>
                                    <span id="selected-count" class="text-sm text-blue-600 font-medium">0件</span>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <label for="bulk-speaker" class="text-sm font-medium text-gray-700">発言者:</label>
                                    <div class="relative">
                                        <input type="text" id="bulk-speaker"
                                            class="px-3 py-1 border border-gray-300 rounded-md text-sm w-32"
                                            placeholder="発言者名">
                                        <div class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-32 overflow-y-auto hidden"
                                            id="bulk-speaker-suggestions">
                                            @foreach($speakers as $speakerOption)
                                                <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm bulk-speaker-option"
                                                    onclick="selectBulkSpeaker('{{ addslashes($speakerOption) }}')">
                                                    <i class="fa-solid fa-user mr-2 text-gray-400"></i>
                                                    {{ $speakerOption }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <button type="button" onclick="bulkUpdateSpeaker()"
                                        class="px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                                        <i class="fa-solid fa-user-edit mr-1"></i>更新
                                    </button>
                                </div>

                                <button type="button" onclick="bulkDeleteDialogues()"
                                    class="px-3 py-1 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">
                                    <i class="fa-solid fa-trash mr-1"></i>削除
                                </button>

                                <button type="button" onclick="clearSelection()"
                                    class="px-3 py-1 bg-gray-500 text-white text-sm rounded-md hover:bg-gray-600">
                                    <i class="fa-solid fa-times mr-1"></i>選択解除
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-2">
                        @if($dialogues->isNotEmpty())
                            <div class="flex items-center mb-3 p-2 bg-gray-100 rounded-lg">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" id="select-all" onchange="toggleSelectAll()"
                                        class="rounded border-gray-300 text-blue-600 mr-2">
                                    <span class="text-sm text-gray-700">すべて選択</span>
                                </label>
                            </div>
                        @endif

                        <div id="dialogues-list">
                            @forelse ($dialogues as $dialogue)
                                <div class="border p-3 rounded-lg hover:bg-gray-50 dialogue-row"
                                    data-dialogue-id="{{ $dialogue->id }}">
                                    <!-- デスクトップ表示 -->
                                    <div class="hidden sm:flex items-center justify-between">
                                        <div class="flex items-center space-x-3 flex-1">
                                            <label class="flex items-center cursor-pointer"
                                                onclick="event.stopPropagation()">
                                                <input type="checkbox"
                                                    class="dialogue-checkbox rounded border-gray-300 text-blue-600"
                                                    value="{{ $dialogue->id }}" onchange="updateSelectionCount()">
                                            </label>

                                            <div class="flex items-center space-x-4 cursor-pointer flex-1"
                                                onclick="editDialogue({{ $dialogue->id }}, {{ $dialogue->timestamp }}, '{{ addslashes($dialogue->speaker ?? '') }}', '{{ addslashes($dialogue->dialogue) }}')"
                                                title="クリックして編集">
                                                <span
                                                    class="font-mono text-sm text-gray-600 w-16 text-right">{{ gmdate("i:s", $dialogue->timestamp) }}</span>
                                                <span
                                                    class="font-bold text-gray-800 w-32 truncate">{{ $dialogue->speaker }}</span>
                                                <p class="text-gray-700">{{ $dialogue->dialogue }}</p>
                                            </div>
                                        </div>
                                        <form action="{{ route('dialogues.destroy', $dialogue) }}" method="POST"
                                            onsubmit="return confirm('この文字起こしを削除しますか？');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700">
                                                <i class="fa-solid fa-times"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <!-- モバイル表示 -->
                                    <div class="block sm:hidden">
                                        <!-- ヘッダー行（チェックボックス、時間、削除ボタン） -->
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center space-x-2">
                                                <label class="flex items-center cursor-pointer"
                                                    onclick="event.stopPropagation()">
                                                    <input type="checkbox"
                                                        class="dialogue-checkbox rounded border-gray-300 text-blue-600"
                                                        value="{{ $dialogue->id }}" onchange="updateSelectionCount()">
                                                </label>
                                                <span class="font-mono text-sm text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                                    {{ gmdate("i:s", $dialogue->timestamp) }}
                                                </span>
                                            </div>
                                            <form action="{{ route('dialogues.destroy', $dialogue) }}" method="POST"
                                                onsubmit="return confirm('この文字起こしを削除しますか？');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 p-1">
                                                    <i class="fa-solid fa-times"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <!-- コンテンツ行（発言者と文字起こし） -->
                                        <div class="cursor-pointer"
                                            onclick="editDialogue({{ $dialogue->id }}, {{ $dialogue->timestamp }}, '{{ addslashes($dialogue->speaker ?? '') }}', '{{ addslashes($dialogue->dialogue) }}')"
                                            title="タップして編集">
                                            @if($dialogue->speaker)
                                                <div class="mb-1">
                                                    <span
                                                        class="inline-block bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded">
                                                        <i class="fa-solid fa-user text-xs mr-1"></i>{{ $dialogue->speaker }}
                                                    </span>
                                                </div>
                                            @endif
                                            <p class="text-gray-700 text-sm leading-relaxed">{{ $dialogue->dialogue }}</p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-center text-gray-500 py-4">まだ文字起こしが登録されていません。</p>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        function importFromYoutube() {
            // 確認アラート
            const confirmMessage = 'YouTubeから文字起こしを取得すると、この動画の既存の文字起こしデータがすべて削除され、新しいデータに置き換わります。\n\n本当に実行しますか？';

            if (!confirm(confirmMessage)) {
                return; // キャンセルされた場合は処理を中止
            }

            const button = document.querySelector('#import-text');
            const form = document.getElementById('import-form');

            // ボタンをローディング状態に
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>取得中...';
            button.disabled = true; // 二重送信防止

            // フォームを送信
            form.submit();
        }

        function editDialogue(id, timestamp, speaker, dialogue) {
            // フォームを編集モードに切り替え
            document.getElementById('edit-mode').value = '1';
            document.getElementById('dialogue-id').value = id;

            // 入力欄に値を設定
            document.getElementById('timestamp').value = timestamp;
            document.getElementById('time-display').value = secondsToTimeFormat(timestamp);
            document.getElementById('speaker').value = speaker;
            document.getElementById('dialogue').value = dialogue;

            // 編集モード表示
            document.getElementById('edit-indicator').classList.remove('hidden');

            // ボタンを更新モードに変更
            document.getElementById('submit-icon').className = 'fa-solid fa-save mr-2';
            document.getElementById('submit-text').textContent = '更新';
            document.getElementById('submit-button').classList.remove('bg-gray-800', 'hover:bg-gray-700');
            document.getElementById('submit-button').classList.add('bg-yellow-600', 'hover:bg-yellow-700');

            // フォームのアクションを更新用に変更（AJAXで動的に決定）
            const form = document.getElementById('dialogue-form');
            form.setAttribute('data-update-url', `{{ url('/dialogues') }}/${id}`);

            // フォームにスクロール
            document.getElementById('dialogue-form').scrollIntoView({ behavior: 'smooth' });
        }

        function cancelEdit() {
            // フォームをリセット
            document.getElementById('edit-mode').value = '0';
            document.getElementById('dialogue-id').value = '';
            document.getElementById('timestamp').value = '';
            document.getElementById('time-display').value = '';
            document.getElementById('speaker').value = '';
            document.getElementById('dialogue').value = '';

            // 編集モード表示を隠す
            document.getElementById('edit-indicator').classList.add('hidden');

            // ボタンを追加モードに戻す
            document.getElementById('submit-icon').className = 'fa-solid fa-plus mr-2';
            document.getElementById('submit-text').textContent = '追加';
            document.getElementById('submit-button').classList.remove('bg-yellow-600', 'hover:bg-yellow-700');
            document.getElementById('submit-button').classList.add('bg-gray-800', 'hover:bg-gray-700');

            // フォームのアクションを作成用に戻す
            const form = document.getElementById('dialogue-form');
            form.action = '{{ route("videos.dialogues.store", $video) }}';
        }

        // 時間変換機能
        function updateTimestamp(timeString) {
            const timestampField = document.getElementById('timestamp');

            if (!timeString.trim()) {
                timestampField.value = '';
                return;
            }

            // mm:ss形式をパース
            const timeMatch = timeString.match(/^(\d{1,2}):(\d{2})$/);
            if (timeMatch) {
                const minutes = parseInt(timeMatch[1]);
                const seconds = parseInt(timeMatch[2]);

                if (seconds < 60) {
                    const totalSeconds = minutes * 60 + seconds;
                    timestampField.value = totalSeconds;
                } else {
                    // 秒が60以上の場合はエラー表示
                    timestampField.value = '';
                }
            } else {
                timestampField.value = '';
            }
        }

        function secondsToTimeFormat(seconds) {
            // 0秒の場合も00:00として表示
            if (seconds === null || seconds === undefined) return '00:00';

            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return mins.toString().padStart(2, '0') + ':' + secs.toString().padStart(2, '0');
        }

        // 発言者候補の管理
        let suggestionTimeout;

        function showSpeakerSuggestions() {
            const suggestions = document.getElementById('speaker-suggestions');
            if (suggestions) {
                clearTimeout(suggestionTimeout);
                suggestions.classList.remove('hidden');
            }
        }

        function hideSpeakerSuggestions() {
            const suggestions = document.getElementById('speaker-suggestions');
            if (suggestions) {
                suggestionTimeout = setTimeout(() => {
                    suggestions.classList.add('hidden');
                }, 150); // 少し遅延させてクリックを受け付ける
            }
        }

        function toggleSpeakerSuggestions() {
            const suggestions = document.getElementById('speaker-suggestions');
            if (suggestions) {
                if (suggestions.classList.contains('hidden')) {
                    showSpeakerSuggestions();
                    document.getElementById('speaker').focus();
                } else {
                    hideSpeakerSuggestions();
                }
            }
        }

        function selectSpeaker(speaker) {
            document.getElementById('speaker').value = speaker;
            hideSpeakerSuggestions();
            document.getElementById('dialogue').focus(); // 次の入力欄にフォーカス
        }

        function filterSpeakers(searchText) {
            const suggestions = document.getElementById('speaker-suggestions');
            if (!suggestions) return;

            const options = suggestions.querySelectorAll('.speaker-option');
            let hasVisibleOptions = false;

            options.forEach(option => {
                const speakerName = option.textContent.trim();
                if (speakerName.toLowerCase().includes(searchText.toLowerCase()) || searchText === '') {
                    option.style.display = 'block';
                    hasVisibleOptions = true;
                } else {
                    option.style.display = 'none';
                }
            });

            // 検索結果があり、入力欄にフォーカスがある場合は候補を表示
            if (hasVisibleOptions && document.activeElement === document.getElementById('speaker')) {
                suggestions.classList.remove('hidden');
            } else if (!hasVisibleOptions) {
                suggestions.classList.add('hidden');
            }
        }

        // クリック範囲外で候補を閉じる
        document.addEventListener('click', function (event) {
            const speakerInput = document.getElementById('speaker');
            const suggestions = document.getElementById('speaker-suggestions');

            if (suggestions && speakerInput &&
                !speakerInput.contains(event.target) &&
                !suggestions.contains(event.target)) {
                suggestions.classList.add('hidden');
            }
        });

        // キーボードナビゲーション
        const speakerElement = document.getElementById('speaker');
        if (speakerElement) {
            speakerElement.addEventListener('keydown', function (event) {
                const suggestions = document.getElementById('speaker-suggestions');
                if (!suggestions || suggestions.classList.contains('hidden')) return;

                const visibleOptions = Array.from(suggestions.querySelectorAll('.speaker-option'))
                    .filter(option => option.style.display !== 'none');

                if (visibleOptions.length === 0) return;

                const currentFocus = suggestions.querySelector('.speaker-option.bg-blue-100');
                let newFocus = null;

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    if (currentFocus) {
                        currentFocus.classList.remove('bg-blue-100');
                        const currentIndex = visibleOptions.indexOf(currentFocus);
                        newFocus = visibleOptions[currentIndex + 1] || visibleOptions[0];
                    } else {
                        newFocus = visibleOptions[0];
                    }
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    if (currentFocus) {
                        currentFocus.classList.remove('bg-blue-100');
                        const currentIndex = visibleOptions.indexOf(currentFocus);
                        newFocus = visibleOptions[currentIndex - 1] || visibleOptions[visibleOptions.length - 1];
                    } else {
                        newFocus = visibleOptions[visibleOptions.length - 1];
                    }
                } else if (event.key === 'Enter' && currentFocus) {
                    event.preventDefault();
                    currentFocus.click();
                } else if (event.key === 'Escape') {
                    suggestions.classList.add('hidden');
                }

                if (newFocus) {
                    newFocus.classList.add('bg-blue-100');
                    newFocus.scrollIntoView({ block: 'nearest' });
                }
            });
        }

        // 時間入力欄の改善機能
        const timeDisplayElement = document.getElementById('time-display');
        if (timeDisplayElement) {
            timeDisplayElement.addEventListener('keydown', function (event) {
                // 数字、コロン、バックスペース、矢印キー、Tabキーのみ許可
                const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];
                const isNumber = event.key >= '0' && event.key <= '9';
                const isColon = event.key === ':';

                if (!isNumber && !isColon && !allowedKeys.includes(event.key)) {
                    event.preventDefault();
                }
            });

            timeDisplayElement.addEventListener('input', function (event) {
                let value = event.target.value;

                // 自動コロン挿入（3桁目に入力したとき）
                if (value.length === 2 && !value.includes(':') && event.inputType === 'insertText') {
                    event.target.value = value + ':';
                }
            });

            timeDisplayElement.addEventListener('blur', function (event) {
                let value = event.target.value.trim();

                if (value) {
                    // 不完全な形式を修正
                    if (value.match(/^\d{1,2}$/)) {
                        // 秒数のみの場合（例: "30" → "0:30"）
                        const seconds = parseInt(value);
                        if (seconds < 60) {
                            value = '0:' + value.padStart(2, '0');
                        } else {
                            // 60以上の場合は分に変換
                            const mins = Math.floor(seconds / 60);
                            const secs = seconds % 60;
                            value = mins + ':' + secs.toString().padStart(2, '0');
                        }
                    } else if (value.match(/^\d{1,2}:\d$/)) {
                        // 秒が1桁の場合（例: "1:5" → "1:05"）
                        const parts = value.split(':');
                        value = parts[0] + ':' + parts[1].padStart(2, '0');
                    }

                    event.target.value = value;
                    updateTimestamp(value);
                }
            });
        }

        // まとめて操作機能
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('select-all');
            const dialogueCheckboxes = document.querySelectorAll('.dialogue-checkbox');

            dialogueCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });

            updateSelectionCount();
        }

        function updateSelectionCount() {
            const checkedBoxes = document.querySelectorAll('.dialogue-checkbox:checked');
            const count = checkedBoxes.length;
            const countElement = document.getElementById('selected-count');
            const bulkActions = document.getElementById('bulk-actions');
            const selectAllCheckbox = document.getElementById('select-all');

            if (countElement) countElement.textContent = count + '件';

            if (bulkActions) {
                if (count > 0) {
                    bulkActions.classList.remove('hidden');
                } else {
                    bulkActions.classList.add('hidden');
                }
            }

            // 全選択チェックボックスの状態を更新
            if (selectAllCheckbox) {
                const totalCheckboxes = document.querySelectorAll('.dialogue-checkbox').length;
                if (count === 0) {
                    selectAllCheckbox.indeterminate = false;
                    selectAllCheckbox.checked = false;
                } else if (count === totalCheckboxes) {
                    selectAllCheckbox.indeterminate = false;
                    selectAllCheckbox.checked = true;
                } else {
                    selectAllCheckbox.indeterminate = true;
                    selectAllCheckbox.checked = false;
                }
            }
        }

        function clearSelection() {
            const dialogueCheckboxes = document.querySelectorAll('.dialogue-checkbox');
            const selectAllCheckbox = document.getElementById('select-all');

            dialogueCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });

            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }

            updateSelectionCount();
        }

        function selectBulkSpeaker(speaker) {
            document.getElementById('bulk-speaker').value = speaker;
            document.getElementById('bulk-speaker-suggestions').classList.add('hidden');
        }

        function bulkUpdateSpeaker() {
            const checkedBoxes = document.querySelectorAll('.dialogue-checkbox:checked');
            const speaker = document.getElementById('bulk-speaker').value.trim();

            if (checkedBoxes.length === 0) {
                alert('更新する行を選択してください。');
                return;
            }

            if (!speaker) {
                alert('発言者名を入力してください。');
                return;
            }

            const confirmMessage = `選択した${checkedBoxes.length}件の発言者を「${speaker}」に変更しますか？`;
            if (!confirm(confirmMessage)) {
                return;
            }

            // 選択されたIDを収集
            const dialogueIds = Array.from(checkedBoxes).map(checkbox => checkbox.value);

            // フォームを作成して送信
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ url("/dialogues/bulk-update-speaker") }}';

            // CSRFトークン
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);

            // 発言者名
            const speakerInput = document.createElement('input');
            speakerInput.type = 'hidden';
            speakerInput.name = 'speaker';
            speakerInput.value = speaker;
            form.appendChild(speakerInput);

            // 選択されたID
            dialogueIds.forEach(id => {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'dialogue_ids[]';
                idInput.value = id;
                form.appendChild(idInput);
            });

            document.body.appendChild(form);
            form.submit();
        }

        function bulkDeleteDialogues() {
            const checkedBoxes = document.querySelectorAll('.dialogue-checkbox:checked');

            if (checkedBoxes.length === 0) {
                alert('削除する行を選択してください。');
                return;
            }

            const confirmMessage = `選択した${checkedBoxes.length}件の文字起こしを削除しますか？\n\nこの操作は取り消せません。`;
            if (!confirm(confirmMessage)) {
                return;
            }

            // 選択されたIDを収集
            const dialogueIds = Array.from(checkedBoxes).map(checkbox => checkbox.value);

            // フォームを作成して送信
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ url("/dialogues/bulk-delete") }}';

            // CSRFトークン
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);

            // 選択されたID
            dialogueIds.forEach(id => {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'dialogue_ids[]';
                idInput.value = id;
                form.appendChild(idInput);
            });

            document.body.appendChild(form);
            form.submit();
        }

        // 発言者候補の表示/非表示（要素が存在する場合のみ）
        const bulkSpeakerElement = document.getElementById('bulk-speaker');
        const bulkSpeakerSuggestionsElement = document.getElementById('bulk-speaker-suggestions');

        if (bulkSpeakerElement && bulkSpeakerSuggestionsElement) {
            bulkSpeakerElement.addEventListener('focus', function () {
                bulkSpeakerSuggestionsElement.classList.remove('hidden');
            });

            bulkSpeakerElement.addEventListener('blur', function () {
                setTimeout(() => {
                    bulkSpeakerSuggestionsElement.classList.add('hidden');
                }, 150);
            });
        }

        // YouTube Player API関連
        let player;
        let isPlayerReady = false;

        // YouTube IFrame Player API の読み込み
        function loadYouTubeAPI() {
            if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
                const tag = document.createElement('script');
                tag.src = 'https://www.youtube.com/iframe_api';
                const firstScriptTag = document.getElementsByTagName('script')[0];
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
            } else {
                onYouTubeIframeAPIReady();
            }
        }

        // API読み込み完了時に呼ばれる関数
        function onYouTubeIframeAPIReady() {
            player = new YT.Player('youtube-player', {
                height: '100%',
                width: '100%',
                videoId: '{{ $video->youtube_video_id }}',
                playerVars: {
                    'playsinline': 1,
                    'rel': 0,
                    'modestbranding': 1,
                    'showinfo': 0,
                    'controls': 1,
                    'disablekb': 0,
                    'fs': 1,
                    'iv_load_policy': 3,
                    'loop': 0,
                    'autoplay': 0
                },
                events: {
                    'onReady': onPlayerReady,
                    'onStateChange': onPlayerStateChange,
                    'onEnd': onPlayerEnd
                }
            });
        }

        function onPlayerReady(event) {
            isPlayerReady = true;
            updateCurrentTimeDisplay();
        }

        function onPlayerStateChange(event) {
            const playPauseIcon = document.getElementById('play-pause-icon');
            const playPauseText = document.getElementById('play-pause-text');

            if (event.data === YT.PlayerState.PLAYING) {
                playPauseIcon.className = 'fa-solid fa-pause';
                playPauseText.textContent = '一時停止';
                startTimeUpdate();
            } else {
                playPauseIcon.className = 'fa-solid fa-play';
                playPauseText.textContent = '再生';
                stopTimeUpdate();
            }
        }

        function onPlayerEnd(event) {
            // 動画終了時に関連動画を表示させないため、動画を最初に戻す
            player.seekTo(0);
            player.pauseVideo();

            // UI更新
            const playPauseIcon = document.getElementById('play-pause-icon');
            const playPauseText = document.getElementById('play-pause-text');
            playPauseIcon.className = 'fa-solid fa-play';
            playPauseText.textContent = '再生';

            stopTimeUpdate();
            updateCurrentTimeDisplay();
        }

        let timeUpdateInterval; function startTimeUpdate() {
            if (timeUpdateInterval) clearInterval(timeUpdateInterval);
            timeUpdateInterval = setInterval(updateCurrentTimeDisplay, 1000);
        }

        function stopTimeUpdate() {
            if (timeUpdateInterval) {
                clearInterval(timeUpdateInterval);
                timeUpdateInterval = null;
            }
        }

        function updateCurrentTimeDisplay() {
            if (isPlayerReady && player) {
                const currentTime = Math.floor(player.getCurrentTime());
                document.getElementById('current-time-display').textContent = secondsToTimeFormat(currentTime);
            }
        }

        function handlePlayerClick() {
            if (isPlayerReady && player) {
                // 動画を一時停止
                player.pauseVideo();

                // 現在時間を取得
                const currentTime = Math.floor(player.getCurrentTime());

                // 00:00の場合は00:00を設定、それ以外は現在時間を設定
                if (currentTime === 0) {
                    document.getElementById('timestamp').value = 0;
                    document.getElementById('time-display').value = '00:00';
                } else {
                    document.getElementById('timestamp').value = currentTime;
                    document.getElementById('time-display').value = secondsToTimeFormat(currentTime);
                }

                // 時間入力欄にフォーカス
                document.getElementById('time-display').focus();
                document.getElementById('time-display').select();
            }
        } function togglePlayPause() {
            if (isPlayerReady && player) {
                const state = player.getPlayerState();
                if (state === YT.PlayerState.PLAYING) {
                    player.pauseVideo();
                } else {
                    player.playVideo();
                }
            }
        }

        function seekBackward() {
            if (isPlayerReady && player) {
                const currentTime = player.getCurrentTime();
                player.seekTo(Math.max(0, currentTime - 1), true);
            }
        }

        function seekForward() {
            if (isPlayerReady && player) {
                const currentTime = player.getCurrentTime();
                player.seekTo(currentTime + 1, true);
            }
        }

        // 音声認識機能
        let recognition;
        let isRecording = false;
        let recordingTimer = null;
        const MAX_RECORDING_TIME = 20000; // 20秒（ミリ秒）

        function initSpeechRecognition() {
            if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                recognition = new SpeechRecognition();

                recognition.continuous = true;
                recognition.interimResults = true;
                recognition.lang = 'ja-JP';
                recognition.maxAlternatives = 1;

                recognition.onstart = function () {
                    isRecording = true;
                    const voiceIcon = document.getElementById('voice-icon');
                    const voiceButton = document.getElementById('voice-input-button');

                    voiceIcon.className = 'fa-solid fa-stop';
                    voiceButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                    voiceButton.classList.add('bg-red-600', 'hover:bg-red-700');
                    voiceButton.title = '音声入力を停止';

                    // 音声認識開始時に動画の現在時間を取得して入力欄に設定
                    if (isPlayerReady && player) {
                        const currentTime = Math.floor(player.getCurrentTime());

                        // 00:00の場合は00:00を設定、それ以外は現在時間を設定
                        if (currentTime === 0) {
                            document.getElementById('timestamp').value = 0;
                            document.getElementById('time-display').value = '00:00';
                        } else {
                            document.getElementById('timestamp').value = currentTime;
                            document.getElementById('time-display').value = secondsToTimeFormat(currentTime);
                        }

                        // 動画を再生
                        player.playVideo();
                    }

                    // 20秒後に自動停止するタイマーを設定
                    recordingTimer = setTimeout(function () {
                        if (isRecording && recognition) {
                            recognition.stop();
                            console.log('音声認識が20秒の制限時間に達したため停止しました');
                        }
                    }, MAX_RECORDING_TIME);
                };

                recognition.onresult = function (event) {
                    let finalTranscript = '';
                    let interimTranscript = '';

                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        const transcript = event.results[i][0].transcript;
                        if (event.results[i].isFinal) {
                            finalTranscript += transcript;
                        } else {
                            interimTranscript += transcript;
                        }
                    }

                    const dialogueField = document.getElementById('dialogue');
                    if (finalTranscript) {
                        // 最終結果の場合、既存のテキストに追加
                        const currentValue = dialogueField.value;
                        dialogueField.value = currentValue + (currentValue ? ' ' : '') + finalTranscript;
                    } else if (interimTranscript) {
                        // 中間結果の場合、一時的に表示（背景色を変更して区別）
                        dialogueField.style.backgroundColor = '#f0f8ff';
                        dialogueField.placeholder = '認識中: ' + interimTranscript;
                    }

                    dialogueField.focus();
                };

                recognition.onend = function () {
                    isRecording = false;
                    const voiceIcon = document.getElementById('voice-icon');
                    const voiceButton = document.getElementById('voice-input-button');
                    const dialogueField = document.getElementById('dialogue');

                    voiceIcon.className = 'fa-solid fa-microphone';
                    voiceButton.classList.remove('bg-red-600', 'hover:bg-red-700');
                    voiceButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
                    voiceButton.title = '音声入力（最大20秒・マイクON→時間取得+動画再生、開始時は00:00、それ以外は現在時間）';

                    // 入力欄の表示をリセット
                    dialogueField.style.backgroundColor = '';
                    dialogueField.placeholder = 'セリフを入力';

                    // 音声認識終了時に動画を一時停止
                    if (isPlayerReady && player) {
                        player.pauseVideo();
                    }

                    // タイマーをクリア
                    if (recordingTimer) {
                        clearTimeout(recordingTimer);
                        recordingTimer = null;
                    }
                };

                recognition.onerror = function (event) {
                    console.error('音声認識エラー:', event.error);
                    isRecording = false;
                    const voiceIcon = document.getElementById('voice-icon');
                    const voiceButton = document.getElementById('voice-input-button');
                    const dialogueField = document.getElementById('dialogue');

                    voiceIcon.className = 'fa-solid fa-microphone';
                    voiceButton.classList.remove('bg-red-600', 'hover:bg-red-700');
                    voiceButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
                    voiceButton.title = '音声入力（最大20秒・マイクON→時間取得+動画再生、開始時は00:00、それ以外は現在時間）';

                    // 入力欄の表示をリセット
                    dialogueField.style.backgroundColor = '';
                    dialogueField.placeholder = 'セリフを入力';

                    // エラー時も動画を一時停止
                    if (isPlayerReady && player) {
                        player.pauseVideo();
                    }

                    // タイマーをクリア
                    if (recordingTimer) {
                        clearTimeout(recordingTimer);
                        recordingTimer = null;
                    }

                    // 'no-speech'エラーは表示しない（無音による自動停止のため）
                    if (event.error !== 'no-speech') {
                        alert('音声認識でエラーが発生しました: ' + event.error);
                    }
                };
            } else {
                // 音声認識がサポートされていない場合、ボタンを無効化
                const voiceButton = document.getElementById('voice-input-button');
                voiceButton.disabled = true;
                voiceButton.classList.add('opacity-50', 'cursor-not-allowed');
                voiceButton.title = 'この browser では音声認識がサポートされていません';
            }
        }

        function toggleVoiceInput() {
            if (!recognition) {
                alert('音声認識がサポートされていません');
                return;
            }

            if (isRecording) {
                recognition.stop();
                // 手動停止時もタイマーをクリア
                if (recordingTimer) {
                    clearTimeout(recordingTimer);
                    recordingTimer = null;
                }
            } else {
                recognition.start();
            }
        }

        // フォーム送信処理（AJAX）
        function handleFormSubmit(event) {
            event.preventDefault(); // ページリロードを防ぐ

            const form = document.getElementById('dialogue-form');
            const formData = new FormData(form);
            const submitButton = document.getElementById('submit-button');
            const submitIcon = document.getElementById('submit-icon');
            const submitText = document.getElementById('submit-text');

            // ボタンを無効化
            submitButton.disabled = true;
            submitIcon.className = 'fa-solid fa-spinner fa-spin mr-2';
            submitText.textContent = '処理中...';

            // 編集モードかどうかを確認
            const editMode = document.getElementById('edit-mode').value === '1';
            const url = editMode ? form.getAttribute('data-update-url') : form.action;

            // CSRF トークンを追加
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                document.querySelector('input[name="_token"]')?.value;

            if (csrfToken) {
                formData.set('_token', csrfToken);
            }

            // 編集モードの場合はPUTメソッドを追加
            if (editMode) {
                formData.set('_method', 'PUT');
            }

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 成功時の処理
                        if (data.dialogue) {
                            // 新規追加の場合、リストに追加
                            if (data.dialogue.id && !document.querySelector(`[data-dialogue-id="${data.dialogue.id}"]`)) {
                                addDialogueToList(data.dialogue);
                            } else {
                                // 更新の場合、既存の項目を更新
                                updateDialogueInList(data.dialogue);
                            }
                        }

                        // フォームをリセット
                        resetForm();

                        // 成功メッセージ
                        showMessage('保存しました', 'success');
                    } else {
                        // エラーの場合
                        showMessage(data.message || 'エラーが発生しました', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('通信エラーが発生しました', 'error');
                })
                .finally(() => {
                    // ボタンを元に戻す
                    submitButton.disabled = false;
                    const editMode = document.getElementById('edit-mode').value;
                    if (editMode === '1') {
                        submitIcon.className = 'fa-solid fa-save mr-2';
                        submitText.textContent = '更新';
                    } else {
                        submitIcon.className = 'fa-solid fa-plus mr-2';
                        submitText.textContent = '追加';
                    }
                });

            return false;
        }

        // フォームリセット
        function resetForm() {
            document.getElementById('dialogue-form').reset();
            document.getElementById('edit-mode').value = '0';
            document.getElementById('dialogue-id').value = '';
            document.getElementById('edit-indicator').classList.add('hidden');

            // ボタンを追加モードに戻す
            const submitButton = document.getElementById('submit-button');
            const submitIcon = document.getElementById('submit-icon');
            const submitText = document.getElementById('submit-text');

            submitIcon.className = 'fa-solid fa-plus mr-2';
            submitText.textContent = '追加';
            submitButton.classList.remove('bg-yellow-600', 'hover:bg-yellow-700');
            submitButton.classList.add('bg-gray-800', 'hover:bg-gray-700');
        }

        // リストに新しいダイアログを追加
        function addDialogueToList(dialogue) {
            const dialoguesList = document.getElementById('dialogues-list');
            if (dialoguesList) {
                const newItem = createDialogueListItem(dialogue);
                dialoguesList.insertAdjacentHTML('afterbegin', newItem);
            }
        }

        // リストの既存ダイアログを更新
        function updateDialogueInList(dialogue) {
            const existingItem = document.querySelector(`[data-dialogue-id="${dialogue.id}"]`);
            if (existingItem) {
                const newItem = createDialogueListItem(dialogue);
                existingItem.outerHTML = newItem;
            }
        }

        // ダイアログリストアイテムのHTMLを生成
        function createDialogueListItem(dialogue) {
            // 既存の表示形式に合わせて時間を00:00形式で表示
            const minutes = Math.floor(dialogue.timestamp / 60);
            const seconds = dialogue.timestamp % 60;
            const timeFormat = minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0');
            const speakerBadge = dialogue.speaker ?
                `<div class="mb-1">
                    <span class="inline-block bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded">
                        <i class="fa-solid fa-user text-xs mr-1"></i>${dialogue.speaker}
                    </span>
                </div>` : '';

            return `
                <div class="border p-3 rounded-lg hover:bg-gray-50 dialogue-row"
                    data-dialogue-id="${dialogue.id}">
                    <!-- デスクトップ表示 -->
                    <div class="hidden sm:flex items-center justify-between">
                        <div class="flex items-center space-x-3 flex-1">
                            <label class="flex items-center cursor-pointer" onclick="event.stopPropagation()">
                                <input type="checkbox"
                                    class="dialogue-checkbox rounded border-gray-300 text-blue-600"
                                    value="${dialogue.id}" onchange="updateSelectionCount()">
                            </label>

                            <div class="flex items-center space-x-4 cursor-pointer flex-1"
                                onclick="editDialogue(${dialogue.id}, ${dialogue.timestamp}, '${dialogue.speaker || ''}', '${dialogue.dialogue}')"
                                title="クリックして編集">
                                <span class="font-mono text-sm text-gray-600 w-16 text-right">${timeFormat}</span>
                                <span class="font-bold text-gray-800 w-32 truncate">${dialogue.speaker || ''}</span>
                                <p class="text-gray-700">${dialogue.dialogue}</p>
                            </div>
                        </div>
                        <form action="/dialogues/${dialogue.id}" method="POST"
                            onsubmit="return confirm('この文字起こしを削除しますか？');">
                            <input type="hidden" name="_token" value="${document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || ''}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="text-red-500 hover:text-red-700">
                                <i class="fa-solid fa-times"></i>
                            </button>
                        </form>
                    </div>

                    <!-- モバイル表示 -->
                    <div class="block sm:hidden">
                        <!-- ヘッダー行（チェックボックス、時間、削除ボタン） -->
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-2">
                                <label class="flex items-center cursor-pointer" onclick="event.stopPropagation()">
                                    <input type="checkbox"
                                        class="dialogue-checkbox rounded border-gray-300 text-blue-600"
                                        value="${dialogue.id}" onchange="updateSelectionCount()">
                                </label>
                                <span class="font-mono text-sm text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                    ${timeFormat}
                                </span>
                            </div>
                            <form action="/dialogues/${dialogue.id}" method="POST"
                                onsubmit="return confirm('この文字起こしを削除しますか？');">
                                <input type="hidden" name="_token" value="${document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || ''}">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="text-red-500 hover:text-red-700 p-1">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </form>
                        </div>

                        <!-- コンテンツ行（発言者と文字起こし） -->
                        <div class="cursor-pointer"
                            onclick="editDialogue(${dialogue.id}, ${dialogue.timestamp}, '${dialogue.speaker || ''}', '${dialogue.dialogue}')"
                            title="タップして編集">
                            ${speakerBadge}
                            <p class="text-gray-700 text-sm leading-relaxed">${dialogue.dialogue}</p>
                        </div>
                    </div>
                </div>
            `;
        }

        // メッセージ表示
        function showMessage(message, type = 'success') {
            const messageDiv = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
            const icon = type === 'success' ? 'fa-check' : 'fa-exclamation-triangle';

            messageDiv.className = `fixed top-4 right-4 ${bgColor} text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-opacity`;
            messageDiv.innerHTML = `<i class="fa-solid ${icon} mr-2"></i>${message}`;

            document.body.appendChild(messageDiv);

            setTimeout(() => {
                messageDiv.style.opacity = '0';
                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        document.body.removeChild(messageDiv);
                    }
                }, 300);
            }, 3000);
        }

        // ページ読み込み時にYouTube APIと音声認識を初期化
        document.addEventListener('DOMContentLoaded', function () {
            loadYouTubeAPI();
            initSpeechRecognition();
        });

        // グローバル関数として定義（YouTube APIから呼ばれる）
        window.onYouTubeIframeAPIReady = onYouTubeIframeAPIReady;

        // 句読点・記号をカーソル位置に挿入するユーティリティ
        function insertPunctuationAtCursor(text) {
            const input = document.getElementById('dialogue');
            if (!input) return;

            // フォーカスを移動して挿入位置を取得
            input.focus();

            // input 要素（type=text）の選択範囲に挿入
            const start = input.selectionStart || 0;
            const end = input.selectionEnd || 0;
            const value = input.value || '';

            const newValue = value.substring(0, start) + text + value.substring(end);
            input.value = newValue;

            // カーソル位置を挿入後の末尾に移動
            const caretPos = start + text.length;
            input.setSelectionRange(caretPos, caretPos);

            // 変更を認識させるために input イベントを発火
            const evt = new Event('input', { bubbles: true });
            input.dispatchEvent(evt);
        }
    </script>
</x-app-layout>
