<div class="mb-6 p-4 border rounded-lg bg-gray-50">
    <label for="url_search" class="block text-sm font-medium text-gray-700">URLからチャンネルIDを検索</label>
    <div class="mt-1 flex rounded-md shadow-sm">
        <input type="url" name="url_search" id="url_search"
            class="flex-1 block w-full rounded-none rounded-l-md border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="https://www.youtube.com/@aaaa">
        <button type="button" id="search-button"
            class="inline-flex items-center px-4 py-2 border border-l-0 border-gray-300 bg-gray-200 text-gray-800 rounded-r-md hover:bg-gray-300 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <i class="fa-solid fa-search"></i>
            <span class="ml-2">検索</span>
        </button>
    </div>
    <div id="search-result" class="mt-2 text-sm"></div>
</div>

@csrf
<div class="mb-4">
    <label for="youtube_channel_id" class="block text-sm font-medium text-gray-700">YouTubeチャンネルID</label>
    {{-- readonly属性を削除し、背景色を通常に戻す --}}
    <input type="text" name="youtube_channel_id" id="youtube_channel_id"
        value="{{ old('youtube_channel_id', $channel->youtube_channel_id ?? '') }}"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required placeholder="URLで検索するか、直接入力してください">
    @error('youtube_channel_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div class="flex items-center justify-end mt-4">
    <a href="{{ url()->previous() }}" class="text-gray-600 hover:underline mr-4">
        キャンセル
    </a>
    <x-primary-button>
        <i class="fa-solid fa-check mr-2"></i>
        <span>{{ $buttonText }}</span>
    </x-primary-button>
</div>

{{-- ページごとにスクリプトを読み込ませるための記述 --}}
@push('scripts')
    <script>
        // DOMの読み込みが完了したら実行
        document.addEventListener('DOMContentLoaded', function () {
            // 必要な要素を取得
            const searchButton = document.getElementById('search-button');
            const urlInput = document.getElementById('url_search');
            const channelIdInput = document.getElementById('youtube_channel_id');
            const resultDiv = document.getElementById('search-result');
            // CSRFトークンを取得
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // 検索ボタンがクリックされたときの処理
            searchButton.addEventListener('click', async function () {
                const url = urlInput.value;
                if (!url) {
                    resultDiv.innerHTML = `<p class="text-red-500">URLを入力してください。</p>`;
                    return;
                }

                // 検索中の表示
                resultDiv.innerHTML = `<p class="text-gray-500">検索中...</p>`;
                channelIdInput.value = '';

                try {
                    // 非同期でサーバーにリクエストを送信
                    const response = await fetch('{{ route("channels.findId") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ url: url })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.error || '不明なエラーが発生しました。');
                    }

                    // 成功した場合の処理
                    channelIdInput.value = data.channel_id;
                    resultDiv.innerHTML = `
                            <p class="text-green-600 font-semibold">チャンネルが見つかりました</p>
                            <p class="text-sm text-gray-700 mt-1"><b>チャンネル名:</b> ${data.channel_name}</p>
                            <p class="text-sm text-gray-700"><b>チャンネルID:</b> ${data.channel_id} をセットしました。</p>
                        `;

                } catch (error) {
                    // 失敗した場合の処理
                    resultDiv.innerHTML = `<p class="text-red-500">エラー: ${error.message}</p>`;
                }
            });
        });
    </script>
@endpush