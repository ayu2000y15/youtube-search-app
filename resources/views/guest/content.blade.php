@extends('layouts.guest-app')

@section('content')
        <div class="py-8 bg-gray-50">
            <style>
                /* ワクワクバッジアニメーション */
                @keyframes gentle-pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.04); }
                    100% { transform: scale(1); }
                }
                @keyframes quick-pop {
                    0% { transform: scale(1); }
                    30% { transform: scale(1.08); }
                    60% { transform: scale(0.98); }
                    100% { transform: scale(1); }
                }
                .badge-pulse { animation: gentle-pulse 1.6s ease-in-out infinite; }
                .badge-pop { animation: quick-pop 0.9s ease-in-out infinite; }
                /* 当日用の小さな点滅 */
                @keyframes blink {
                    0% { opacity: 1; }
                    50% { opacity: 0.55; }
                    100% { opacity: 1; }
                }
                .badge-blink { animation: blink 1s linear infinite; }
            </style>
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8"style="height: calc(100vh - 120px);">
                <div x-data="{ activeTab: 'events' }" class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex flex-col h-full">

                    {{-- タブナビゲーション --}}
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                            {{-- イベントタブ --}}
                            <button @click="activeTab = 'events'"
                                    :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'events', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'events' }"
                                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                イベント一覧
                            </button>
                            {{-- 年表タブ --}}
                            @foreach($timelines as $timeline)
                                <button @click="activeTab = 'timeline-{{ $timeline->id }}'"
                                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'timeline-{{ $timeline->id }}', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'timeline-{{ $timeline->id }}' }"
                                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    {{ $timeline->name }}
                                </button>
                            @endforeach
                        </nav>
                    </div>

                    {{-- コンテンツエリア --}}
                    <div class="p-6 overflow-y-auto flex-grow">
                        {{-- イベントタブのコンテンツ --}}
                        <div x-show="activeTab === 'events'" x-cloak>
                            <div class="flex justify-between items-center border-b pb-2 mb-4">
                                <h3 class="text-xl font-bold">イベント一覧</h3>
                            </div>

                            @php
                                // コントローラーから渡された $events を使用します。
                                // コントローラー側で withMax による降順ソート済みのため、ここではソートを行わず
                                // 開催日時があるイベントのみフィルタリングします。
                                $sortedEvents = collect($events)->filter(function ($event) {
                                    return isset($event->schedules) && $event->schedules->isNotEmpty();
                                });
                            @endphp

                            @forelse ($sortedEvents as $event)
                                <div x-data="{ open: false }" x-cloak class="border rounded-lg pt-4 px-4 mb-4 shadow-sm">
                                    @php
                                        // 最も早い登録日（表示用） - null 安全に取得
                                        $firstDate = optional($event->schedules->sortBy('performance_date')->first())->performance_date ?? null;
                                        // 日本時間基準で判定する
                                        $today = \Carbon\Carbon::today('Asia/Tokyo');
                                        $nextSchedule = $event->schedules->filter(function($s) use ($today) {
                                            return !empty($s->performance_date) && \Carbon\Carbon::parse($s->performance_date, 'Asia/Tokyo')->startOfDay()->gte($today);
                                        })->sortBy('performance_date')->first();
                                        $nextDate = optional($nextSchedule)->performance_date ?? null;
                                        $isFuture = $firstDate && \Carbon\Carbon::parse($firstDate, 'Asia/Tokyo')->startOfDay()->gte($today);
                                        $dateClass = $isFuture ? 'text-blue-600' : 'text-gray-500';
                                        $titleClass = $isFuture ? 'text-gray-900' : 'text-gray-600';
                                            if ($nextDate) {
                                            $nextCarbon = \Carbon\Carbon::parse($nextDate, 'Asia/Tokyo')->startOfDay();
                                            // 今日から見て何日後かを取得（常に非負）
                                            $diff = (int) $today->diffInDays($nextCarbon);
                                            $isToday = $nextCarbon->isSameDay($today);
                                        } else {
                                            $diff = null;
                                            $isToday = false;
                                        }

                                        // バッジ表示の文言・クラスを決定（ワクワク表現）
                                        $badgeText = '';
                                        $badgeClass = '';
                                            if ($nextDate && isset($diff)) {
                                            if ($isToday) {
                                                $badgeText = '当日！！';
                                                $badgeClass = 'text-sm font-semibold text-white bg-red-600 px-3 py-1 rounded badge-blink';
                                            } elseif ($diff === 1) {
                                                // 現在の日本時間で時刻に応じて文言を変更
                                                $now = \Carbon\Carbon::now('Asia/Tokyo');
                                                $hour = (int) $now->format('H');
                                                // 午前中（0-11）は通常の「明日！」、正午以降（12-23）は強めの「いよいよ明日！」
                                                if ($hour < 12) {
                                                    $badgeText = '明日！';
                                                    $badgeClass = 'text-sm font-semibold text-white bg-orange-500 px-3 py-1 rounded badge-pop';
                                                } else {
                                                    $badgeText = 'いよいよ明日！';
                                                    // より目立たせるために濃いオレンジとポップアニメを使う
                                                    $badgeClass = 'text-sm font-bold text-white bg-orange-600 px-3 py-1 rounded badge-pop';
                                                }
                                            } elseif ($diff >= 2 && $diff <= 6) {
                                                $badgeText = 'あと' . $diff . '日 ✨';
                                                $badgeClass = 'text-sm font-medium text-yellow-800 bg-yellow-100 px-3 py-1 rounded badge-pulse';
                                            } elseif ($diff >= 7 && $diff <= 30) {
                                                $badgeText = 'あと' . $diff . '日';
                                                $badgeClass = 'text-sm font-medium text-white bg-green-600 px-3 py-1 rounded badge-pulse';
                                            } else {
                                                $badgeText = 'あと' . $diff . '日';
                                                $badgeClass = 'text-sm font-medium text-gray-700 bg-gray-100 px-3 py-1 rounded';
                                            }
                                        }
                                    @endphp

                                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3">
                                        <div class="flex items-start flex-1">
                                            <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                                                class="flex items-center p-1 mr-0 sm:mr-3 text-gray-800 hover:text-gray-400 focus:outline-none"
                                                aria-label="Toggle details">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="w-4 h-4 transform transition-transform duration-200"
                                                    :class="{ 'rotate-90': open }" viewBox="0 0 20 20" fill="currentColor"
                                                    aria-hidden="true">
                                                    <path fill-rule="evenodd"
                                                        d="M7.21 14.78a1 1 0 01-1.42-1.42l5-5a1 1 0 011.42 0l5 5a1 1 0 01-1.42 1.42L12 10.41l-4.79 4.37z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                <div class="ml-2 flex items-center">
                                                    {{-- 日付 --}}
                                                    @if ($firstDate)
                                                        <span
                                                            class="text-xs md:text-sm {{ $dateClass }} mr-3 text-left w-32 flex-shrink-0">{{ \Carbon\Carbon::parse($firstDate, 'Asia/Tokyo')->locale('ja')->isoFormat('YYYY/MM/DD (ddd)') }}</span>
                                                    @else
                                                        <span class="w-32 mr-3 flex-shrink-0"></span>
                                                    @endif

                                                    {{-- カテゴリとイベント名のコンテナ --}}
                                                    <div class="flex flex-col items-start">
                                                        @if($event->category)
                                                            <span class="text-xs font-semibold mb-1 px-2 py-0.5 rounded-full text-white" style="background-color: {{ $event->category->color }};">
                                                                {{ $event->category->name }}
                                                            </span>
                                                        @endif
                                                        <span class="text-sm md:text-base text-left font-semibold {{ $titleClass }}">{{ $event->name }}</span>
                                                    </div>
                                                </div>
                                            </button>
                                        </div>

                                        {{-- 右端のカウント表示（次回公演が未来なら表示。当日は当日を強調） --}}
                                        <div class="mt-2 sm:mt-0 sm:ml-4 flex items-center">
                                                @if (!empty($badgeText))
                                                    <span class="{{ $badgeClass }}">{!! $badgeText !!}</span>
                                                @endif
                                        </div>
                                    </div>

                                    <div x-show="open" x-transition class="px-4 pb-4">
                                        <div x-show="open" x-transition class="mt-2">
                                            @if ($event->schedules->isNotEmpty())
                                                <div class="overflow-x-auto">
                                                    <table class="min-w-full text-sm text-left text-gray-500">
                                                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                                            <tr>
                                                                <th scope="col" class="py-3 px-6 whitespace-nowrap">公演名</th>
                                                                <th scope="col" class="py-3 px-6">開場</th>
                                                                <th scope="col" class="py-3 px-6">開演</th>
                                                                <th scope="col" class="py-3 px-6">終演</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($event->schedules->sortBy('performance_date') as $schedule)
                                                                <tr class="bg-white border-b">
                                                                    <td class="py-4 px-6 font-medium text-gray-900">
                                                                        <span class="block max-w-xs truncate">{{ $schedule->session_name ?? '' }}</span>
                                                                    </td>
                                                                    <td class="py-4 px-6">
                                                                        {{ $schedule->doors_open_time ? \Carbon\Carbon::parse($schedule->doors_open_time, 'Asia/Tokyo')->format('H:i') : '' }}
                                                                    </td>
                                                                    <td class="py-4 px-6">
                                                                        {{ $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time, 'Asia/Tokyo')->format('H:i') : '' }}
                                                                    </td>
                                                                    <td class="py-4 px-6">
                                                                        {{ $schedule->end_time ? \Carbon\Carbon::parse($schedule->end_time, 'Asia/Tokyo')->format('H:i') : '' }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <p class="text-sm text-gray-500">開催日時は登録されていません。</p>
                                            @endif

                                            @if ($event->ticketSales->isNotEmpty())
                                                <div class="mt-6">
                                                    <h5 class="font-semibold mb-2 text-gray-800">チケット販売情報</h5>
                                                    <div class="space-y-3">
                                                        @foreach ($event->ticketSales as $sale)
                                                            <div class="border rounded-md p-3 bg-gray-50 text-sm">
                                                                <p class="font-bold text-base text-gray-900 mb-2">
                                                                    {{ $sale->sale_method_name }}</p>
                                                                <div class="space-y-1 text-sm">
                                                                    <div class="flex items-start gap-2">
                                                                        <dt class="text-gray-500 flex-shrink-0">申込受付：</dt>
                                                                        <dd class="text-gray-900">
                                                                            {{ $sale->app_starts_at ? \Carbon\Carbon::parse($sale->app_starts_at, 'Asia/Tokyo')->locale('ja')->isoFormat('YYYY/MM/DD (ddd) HH:mm') : '' }}
                                                                            〜
                                                                            {{ $sale->app_ends_at ? \Carbon\Carbon::parse($sale->app_ends_at, 'Asia/Tokyo')->locale('ja')->isoFormat('YYYY/MM/DD (ddd) HH:mm') : '' }}
                                                                        </dd>
                                                                    </div>
                                                                    <div class="flex items-start gap-2">
                                                                        <dt class="text-gray-500 flex-shrink-0">結果発表：</dt>
                                                                        <dd class="text-gray-900">
                                                                            {{ $sale->results_at ? \Carbon\Carbon::parse($sale->results_at, 'Asia/Tokyo')->locale('ja')->isoFormat('YYYY/MM/DD (ddd) HH:mm') : '' }}
                                                                        </dd>
                                                                    </div>
                                                                    <div class="flex items-start gap-2">
                                                                        <dt class="text-gray-500 flex-shrink-0">支払期間：</dt>
                                                                        <dd class="text-gray-900">
                                                                            {{ $sale->payment_starts_at ? \Carbon\Carbon::parse($sale->payment_starts_at, 'Asia/Tokyo')->locale('ja')->isoFormat('YYYY/MM/DD (ddd) HH:mm') : '' }}
                                                                            〜
                                                                            {{ $sale->payment_ends_at ? \Carbon\Carbon::parse($sale->payment_ends_at, 'Asia/Tokyo')->locale('ja')->isoFormat('YYYY/MM/DD (ddd) HH:mm') : '' }}
                                                                        </dd>
                                                                    </div>
                                                                </div>
                                                                @if ($sale->notes)
                                                                    <div class="mt-2 pt-2 border-t">
                                                                        <p class="text-xs text-gray-600 ">
                                                                            {{ $sale->notes }}</p>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="my-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                                    @if (empty($event->venue))
                                                        <div><strong>会場:</strong> 情報なし</div>
                                                    @else
                                                        <div>
                                                            <div class="font-semibold"><strong>会場:</strong></div>
                                                            <div class="text-gray-700 break-words ">
                                                                {{ $event->venue }}</div>
                                                        </div>
                                                    @endif

                                                @if (empty($event->event_url))
                                                    <div><strong>公式サイト:</strong> 情報なし</div>
                                                @else
                                                    <div>
                                                        <div class="font-semibold"><strong>公式サイト:</strong></div>
                                                        <a href="{{ $event->event_url }}" target="_blank" class="text-blue-600 hover:underline">
                                                            {{ $event->event_url }}
                                                        </a>
                                                    </div>
                                                @endif

                                                @if (empty($event->performers))
                                                    <div><strong>出演:</strong> 情報なし</div>
                                                @else
                                                    <div>
                                                        <div class="font-semibold"><strong>出演:</strong></div>
                                                        <div class="text-gray-700 break-words ">
                                                            {!! nl2br(e($event->performers)) !!}</div>
                                                    </div>
                                                @endif

                                                @if (empty($event->price_info))
                                                    <div><strong>料金:</strong> 情報なし</div>
                                                @else
                                                    <div>
                                                        <div class="font-semibold"><strong>料金:</strong></div>
                                                        <div class="text-gray-700 break-words ">
                                                            {!! nl2br(e($event->price_info)) !!}</div>
                                                    </div>
                                                @endif

                                                @if (empty($event->description))
                                                    <div><strong>内容:</strong> 情報なし</div>
                                                @else
                                                    <div>
                                                        <div class="font-semibold"><strong>内容:</strong></div>
                                                        <div class="text-gray-700 break-words ">
                                                            {!! nl2br(e($event->description)) !!}</div>
                                                    </div>
                                                @endif

                                                @if (empty($event->memo))
                                                    <div><strong>内部メモ:</strong> 情報なし</div>
                                                @else
                                                    <div>
                                                        <div class="font-semibold"><strong>内部メモ:</strong></div>
                                                        <div class="text-gray-700 break-words ">
                                                            {!! nl2br(e($event->memo)) !!}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-center text-gray-500 py-4">まだイベントは登録されていません。</p>
                            @endforelse
                        </div>

                        {{-- 年表タブのコンテンツ --}}
    @foreach($timelines as $timeline)
        <div x-show="activeTab === 'timeline-{{ $timeline->id }}'" x-cloak>
            <div class="pl-4" style="padding-left:20px">
                <div class="relative border-l-4 border-gray-200 ml-6 sm:ml-8">
                    @foreach ($timeline->verticalAxes as $axis)
                        <div class="mb-10">
                            <span class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full" style="position: absolute; left: -18px;">
                                <i class="fa-solid fa-calendar-days text-blue-800"></i>
                            </span>
                            <div>
                                <div class="pt-1 ml-4 text-base font-bold text-gray-800">
                                    {{ $axis->label }}
                                </div>
                                <div class="ml-4 py-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        @php
                                            $sortedEntries = $axis->historyEntries->sortBy(fn($entry) => $entry->axis_type === 'date' ? $entry->axis_date : $entry->axis_custom_value);
                                        @endphp
                                        @forelse ($sortedEntries as $entry)
                                            <div x-data="{ open: false }" class="border rounded-lg shadow-md overflow-hidden bg-white">
                                                <div class="p-1 flex items-center justify-between">
                                                    <div class="flex items-center space-x-3 min-w-0">
                                                        <span class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded-full whitespace-nowrap">{{ $entry->category->name }}</span>
                                                        <div class="min-w-0">
                                                            <div class="text-sm truncate">{{ $entry->title }}</div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center space-x-2">
                                                        <button x-on:click="open = !open" class="text-sm text-gray-600 hover:text-gray-800 focus:outline-none">
                                                            <span x-show="!open">表示 ▾</span>
                                                            <span x-show="open">閉じる ▴</span>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div x-show="open" x-cloak class="border-t bg-gray-50 p-4">
                                                    @if (!empty($entry->content))
                                                        <div class="mb-3"><h5 class="text-sm font-medium text-gray-700">あらすじ</h5><p class="text-sm text-gray-600 mt-1">{!! nl2br(e($entry->content)) !!}</p></div>
                                                    @endif
                                                    @if (!empty($entry->memo))
                                                        <div class="mb-3"><h5 class="text-sm font-medium text-gray-700">メモ</h5><p class="text-sm text-gray-600 mt-1">{!! nl2br(e($entry->memo)) !!}</p></div>
                                                    @endif
                                                    @if (!empty($entry->related_urls))
                                                        <div class="mb-3">
                                                            <h5 class="text-sm font-medium text-gray-700">関連URL</h5>
                                                            <div class="mt-1 flex flex-wrap items-center">
                                                                @foreach ($entry->related_urls as $url)
                                                                    <a href="{{ $url['url'] }}" target="_blank" class="text-xs text-blue-500 hover:underline mr-3 mb-2"><i class="fa-solid fa-link mr-1"></i>{{ $url['label'] }}</a>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                    @if ($entry->videos->isNotEmpty())
                                                        <div class="mb-3">
                                                            <h5 class="text-sm font-medium text-gray-700">関連動画</h5>
                                                            <div class="mt-1"><div class="flex flex-wrap items-center gap-2">
                                                                @foreach ($entry->videos as $video)
                                                                    <a href="https://youtu.be/{{ $video->youtube_video_id }}" target="_blank" rel="noopener noreferrer" class="group relative block w-20 h-12 overflow-hidden rounded-md border bg-gray-50 hover:shadow-md">
                                                                        <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}" class="w-full h-full object-cover">
                                                                        <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30 opacity-0 group-hover:opacity-100 transition-opacity">
                                                                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-black bg-opacity-60"><i class="fa-solid fa-play text-white text-sm"></i></span>
                                                                        </div>
                                                                    </a>
                                                                @endforeach
                                                            </div></div>
                                                        </div>
                                                    @endif
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
    @endforeach
                    </div>
                </div>
            </div>
        </div>
@endsection
