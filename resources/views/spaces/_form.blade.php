@csrf
<div class="mb-4">
    <label for="name" class="block text-sm font-medium text-gray-700">スペース名</label>
    {{-- null合体演算子(?->)を使い、$space変数が存在しない場合のエラーを回避 --}}
    <input type="text" name="name" id="name" value="{{ old('name', $space->name ?? null) }}"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div class="mb-4">
    <label for="slug" class="block text-sm font-medium text-gray-700">URL用識別子 (半角英数字とハイフン)</label>
    <input type="text" name="slug" id="slug" value="{{ old('slug', $space->slug ?? null) }}"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
    @error('slug') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700">公開範囲</label>
    <div class="mt-2 space-y-2">
        @php
            // 新規作成時は0(自分のみ)、編集時はDBの値を選択状態にする
            $currentVisibility = old('visibility', $space->visibility ?? 0);
        @endphp
        <label class="flex items-center">
            <input type="radio" name="visibility" value="0" class="form-radio" {{ $currentVisibility == 0 ? 'checked' : '' }}>
            <span class="ml-2">自分のみ</span>
        </label>
        <label class="flex items-center">
            <input type="radio" name="visibility" value="1" class="form-radio" {{ $currentVisibility == 1 ? 'checked' : '' }}>
            <span class="ml-2">限定公開</span>
        </label>
        <label class="flex items-center">
            <input type="radio" name="visibility" value="2" class="form-radio" {{ $currentVisibility == 2 ? 'checked' : '' }}>
            <span class="ml-2">全体公開</span>
        </label>
    </div>
    @error('visibility') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>

{{-- ゲストリンク表示セクション（編集時のみ） --}}
@if(isset($space) && $space->id)
    <div class="mb-6 p-4 bg-gray-50 rounded-lg border">
        <h3 class="text-lg font-medium text-gray-900 mb-3">
            <i class="fa-solid fa-link mr-2"></i>ゲスト用リンク
        </h3>

        <div id="guest-link-section">
            @if($space->visibility >= 1)
                <div class="space-y-3">
                    {{-- 現在の公開状態 --}}
                    <div class="flex items-center text-sm">
                        <span class="font-medium text-gray-700 mr-2">現在の公開状態:</span>
                        @php
                            $visibilityClasses = 'px-2 py-1 text-xs rounded-full ';
                            if ($space->visibility === 2) {
                                $visibilityClasses .= 'bg-green-100 text-green-800';
                            } elseif ($space->visibility === 1) {
                                $visibilityClasses .= 'bg-yellow-100 text-yellow-800';
                            } else {
                                $visibilityClasses .= 'bg-gray-100 text-gray-800';
                            }
                        @endphp
                        <span class="{{ $visibilityClasses }}">{{ $space->getVisibilityLabel() }}</span>
                    </div>

                    @if($space->getGuestUrl())
                        {{-- ゲストリンク表示 --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ゲスト用URL:</label>
                            <div class="flex items-center space-x-2">
                                <input type="text" id="guest-url" value="{{ $space->getGuestUrl() }}" readonly
                                    class="flex-1 px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm">
                                <button type="button" onclick="copyToClipboard('guest-url')"
                                    class="px-3 py-2 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                    <i class="fa-solid fa-copy mr-1"></i>コピー
                                </button>
                                <a href="{{ $space->getGuestUrl() }}" target="_blank"
                                    class="px-3 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <i class="fa-solid fa-external-link-alt mr-1"></i>開く
                                </a>
                            </div>
                        </div>
                    @endif

                    {{-- 限定公開用の招待トークン生成 --}}
                    @if($space->visibility === 1)
                        <div class="pt-2 border-t border-gray-200">
                            @if(!$space->invite_token)
                                <p class="text-sm text-gray-600 mb-2">限定公開リンクがまだ生成されていません。</p>
                                <button type="button" onclick="generateInviteToken({{ $space->id }})"
                                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <i class="fa-solid fa-key mr-1"></i>招待リンクを生成
                                </button>
                            @else
                                <div class="flex items-center justify-between">
                                    <p class="text-sm text-gray-600">招待リンクでゲストがアクセスできます。</p>
                                    <button type="button" onclick="generateInviteToken({{ $space->id }})"
                                        class="px-3 py-2 bg-yellow-600 text-white text-sm rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                        <i class="fa-solid fa-refresh mr-1"></i>リンクを再生成
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fa-solid fa-lock text-gray-400 text-2xl mb-2"></i>
                    <p class="text-sm text-gray-600">「限定公開」または「全体公開」に設定すると、ゲスト用リンクが生成されます。</p>
                </div>
            @endif
        </div>
    </div>
@endif

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700">関連リンク (任意)</label>
    <p class="text-xs text-gray-500 mb-2">複数の URL を追加できます。例: YouTube チャンネル、公式サイトなど</p>
    <div id="related-urls-list" class="space-y-2" data-next-index="0">
        @php
            // old() の値や DB の保存値が様々な形で来る可能性があるため正規化する
            $raw = old('related_urls', $space->related_urls ?? []);
            $related = [];

            if (is_array($raw)) {
                // 連想配列と数値添字配列の両方に対応する
                // もし $raw のキーが連想配列（label/url を持つ）であれば、そのまま配列化
                if ((array_key_exists('label', $raw) || array_key_exists('url', $raw)) && array_keys($raw) !== range(0, count($raw) - 1)) {
                    $raw = [$raw];
                }

                foreach ($raw as $item) {
                    if (is_string($item)) {
                        // 旧データや単純配列の場合は URL として扱う
                        $related[] = ['label' => '', 'url' => $item];
                    } elseif (is_array($item)) {
                        $label = isset($item['label']) ? $item['label'] : '';
                        $url = isset($item['url']) ? $item['url'] : '';
                        $related[] = ['label' => $label, 'url' => $url];
                    }
                }
            }

            // フォームに1行もない場合は空の行を用意
            if (count($related) === 0) {
                $related[] = ['label' => '', 'url' => ''];
            }
        @endphp

        @foreach($related as $i => $item)
            @php
                $labelOptions = ['公式サイト', 'YouTube', 'X', 'instagram', 'tiktok'];
                $isOther = $item['label'] !== '' && !in_array($item['label'], $labelOptions);
            @endphp
            <div class="grid grid-cols-12 gap-3 items-center py-2 border rounded-md px-3" data-related-index="{{ $i }}">
                <input type="hidden" name="related_urls[{{ $i }}][_delete]" value="0" class="related-delete-input">
                <input type="hidden" name="related_urls[{{ $i }}][label]" value="{{ $item['label'] }}"
                    class="related-label-hidden">

                <div class="col-span-3">
                    <select class="label-select w-full px-3 py-2 border rounded-md text-left" data-index="{{ $i }}">
                        <option value="">選択または入力</option>
                        @foreach($labelOptions as $opt)
                            <option value="{{ $opt }}" {{ (!$isOther && $item['label'] === $opt) ? 'selected' : '' }}>{{ $opt }}
                            </option>
                        @endforeach
                        <option value="その他" {{ $isOther ? 'selected' : '' }}>その他</option>
                    </select>
                </div>

                <div class="col-span-3">
                    <input type="text" class="label-other-input w-full px-3 py-2 border rounded-md text-left"
                        placeholder="名称を入力" style="{{ $isOther ? '' : 'display:none;' }}"
                        value="{{ $isOther ? $item['label'] : '' }}">
                </div>

                <div class="col-span-4">
                    <input type="url" name="related_urls[{{ $i }}][url]" value="{{ $item['url'] }}"
                        class="w-full px-3 py-2 border rounded-md related-url-input text-left"
                        placeholder="https://example.com">
                </div>

                <div class="col-span-1 flex items-center text-left">
                    <button type="button"
                        class="toggle-delete-related inline-flex items-center justify-center px-2 py-1 text-sm text-red-600 hover:bg-red-50 rounded"
                        title="削除/取消">
                        <span class="delete-mark text-red-600 font-bold mr-1" style="display:none;">×</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M6 2a1 1 0 00-.894.553L4 4H2a1 1 0 100 2h1v10a2 2 0 002 2h8a2 2 0 002-2V6h1a1 1 0 100-2h-2l-1.106-1.447A1 1 0 0014 2H6zm3 6a1 1 0 10-2 0v6a1 1 0 102 0V8z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="sr-only">削除</span>
                    </button>
                </div>
            </div>
        @endforeach
        @php
            // 次に追加するインデックスを data 属性に持たせる
            $nextIndex = count($related);
        @endphp
        <script>document.addEventListener('DOMContentLoaded', function () {
                const list = document.getElementById('related-urls-list');
                if (list) list.setAttribute('data-next-index', '{{ $nextIndex }}');
            });</script>
    </div>
    <div class="mt-3">
        <button type="button" id="add-related-url"
            class="px-4 py-2 bg-white border rounded text-sm hover:bg-gray-50">URLを追加</button>
    </div>
    @error('related_urls') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    @error('related_urls.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div class="flex justify-end">
    {{-- ボタンのテキストは親ビューから受け取る --}}
    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
        {{ $buttonText }}
    </button>
</div>

@push('scripts')
    <script>
        // クリップボードにコピー
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            element.select();
            element.setSelectionRange(0, 99999); // モバイル対応

            navigator.clipboard.writeText(element.value).then(function () {
                // 成功時の処理
                const button = event.target.closest('button');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fa-solid fa-check mr-1"></i>コピー完了';
                button.classList.remove('bg-gray-600', 'hover:bg-gray-700');
                button.classList.add('bg-green-600', 'hover:bg-green-700');

                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('bg-green-600', 'hover:bg-green-700');
                    button.classList.add('bg-gray-600', 'hover:bg-gray-700');
                }, 2000);
            }).catch(function (err) {
                console.error('コピーに失敗しました: ', err);
                alert('コピーに失敗しました。手動でコピーしてください。');
            });
        }

        // 招待トークンを生成
        function generateInviteToken(spaceId) {
            const button = event.target;
            const originalText = button.innerHTML;

            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>生成中...';

            @push('scripts')
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                            const list = document.getElementById('related-urls-list');
                            const addBtn = document.getElementById('add-related-url');

                            function bindRemove(btn) {
                                btn.addEventListener('click', function (ev) {
                                    const btnEl = ev.currentTarget;
                                    // 行コンテナを data-related-index 属性で特定
                                    const row = btnEl.closest('[data-related-index]');
                                    const deleteInput = row.querySelector('.related-delete-input');
                                    const mark = row.querySelector('.delete-mark');

                                    if (!deleteInput) return;

                                    // 対象要素: select とその他入力、および URL 入力
                                    const controls = row.querySelectorAll('select.label-select, input.label-other-input, input.related-url-input');

                                    if (deleteInput.value === '0') {
                                        // マークして無効化（取り消し可能）
                                        deleteInput.value = '1';
                                        row.classList.add('opacity-50');
                                        controls.forEach(i => i.setAttribute('disabled', 'disabled'));
                                        if (mark) mark.style.display = 'inline';
                                        btnEl.classList.add('bg-red-50');
                                        btnEl.setAttribute('title', '取消');
                                        btnEl.setAttribute('aria-pressed', 'true');
                                    } else {
                                        // マーク解除
                                        deleteInput.value = '0';
                                        row.classList.remove('opacity-50');
                                        controls.forEach(i => i.removeAttribute('disabled'));
                                        if (mark) mark.style.display = 'none';
                                        btnEl.classList.remove('bg-red-50');
                                        btnEl.setAttribute('title', '削除');
                                        btnEl.setAttribute('aria-pressed', 'false');
                                    }
                                });
                                            }

                            // 初期の削除ボタンにバインド (toggle-delete-related に統一)
                            document.querySelectorAll('.toggle-delete-related').forEach(bindRemove);
                            // ラベルセレクトのハンドラ: hidden の label を更新し、その他の場合は入力を表示
                            function bindLabelSelect(sel) {
                                                if (!sel) return;
                            const idx = sel.getAttribute('data-index');
                            // 行コンテナを data-related-index 属性で特定
                            const row = sel.closest('[data-related-index]');
                            const hidden = row ? row.querySelector('.related-label-hidden') : null;
                            const otherInput = row ? row.querySelector('.label-other-input') : null;

                            function update() {
                                                    const val = sel.value;
                            if (val === 'その他') {
                                                        if (otherInput) otherInput.style.display = '';
                            if (hidden) hidden.value = otherInput ? otherInput.value : '';
                                                    } else if (val === '') {
                                                        if (otherInput) otherInput.style.display = 'none';
                            if (hidden) hidden.value = '';
                                                    } else {
                                                        if (otherInput) otherInput.style.display = 'none';
                            if (hidden) hidden.value = val;
                                                    }
                                                }

                            sel.addEventListener('change', update);
                            if (otherInput) {
                                otherInput.addEventListener('input', function () {
                                    if (sel.value === 'その他') {
                                        hidden.value = otherInput.value;
                                    }
                                });
                                                }
                            // 初期同期
                            update();
                                            }
                            document.querySelectorAll('.label-select').forEach(bindLabelSelect);

                            addBtn.addEventListener('click', function () {
                                                const idx = parseInt(list.getAttribute('data-next-index') || '0', 10);
                            const row = document.createElement('div');
                            row.className = 'grid grid-cols-12 gap-3 items-center py-2 border rounded-md px-3';
                            row.setAttribute('data-related-index', idx);
                            row.innerHTML = `
                            <input type="hidden" name="related_urls[${idx}][_delete]" value="0" class="related-delete-input">
                                <input type="hidden" name="related_urls[${idx}][label]" value="" class="related-label-hidden">
                                    <div class="col-span-3">
                                        <select class="label-select w-full px-3 py-2 border rounded-md text-left" data-index="${idx}">
                                            <option value="">選択または入力</option>
                                            <option value="公式サイト">公式サイト</option>
                                            <option value="X">X</option>
                                            <option value="instagram">instagram</option>
                                            <option value="tiktok">tiktok</option>
                                            <option value="その他">その他</option>
                                        </select>
                                    </div>
                                    <div class="col-span-3">
                                        <input type="text" class="label-other-input w-full px-3 py-2 border rounded-md text-left" placeholder="名称を入力" style="display:none;" value="">
                                    </div>
                                    <div class="col-span-4">
                                        <input type="url" name="related_urls[${idx}][url]" value="" class="w-full px-3 py-2 border rounded-md related-url-input text-left" placeholder="https://example.com">
                                    </div>
                                    <div class="col-span-1 flex items-center text-left">
                                        <button type="button" class="toggle-delete-related inline-flex items-center justify-center px-2 py-1 text-sm text-red-600 hover:bg-red-50 rounded" title="削除/取消">
                                            <span class="delete-mark text-red-600 font-bold mr-1" style="display:none;">×</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-.894.553L4 4H2a1 1 0 100 2h1v10a2 2 0 002 2h8a2 2 0 002-2V6h1a1 1 0 100-2h-2l-1.106-1.447A1 1 0 0014 2H6zm3 6a1 1 0 10-2 0v6a1 1 0 102 0V8z" clip-rule="evenodd" /></svg>
                                            <span class="sr-only">削除</span>
                                        </button>
                                    </div>`;
                                                list.appendChild(row);
                                                bindRemove(row.querySelector('.toggle-delete-related'));
                                                bindLabelSelect(row.querySelector('.label-select'));
                                                list.setAttribute('data-next-index', (idx + 1).toString());
                                            });
                                        });
                </script>
            @endpush

    fetch(`/spaces/${spaceId}/generate-invite-token`, {
    method: 'POST',
    headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    }
    })
    .then(response => response.json())
    .then(data => {
    if (data.success) {
    // ページをリロードして新しいリンクを表示
    location.reload();
    } else {
    alert('招待リンクの生成に失敗しました。');
    button.disabled = false;
    button.innerHTML = originalText;
    }
    })
    .catch(error => {
    console.error('Error:', error);
    alert('エラーが発生しました。');
    button.disabled = false;
    button.innerHTML = originalText;
    });
    }

    // 公開範囲変更時のリアルタイム更新
    document.addEventListener('DOMContentLoaded', function () {
    const visibilityRadios = document.querySelectorAll('input[name="visibility"]');
    const guestLinkSection = document.getElementById('guest-link-section');

    if (visibilityRadios.length > 0 && guestLinkSection) {
    visibilityRadios.forEach(radio => {
    radio.addEventListener('change', function () {
    // 公開範囲変更時の説明を更新
    // 実際のリンク生成は保存後に行われることを示す
    if (this.value === '0') {
    guestLinkSection.innerHTML = `
    <div class="text-center py-4">
        <i class="fa-solid fa-lock text-gray-400 text-2xl mb-2"></i>
        <p class="text-sm text-gray-600">「限定公開」または「全体公開」に設定すると、ゲスト用リンクが生成されます。</p>
    </div>
    `;
    } else {
    guestLinkSection.innerHTML = `
    <div class="text-center py-4">
        <i class="fa-solid fa-info-circle text-blue-400 text-2xl mb-2"></i>
        <p class="text-sm text-blue-600">設定を保存すると、ゲスト用リンクが生成されます。</p>
    </div>
    `;
    }
    });
    });
    }
    });
    </script>
@endpush