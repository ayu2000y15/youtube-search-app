@extends('layouts.guest-app')

@section('content')
    <div class="pb-4 md:py-8 bg-gray-50">
        <style>
            /* ワクワクバッジアニメーション */
            @keyframes gentle-pulse {
                0% {
                    transform: scale(1);
                }

                50% {
                    transform: scale(1.04);
                }

                100% {
                    transform: scale(1);
                }
            }

            @keyframes quick-pop {
                0% {
                    transform: scale(1);
                }

                30% {
                    transform: scale(1.08);
                }

                60% {
                    transform: scale(0.98);
                }

                100% {
                    transform: scale(1);
                }
            }

            .badge-pulse {
                animation: gentle-pulse 1.6s ease-in-out infinite;
            }

            .badge-pop {
                animation: quick-pop 0.9s ease-in-out infinite;
            }

            /* 当日用の小さな点滅 */
            @keyframes blink {
                0% {
                    opacity: 1;
                }

                50% {
                    opacity: 0.55;
                }

                100% {
                    opacity: 1;
                }
            }

            .badge-blink {
                animation: blink 1s linear infinite;
            }
        </style>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8" style="height: calc(100vh - 120px);">
            <div x-data="{ activeTab: 'events' }"
                class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex flex-col h-full">

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
                <div class="px-6 pb-6 overflow-y-auto flex-grow">
                    {{-- イベントタブのコンテンツ --}}
                    <div x-show="activeTab === 'events'" x-cloak>
                        @php
                            // コントローラーから渡されたイベントを、最初の公演日に基づいて年ごとにグループ化
                            $eventsByYear = collect($events)
                                ->filter(function ($event) {
                                    return isset($event->schedules) && $event->schedules->isNotEmpty();
                                })
                                ->groupBy(function ($event) {
                                    // 最も早い公演日を取得し、その年を返す。日付がなければ 'TBA' (To Be Announced) とする
                                    $firstDate = optional($event->schedules->sortBy('performance_date')->first())->performance_date;
                                    return $firstDate ? \Carbon\Carbon::parse($firstDate)->year : 'TBA';
                                })
                                ->sortKeysDesc(); // 年を降順（新しい順）にソート
                        @endphp

                        @forelse ($eventsByYear as $year => $yearEvents)
                            {{-- 👇 年ごとのループとスティッキーヘッダー --}}
                            <div class="relative">
                                <h4
                                    class="sticky top-0 bg-gray-100 backdrop-blur-sm z-10 py-2 px-4 text-lg font-bold text-gray-800 border-b-2 border-gray-200">
                                    {{ $year }}年
                                </h4>

                                <div class="space-y-4 pt-4">
                                    @foreach ($yearEvents as $event)
                                        <div x-data="{ open: false }" x-cloak
                                            class="bg-white rounded-lg shadow-md transition-shadow duration-300 hover:shadow-xl border-l-4"
                                            style="margin-bottom: 10px; border-left-color: {{ $event->category->color ?? '#E5E7EB' }};">
                                            @php
                                                // 既存のロジックは変更せずにそのまま活用します
                                                $firstDate = optional($event->schedules->sortBy('performance_date')->first())->performance_date ?? null;
                                                $today = \Carbon\Carbon::today('Asia/Tokyo');
                                                $nextSchedule = $event->schedules->filter(function ($s) use ($today) {
                                                    return !empty($s->performance_date) && \Carbon\Carbon::parse($s->performance_date, 'Asia/Tokyo')->startOfDay()->gte($today);
                                                })->sortBy('performance_date')->first();
                                                $nextDate = optional($nextSchedule)->performance_date ?? null;
                                                $isFuture = $firstDate && \Carbon\Carbon::parse($firstDate, 'Asia/Tokyo')->startOfDay()->gte($today);

                                                $badgeText = '';
                                                $badgeClass = '';
                                                if ($nextDate) {
                                                    $nextCarbon = \Carbon\Carbon::parse($nextDate, 'Asia/Tokyo')->startOfDay();
                                                    $diff = (int) $today->diffInDays($nextCarbon);
                                                    $isToday = $nextCarbon->isSameDay($today);

                                                    if ($isToday) {
                                                        $badgeText = '当日！！';
                                                        $badgeClass = 'text-xs font-semibold text-white bg-red-600 px-3 py-1 rounded badge-blink';
                                                    } elseif ($diff === 1) {
                                                        $now = \Carbon\Carbon::now('Asia/Tokyo');
                                                        $hour = (int) $now->format('H');
                                                        if ($hour < 12) {
                                                            $badgeText = '明日！';
                                                            $badgeClass = 'text-xs font-semibold text-white bg-orange-500 px-1 py-1 rounded badge-pop';
                                                        } else {
                                                            $badgeText = 'いよいよ明日！';
                                                            $badgeClass = 'text-xs font-bold text-white bg-orange-600 px-3 py-1 rounded badge-pop';
                                                        }
                                                    } elseif ($diff >= 2 && $diff <= 6) {
                                                        $badgeText = 'あと' . $diff . '日 ☆';
                                                        $badgeClass = 'text-xs font-medium text-yellow-800 bg-yellow-100 px-3 py-1 rounded badge-pulse';
                                                    } elseif ($diff >= 7 && $diff <= 30) {
                                                        $badgeText = 'あと' . $diff . '日';
                                                        $badgeClass = 'text-xs font-medium text-white bg-green-600 px-1 py-1 rounded badge-pulse';
                                                    } else {
                                                        $badgeText = 'あと' . $diff . '日';
                                                        $badgeClass = 'text-xs font-medium text-gray-700 bg-gray-100 px-1 py-1 rounded';
                                                    }
                                                }

                                                // 小さなヘルパー: テキスト中のURLをアンカー化して別タブで開く
                                                $linkify = function ($text) {
                                                    if (empty($text)) {
                                                        return null;
                                                    }
                                                    $escaped = e($text);
                                                    // デリミタと文字クラスがぶつかるとエラーになるため、安全なパターンにする
                                                    // https:// から空白または '<' までをマッチ（典型的なURLマッチ）
                                                    $pattern = '!(https?://[^\\s<]+)!i';
                                                    $linked = preg_replace_callback($pattern, function ($m) {
                                                        $url = $m[1];
                                                        $label = $url;
                                                        return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">' . $label . '</a>';
                                                    }, $escaped);
                                                    return nl2br($linked);
                                                };
                                            @endphp

                                            {{-- カードヘッダー：クリックで詳細を開閉 --}}
                                            <div @click="open = !open"
                                                class="p-3 sm:p-4 cursor-pointer transition-colors duration-200 hover:bg-gray-50/80">
                                                <div class="flex items-center justify-between">
                                                    {{-- 左側：日付、カテゴリ、イベント名 --}}
                                                    <div class="flex items-center space-x-3 sm:space-x-4 min-w-0">
                                                        {{-- 日付表示ブロック --}}
                                                        <div class="flex-shrink-0 text-center w-12 sm:w-24">
                                                            @if ($firstDate)
                                                                <div
                                                                    class="text-xs {{ $isFuture ? 'text-gray-500' : 'text-gray-400' }}">
                                                                    {{ \Carbon\Carbon::parse($firstDate)->locale('ja')->isoFormat('YYYY') }}
                                                                </div>
                                                                <div
                                                                    class="text-base sm:text-2xl font-bold {{ $isFuture ? 'text-blue-700' : 'text-gray-600' }}">
                                                                    {{ \Carbon\Carbon::parse($firstDate)->locale('ja')->isoFormat('M/D') }}
                                                                </div>
                                                                <div
                                                                    class="text-xs sm:text-sm font-semibold {{ $isFuture ? 'text-blue-700' : 'text-gray-600' }}">
                                                                    ({{ \Carbon\Carbon::parse($firstDate)->locale('ja')->isoFormat('ddd') }})
                                                                </div>
                                                            @else
                                                                <div class="text-sm text-gray-400">開催日<br>未定</div>
                                                            @endif
                                                        </div>

                                                        {{-- イベント情報 --}}
                                                        <div class="flex-1 min-w-0">
                                                            {{-- [修正] カテゴリとバッジを同じ行に配置するためのコンテナ --}}
                                                            <div class="flex items-center space-x-2 mb-1">
                                                                @if($event->category)
                                                                    <span
                                                                        class="text-[10px] md:text-xs font-semibold px-2.5 py-0.5 md:py-1 rounded-full text-white inline-block"
                                                                        style="background-color: {{ $event->category->color }};">
                                                                        {{ $event->category->name }}
                                                                    </span>
                                                                @endif
                                                                {{-- [修正] 「あと○日」のバッジをここに移動 --}}
                                                                @if (!empty($badgeText))
                                                                    <span class="{{ $badgeClass }}">{!! $badgeText !!}</span>
                                                                @endif
                                                            </div>

                                                            <p
                                                                class="text-sm md:text-base sm:text-lg font-bold mt-1 {{ $isFuture ? 'text-gray-900' : 'text-gray-500' }}">
                                                                {{ $event->name }}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    {{-- 右側：開閉アイコン --}}
                                                    {{-- [修正] バッジを移動したため、ここはアイコンのみに変更 --}}
                                                    <div class="flex-shrink-0 ml-2 sm:ml-4">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="w-6 h-6 text-gray-500 transform transition-transform duration-300"
                                                            :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd"
                                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- 詳細コンテンツエリア --}}
                                            <div x-show="open" x-transition:enter="transition ease-out duration-300"
                                                x-transition:enter-start="opacity-0 transform -translate-y-2"
                                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                                x-transition:leave="transition ease-in duration-200"
                                                x-transition:leave-start="opacity-100 transform translate-y-0"
                                                x-transition:leave-end="opacity-0 transform -translate-y-2"
                                                class="border-t border-gray-200 bg-gray-50/70">
                                                <div class="p-4 sm:p-6 space-y-6">

                                                    {{-- スケジュールテーブル --}}
                                                    @if ($event->schedules->isNotEmpty())
                                                        <div>
                                                            <h5 class="font-bold text-gray-800 text-sm sm:text-base mb-3">🗓️ スケジュール
                                                            </h5>
                                                            <div class="overflow-x-auto">
                                                                <table class="min-w-full text-sm text-left text-gray-500">
                                                                    <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                                                                        <tr>
                                                                            <th scope="col" class="py-3 px-4 sm:px-6 whitespace-nowrap">
                                                                                公演名</th>
                                                                            <th scope="col" class="py-3 px-4 sm:px-6">開場</th>
                                                                            <th scope="col" class="py-3 px-4 sm:px-6">開演</th>
                                                                            <th scope="col" class="py-3 px-4 sm:px-6">終演</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach ($event->schedules->sortBy('performance_date') as $schedule)
                                                                            <tr class="bg-white border-b hover:bg-gray-50">
                                                                                <td class="py-4 px-4 sm:px-6 font-medium text-gray-900">
                                                                                    <span
                                                                                        class="block max-w-xs truncate">{{ $schedule->session_name ?? '' }}</span>
                                                                                </td>
                                                                                <td class="py-4 px-4 sm:px-6">
                                                                                    {{ $schedule->doors_open_time ? \Carbon\Carbon::parse($schedule->doors_open_time, 'Asia/Tokyo')->format('H:i') : '-' }}
                                                                                </td>
                                                                                <td class="py-4 px-4 sm:px-6">
                                                                                    {{ $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time, 'Asia/Tokyo')->format('H:i') : '-' }}
                                                                                </td>
                                                                                <td class="py-4 px-4 sm:px-6">
                                                                                    {{ $schedule->end_time ? \Carbon\Carbon::parse($schedule->end_time, 'Asia/Tokyo')->format('H:i') : '-' }}
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    {{-- チケット販売情報 --}}
                                                    @if ($event->ticketSales->isNotEmpty())
                                                        <div>
                                                            <h5 class="font-bold text-gray-800 text-sm sm:text-base mb-3">🎟️ チケット販売
                                                            </h5>
                                                            <div class="space-y-3">
                                                                @foreach ($event->ticketSales as $sale)
                                                                    <div class="border rounded-md p-3 sm:p-4 bg-white text-sm shadow-sm">
                                                                        <p class="font-bold text-gray-900 mb-2 text-sm sm:text-base">
                                                                            {{ $sale->sale_method_name }}
                                                                        </p>
                                                                        <dl class="space-y-2 text-xs sm:text-sm">
                                                                            <div class="flex items-start gap-3">
                                                                                <dt class="text-gray-500 flex-shrink-0 w-16 md:w-20">申込受付：
                                                                                </dt>
                                                                                <dd class="text-gray-900">
                                                                                    {{ $sale->app_starts_at ? \Carbon\Carbon::parse($sale->app_starts_at, 'Asia/Tokyo')->locale('ja')->isoFormat('YYYY/MM/DD (ddd) HH:mm') : '' }}
                                                                                    <br>〜
                                                                                    {{ $sale->app_ends_at ? \Carbon\Carbon::parse($sale->app_ends_at, 'Asia/Tokyo')->locale('ja')->isoFormat('MM/DD (ddd) HH:mm') : '' }}
                                                                                </dd>
                                                                            </div>
                                                                            <div class="flex items-start gap-3">
                                                                                <dt class="text-gray-500 flex-shrink-0 w-16 md:w-20">結果発表：
                                                                                </dt>
                                                                                <dd class="text-gray-900">
                                                                                    {{ $sale->results_at ? \Carbon\Carbon::parse($sale->results_at, 'Asia/Tokyo')->locale('ja')->isoFormat('YYYY/MM/DD (ddd) HH:mm') : '' }}
                                                                                </dd>
                                                                            </div>
                                                                            <div class="flex items-start gap-3">
                                                                                <dt class="text-gray-500 flex-shrink-0 w-16 md:w-20">支払期間：
                                                                                </dt>
                                                                                <dd class="text-gray-900">
                                                                                    {{ $sale->payment_starts_at ? \Carbon\Carbon::parse($sale->payment_starts_at, 'Asia/Tokyo')->locale('ja')->isoFormat('YYYY/MM/DD (ddd) HH:mm') : '' }}
                                                                                    <br>〜
                                                                                    {{ $sale->payment_ends_at ? \Carbon\Carbon::parse($sale->payment_ends_at, 'Asia/Tokyo')->locale('ja')->isoFormat('MM/DD (ddd) HH:mm') : '' }}
                                                                                </dd>
                                                                            </div>
                                                                        </dl>
                                                                        @if ($sale->notes)
                                                                            <div class="mt-3 pt-3 border-t">
                                                                                <p class="text-xs text-gray-600">{!! nl2br($sale->notes) !!}</p>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif

                                                    {{-- 詳細情報リスト --}}
                                                    <div>
                                                        <h5 class="font-bold text-gray-800 text-sm sm:text-base mb-4">ℹ️ イベント詳細</h5>
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5 text-sm">
                                                            <div class="flex items-start"><i
                                                                    class="fa-solid fa-location-dot text-gray-500 w-5 mt-1 text-center"></i>
                                                                <div class="ml-3">
                                                                    <dt class="font-semibold text-gray-800">会場</dt>
                                                                    <dd class="mt-1 text-gray-600 break-words">
                                                                        @php
                                                                            $venueText = $event->venue ?? '';
                                                                            // 全角／半角の丸括弧に対応して住所を抽出
                                                                            $address = null;
                                                                            if (preg_match('/[\(（]([^\)）]+)[\)）]/u', $venueText, $m)) {
                                                                                $address = trim($m[1]);
                                                                            }
                                                                        @endphp
                                                                        @if ($venueText)
                                                                            @if ($address)
                                                                                @php
                                                                                    $placeName = trim(preg_replace('/[\(（][^\)）]+[\)）]/u', '', $venueText));
                                                                                    $placeQuery = $placeName ?: $address;
                                                                                    $gmUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($placeQuery);
                                                                                    $appleUrl = 'maps://?q=' . urlencode($placeQuery);
                                                                                @endphp
                                                                                <div x-data="{ showMapChooser: false }"
                                                                                    class="inline-block relative">
                                                                                    <button type="button" @click="(function(){
                                                                                                                var ua = navigator.userAgent || navigator.vendor || window.opera;
                                                                                                                var isIOS = /iPhone|iPad|iPod/.test(ua) && !window.MSStream;
                                                                                                                var isAndroid = /Android/.test(ua);
                                                                                                                if (isIOS || isAndroid) {
                                                                                                                    showMapChooser = !showMapChooser;
                                                                                                                } else {
                                                                                                                    window.open('{{ $gmUrl }}', '_blank');
                                                                                                                }
                                                                                                            })()"
                                                                                        class="text-blue-600 hover:underline">{{ $placeName }}</button>

                                                                                    <div x-show="showMapChooser" x-cloak
                                                                                        @click.away="showMapChooser = false"
                                                                                        class="absolute z-50 bg-white border rounded shadow mt-2 right-0 p-2 w-44">
                                                                                        <a href="{{ $gmUrl }}" target="_blank"
                                                                                            rel="noopener noreferrer"
                                                                                            class="block px-2 py-1 text-sm hover:bg-gray-100">Google
                                                                                            Mapsで開く</a>
                                                                                        <a href="{{ $appleUrl }}"
                                                                                            class="block px-2 py-1 text-sm hover:bg-gray-100">Apple
                                                                                            Mapsで開く</a>
                                                                                        <a href="{{ $gmUrl }}" target="_blank"
                                                                                            rel="noopener noreferrer"
                                                                                            class="block px-2 py-1 text-sm hover:bg-gray-100">ブラウザで開く</a>
                                                                                    </div>
                                                                                </div>
                                                                            @else
                                                                                {{ $venueText }}
                                                                            @endif
                                                                        @else
                                                                            情報なし
                                                                        @endif
                                                                    </dd>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-start"><i
                                                                    class="fa-solid fa-link text-gray-500 w-5 mt-1 text-center"></i>
                                                                <div class="ml-3">
                                                                    <dt class="font-semibold text-gray-800">公式サイト</dt>
                                                                    <dd class="mt-1 text-blue-600 hover:underline break-all">
                                                                        @if($event->event_url)<a href="{{ $event->event_url }}"
                                                                            target="_blank"
                                                                        rel="noopener noreferrer">{{ $event->event_url }}</a>@else
                                                                            情報なし @endif
                                                                    </dd>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-start"><i
                                                                    class="fa-solid fa-users text-gray-500 w-5 mt-1 text-center"></i>
                                                                <div class="ml-3">
                                                                    <dt class="font-semibold text-gray-800">出演</dt>
                                                                    <dd class="mt-1 text-gray-600">
                                                                        {!! $event->performers ? nl2br(e($event->performers)) : '情報なし' !!}
                                                                    </dd>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-start"><i
                                                                    class="fa-solid fa-yen-sign text-gray-500 w-5 mt-1 text-center"></i>
                                                                <div class="ml-3">
                                                                    <dt class="font-semibold text-gray-800">料金</dt>
                                                                    <dd class="mt-1 text-gray-600">
                                                                        {!! $linkify($event->price_info) ?? '情報なし' !!}
                                                                    </dd>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-start col-span-1 md:col-span-2"><i
                                                                    class="fa-solid fa-file-alt text-gray-500 w-5 mt-1 text-center"></i>
                                                                <div class="ml-3">
                                                                    <dt class="font-semibold text-gray-800">内容</dt>
                                                                    <dd class="mt-1 text-gray-600">
                                                                        {!! $linkify($event->description) ?? '情報なし' !!}
                                                                    </dd>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-start col-span-1 md:col-span-2"><i
                                                                    class="fa-solid fa-pencil text-gray-500 w-5 mt-1 text-center"></i>
                                                                <div class="ml-3">
                                                                    <dt class="font-semibold text-gray-800">内部メモ</dt>
                                                                    <dd class="mt-1 text-gray-600">
                                                                        {!! $linkify($event->memo) ?? '情報なし' !!}
                                                                    </dd>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-gray-500 py-4">まだイベントは登録されていません。</p>
                        @endforelse
                    </div>

                    {{-- 年表タブのコンテンツ --}}
                    @foreach($timelines as $timeline)
                        <div x-show="activeTab === 'timeline-{{ $timeline->id }}'" x-cloak>
                            <div class="pt-2 pl-4" style="padding-left:20px">
                                <div class="relative border-l-4 border-gray-200 ml-6 sm:ml-8">
                                    @foreach ($timeline->verticalAxes as $axis)
                                        <div class="mb-10">
                                            <span class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full"
                                                style="position: absolute; left: -18px;">
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
                                                            <div x-data="{ open: false }"
                                                                class="border rounded-lg shadow-md overflow-hidden bg-white">
                                                                <div class="p-1 flex items-center justify-between">
                                                                    <div class="flex items-center space-x-3 min-w-0">
                                                                        <span
                                                                            class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded-full whitespace-nowrap">{{ $entry->category->name }}</span>
                                                                        <div class="min-w-0">
                                                                            <div class="text-sm truncate">{{ $entry->title }}</div>
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
                                                                    @if (!empty($entry->content))
                                                                        <div class="mb-3">
                                                                            <h5 class="text-sm font-medium text-gray-700">あらすじ</h5>
                                                                            <p class="text-sm text-gray-600 mt-1">
                                                                                {!! nl2br(e($entry->content)) !!}
                                                                            </p>
                                                                        </div>
                                                                    @endif
                                                                    @if (!empty($entry->memo))
                                                                        <div class="mb-3">
                                                                            <h5 class="text-sm font-medium text-gray-700">メモ</h5>
                                                                            <p class="text-sm text-gray-600 mt-1">
                                                                                {!! nl2br(e($entry->memo)) !!}
                                                                            </p>
                                                                        </div>
                                                                    @endif
                                                                    @if (!empty($entry->related_urls))
                                                                        <div class="mb-3">
                                                                            <h5 class="text-sm font-medium text-gray-700">関連URL</h5>
                                                                            <div class="mt-1 flex flex-wrap items-center">
                                                                                @foreach ($entry->related_urls as $url)
                                                                                    <a href="{{ $url['url'] }}" target="_blank"
                                                                                        class="text-xs text-blue-500 hover:underline mr-3 mb-2"><i
                                                                                            class="fa-solid fa-link mr-1"></i>{{ $url['label'] }}</a>
                                                                                @endforeach
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                    @if ($entry->videos->isNotEmpty())
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
                                                                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-black bg-opacity-60"><i
                                                                                                        class="fa-solid fa-play text-white text-sm"></i></span>
                                                                                            </div>
                                                                                        </a>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>
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