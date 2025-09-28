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
                        <span class="px-2 py-1 text-xs rounded-full
                                    @if($space->visibility === 2) bg-green-100 text-green-800
                                    @elseif($space->visibility === 1) bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800 @endif">
                            {{ $space->getVisibilityLabel() }}
                        </span>
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
