<x-app-layout>
    <x-slot name="header">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('spaces.index') }}"
                        class="text-sm font-medium text-gray-500 hover:underline inline-flex items-center">
                        <i class="fa-solid fa-house mr-2"></i> マイスペース
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                        <a href="{{ route('spaces.show', $timeline->space) }}"
                            class="text-sm font-medium text-gray-500 hover:underline">{{ $timeline->space->name }}</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                        <span class="text-sm font-medium text-gray-700">{{ $timeline->name }}</span>
                    </div>
                </li>
            </ol>
        </nav>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{-- 年表ヘッダー --}}
                    <div class="flex justify-between items-center border-b pb-4 mb-6">
                        <div>
                            <h2 class="text-2xl font-bold">{{ $timeline->name }}</h2>
                            <p class="text-sm text-gray-600 mt-1">{{ $timeline->description }}</p>
                        </div>
                        <x-button-link-primary href="{{ route('timelines.history-entries.create', $timeline) }}">
                            <i class="fa-solid fa-plus mr-2"></i>
                            <span>項目を追加</span>
                        </x-button-link-primary>
                    </div>

                    {{-- 年表本体 --}}
                    <div class="pl-4" style="padding-left:20px">
                        <div class="relative border-l-4 border-gray-200  ml-6 sm:ml-8">
                            @foreach ($timeline->verticalAxes as $axis)
                                <div class="mb-10">
                                    {{-- タイムライン上のドット --}}
                                    <span class=" flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full "
                                        style="position: absolute; left: -18px; ">
                                        <i class="fa-solid fa-calendar-days text-blue-800"></i>
                                    </span>

                                    {{-- ラベルと項目全体を囲むコンテナを追加し、マージンをここに適用 --}}
                                    <div>
                                        {{-- 縦軸ラベル --}}
                                        <div class="pt-1 ml-4 text-base font-bold text-gray-800">
                                            {{ $axis->label }}
                                        </div>

                                        {{-- この縦軸に属する項目 --}}
                                        <div class="ml-4 py-4">
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                                @php
                                                    // 項目を日付またはカスタム値でソート
                                                    $sortedEntries = $axis->historyEntries->sortBy(function ($entry) {
                                                        return $entry->axis_type === 'date' ? $entry->axis_date : $entry->axis_custom_value;
                                                    });
                                                @endphp

                                                @forelse ($sortedEntries as $entry)
                                                    <div x-data="{ open: false }"
                                                        class="border rounded-lg shadow-md overflow-hidden bg-white">
                                                        <div class="p-1 flex items-center justify-between">
                                                            <div class="flex items-center space-x-3 min-w-0">
                                                                <span
                                                                    class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded-full whitespace-nowrap">{{ $entry->category->name }}</span>
                                                                <div class="min-w-0">

                                                                    <div class="text-sm truncate">
                                                                        {{ $entry->title }}
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="flex items-center space-x-2">
                                                                <button x-on:click="open = !open"
                                                                    class="text-sm text-gray-600 hover:text-gray-800 focus:outline-none">
                                                                    <span x-show="!open">表示 ▾</span>
                                                                    <span x-show="open">閉じる ▴</span>
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <div x-show="open" x-cloak class="border-t bg-gray-50 p-4">
                                                            {{-- 展開領域: 内容・メモ・関連URL・関連動画リンク --}}
                                                            @if (!empty($entry->content))
                                                                <div class="mb-3">
                                                                    <h5 class="text-sm font-medium text-gray-700">あらすじ</h5>
                                                                    <p class="text-sm text-gray-600 mt-1 ">
                                                                        {!! nl2br($entry->content) !!}
                                                                    </p>
                                                                </div>
                                                            @endif

                                                            @if (!empty($entry->memo))
                                                                <div class="mb-3">
                                                                    <h5 class="text-sm font-medium text-gray-700">メモ</h5>
                                                                    <p class="text-sm text-gray-600 mt-1 ">
                                                                        {!! nl2br($entry->memo) !!}
                                                                    </p>
                                                                </div>
                                                            @endif

                                                            @if (!empty($entry->related_urls))
                                                                <div class="mb-3">
                                                                    <h5 class="text-sm font-medium text-gray-700">関連URL</h5>
                                                                    <div class="mt-1 flex flex-wrap items-center">
                                                                        @foreach ($entry->related_urls as $url)
                                                                            <a href="{{ $url['url'] }}" target="_blank"
                                                                                class="text-xs text-blue-500 hover:underline mr-3 mb-2">
                                                                                <i class="fa-solid fa-link mr-1"></i>{{ $url['label'] }}
                                                                            </a>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            @if (method_exists($entry, 'videos') && $entry->videos->isNotEmpty())
                                                                <div class="mb-3">
                                                                    <h5 class="text-sm font-medium text-gray-700">関連動画</h5>
                                                                    <div class="mt-1">
                                                                        <div class="flex flex-wrap items-center gap-2">
                                                                            @foreach ($entry->videos as $video)
                                                                                <a href="https://youtu.be/{{ $video->youtube_video_id }}"
                                                                                    target="_blank" rel="noopener noreferrer"
                                                                                    class="group relative block w-20 h-12 overflow-hidden rounded-md border bg-gray-50 hover:shadow-md">
                                                                                    <img src="{{ $video->thumbnail_url }}"
                                                                                        alt="{{ $video->title }}"
                                                                                        class="w-full h-full object-cover">
                                                                                    <div
                                                                                        class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30 opacity-0 group-hover:opacity-100 transition-opacity">
                                                                                        <span
                                                                                            class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-black bg-opacity-60">
                                                                                            <i
                                                                                                class="fa-solid fa-play text-white text-sm"></i>
                                                                                        </span>
                                                                                    </div>
                                                                                </a>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            <div class="mt-4 flex justify-end space-x-2">
                                                                <x-button-link-secondary
                                                                    href="{{ route('history-entries.edit', $entry) }}"
                                                                    class="py-1 px-2 text-xs">編集</x-button-link-secondary>
                                                                <form method="POST"
                                                                    action="{{ route('history-entries.destroy', $entry) }}"
                                                                    onsubmit="return confirm('本当に削除しますか？');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <x-button-danger
                                                                        class="py-1 px-2 text-xs">削除</x-button-danger>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p class="text-sm text-gray-400 col-span-full">-</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>