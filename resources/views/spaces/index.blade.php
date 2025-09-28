<x-app-layout>
    <x-slot name="header">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fa-solid fa-home mr-2 text-gray-500"></i>
                        <span class="text-sm font-medium text-gray-500">
                            マイスペース
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
                    <div class="flex justify-end mb-4">
                        {{-- 新規作成ボタンをコンポーネントに変更 --}}
                        <x-button-link-primary href="{{ route('spaces.create') }}">
                            <i class="fa-solid fa-plus mr-2"></i>
                            <span>新規作成</span>
                        </x-button-link-primary>
                    </div>

                    <ul class="space-y-4">
                        @forelse ($spaces as $space)
                            <li class="border p-4 rounded-lg flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-bold">{{ $space->name }}</h3>
                                    <p class="text-sm text-gray-600">
                                        公開範囲:
                                        @if ($space->visibility === 0) 自分のみ
                                        @elseif ($space->visibility === 1) 限定公開
                                        @else 全体公開
                                        @endif
                                    </p>
                                    @php
                                        $guestUrl = $space->getGuestUrl();
                                    @endphp
                                    @if($guestUrl)
                                        <div class="mt-2 text-sm text-gray-700 flex items-center space-x-2">
                                            <a href="{{ $guestUrl }}" target="_blank" rel="noopener"
                                                class="truncate text-blue-600 hover:underline">{{ $guestUrl }}</a>
                                            <button type="button" data-url="{{ $guestUrl }}"
                                                class="copy-url-btn inline-flex items-center px-2 py-1 text-xs bg-gray-100 border border-gray-200 rounded hover:bg-gray-200">コピー</button>
                                        </div>
                                    @endif
                                    {{-- 登録された関連リンクを表示 --}}
                                    @if(!empty($space->related_urls) && is_array($space->related_urls))
                                        <div class="mt-2 flex flex-wrap items-center space-x-3 text-sm">
                                            @foreach($space->related_urls as $rel)
                                                @php
                                                    // 正規化: 期待する形は ['label' => ..., 'url' => ...]
                                                    $label = is_array($rel) && isset($rel['label']) ? $rel['label'] : (is_string($rel) ? '' : '');
                                                    $url = is_array($rel) && isset($rel['url']) ? $rel['url'] : (is_string($rel) ? $rel : '');
                                                    $iconClass = 'fa-solid fa-link';
                                                    // ラベルに応じてブランドアイコンに変更
                                                    if (in_array($label, ['公式サイト'])) {
                                                        $iconClass = 'fa-solid fa-globe';
                                                    } elseif (in_array($label, ['X'])) {
                                                        $iconClass = 'fa-brands fa-x-twitter';
                                                    } elseif (in_array($label, ['instagram'])) {
                                                        $iconClass = 'fa-brands fa-instagram';
                                                    } elseif (in_array($label, ['tiktok'])) {
                                                        $iconClass = 'fa-brands fa-tiktok';
                                                    }
                                                @endphp
                                                @if(!empty($url))
                                                    @php
                                                        // ブランド判定: ラベルまたは URL から判定
                                                        $lowerLabel = mb_strtolower((string) $label);
                                                        $lowerUrl = mb_strtolower((string) $url);
                                                        $btnBg = 'bg-gray-50 text-gray-700';
                                                        $fa = 'fa-solid fa-link';
                                                        if ($lowerLabel === 'youtube' || str_contains($lowerUrl, 'youtube.com') || str_contains($lowerUrl, 'youtu.be')) {
                                                            $fa = 'fa-brands fa-youtube';
                                                            $btnBg = 'bg-red-600 text-white';
                                                        } elseif ($lowerLabel === 'x' || str_contains($lowerUrl, 'x.com') || str_contains($lowerUrl, 'twitter.com')) {
                                                            $fa = 'fa-brands fa-x-twitter';
                                                            $btnBg = 'bg-black text-white';
                                                        } elseif ($lowerLabel === 'instagram' || str_contains($lowerUrl, 'instagram.com')) {
                                                            $fa = 'fa-brands fa-instagram';
                                                            $btnBg = 'bg-gradient-to-tr from-pink-500 via-purple-600 to-yellow-400 text-white';
                                                        } elseif ($lowerLabel === 'tiktok' || str_contains($lowerUrl, 'tiktok.com')) {
                                                            $fa = 'fa-brands fa-tiktok';
                                                            $btnBg = 'bg-black text-white';
                                                        } elseif ($lowerLabel === '公式サイト' || $lowerLabel === 'official' || $lowerLabel === '') {
                                                            // 公式サイトやラベル未設定は地球アイコン (淡色背景)
                                                            $fa = 'fa-solid fa-globe';
                                                            $btnBg = 'bg-gray-50 text-gray-700';
                                                        }
                                                        // カラー系（text-white を含む）だと境界線は不要
                                                        $borderClass = str_contains($btnBg, 'text-white') ? '' : 'border border-gray-200';
                                                    @endphp
                                                    <a href="{{ $url }}" target="_blank" rel="noopener"
                                                        title="{{ $label !== '' ? $label : $url }}"
                                                        aria-label="{{ $label !== '' ? $label : $url }}"
                                                        class="inline-flex items-center">
                                                        <span
                                                            class="w-8 h-8 rounded-full flex items-center justify-center {{ $btnBg }} {{ $borderClass }}">
                                                            <i class="{{ $fa }}"></i>
                                                        </span>
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="flex flex-col items-end space-y-2">
                                    <div class="flex items-center space-x-2">
                                        {{-- イベントボタン --}}
                                        <x-button-link-event href="{{ route('spaces.show', $space) }}"
                                            class="bg-purple-600 text-white border-transparent hover:bg-purple-700 focus:ring-purple-500">
                                            <i class="fa-solid fa-calendar-days mr-2"></i>
                                            <span>イベント管理</span>
                                        </x-button-link-event>
                                        {{-- 編集ボタン --}}
                                        <x-button-link-secondary href="{{ route('spaces.edit', $space) }}">
                                            <i class="fa-solid fa-pencil mr-2"></i>
                                            <span>編集</span>
                                        </x-button-link-secondary>

                                        {{-- 削除ボタン --}}
                                        <form action="{{ route('spaces.destroy', $space) }}" method="POST"
                                            onsubmit="return confirm('本当に削除しますか？');">
                                            @csrf
                                            @method('DELETE')
                                            <x-button-danger>
                                                <i class="fa-solid fa-trash mr-2"></i>
                                                <span>削除</span>
                                            </x-button-danger>
                                        </form>
                                    </div>

                                    <div>
                                        <x-button-link-primary href="{{ route('spaces.channels.index', $space) }}"
                                            class="bg-gray-600 hover:bg-gray-800">
                                            <i class="fa-brands fa-youtube mr-2"></i>
                                            <span>チャンネル管理</span>
                                        </x-button-link-primary>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <p>まだスペースが作成されていません。</p>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.copy-url-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const url = btn.getAttribute('data-url');
                try {
                    await navigator.clipboard.writeText(url);
                    const original = btn.textContent;
                    btn.textContent = 'コピーしました';
                    setTimeout(() => btn.textContent = original, 1500);
                } catch (err) {
                    // フォールバック: テキストを選択して手動コピー促す
                    const input = document.createElement('input');
                    input.value = url;
                    document.body.appendChild(input);
                    input.select();
                    try {
                        document.execCommand('copy');
                        const original = btn.textContent;
                        btn.textContent = 'コピーしました';
                        setTimeout(() => btn.textContent = original, 1500);
                    } catch (e) {
                        alert('コピーに失敗しました。手動でコピーしてください。');
                    }
                    input.remove();
                }
            });
        });
    });
</script>