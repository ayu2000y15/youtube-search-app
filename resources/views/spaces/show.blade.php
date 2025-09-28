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
                        <span class="text-sm font-medium text-gray-700">{{ $space->name }}</span>
                    </div>
                </li>
            </ol>
        </nav>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- カード1: イベント一覧 --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center border-b pb-2 mb-4">
                        <h3 class="text-xl font-bold">イベント一覧</h3>
                        <x-button-link-primary href="{{ route('spaces.events.create', $space) }}">
                            <i class="fa-solid fa-plus"></i>
                            <span class="hidden ml-2 sm:inline">イベントを新規登録</span>
                        </x-button-link-primary>
                    </div>

                    @forelse ($space->events as $event)
                        <div x-data="{ open: false }" x-cloak class="border rounded-lg pt-4 px-4 mb-4 shadow">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3">
                                <div class="flex items-center">
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
                                        @php
                                            $first = $event->schedules->isNotEmpty() ? $event->schedules->sortBy('performance_date')->first() : null;
                                            $firstDate = $first->performance_date ?? null;
                                            $isFuture = false;
                                            if ($firstDate) {
                                                $firstCarbon = \Carbon\Carbon::parse($firstDate)->startOfDay();
                                                $today = \Carbon\Carbon::today();
                                                $isFuture = $firstCarbon->gte($today);
                                            }
                                            $dateClass = $firstDate ? ($isFuture ? 'text-blue-600' : 'text-gray-500') : 'text-gray-400';
                                            $titleClass = $isFuture ? 'text-gray-900' : 'text-gray-600';
                                        @endphp
                                        <span
                                            class="ml-2 text-lg font-semibold {{ $titleClass }} flex flex-col sm:flex-row sm:items-center">
                                            @if ($firstDate)
                                                <span
                                                    class="text-sm {{ $dateClass }} mb-1 sm:mb-0 sm:mr-3 text-left">{{ \Carbon\Carbon::parse($firstDate)->locale('ja')->isoFormat('YYYY/MM/DD (ddd)') }}</span>
                                            @endif
                                            <span class="text-left">{{ $event->name }}</span>
                                        </span>
                                    </button>
                                </div>
                                <div class="flex items-center space-x-2 mt-2 sm:mt-0 justify-end sm:justify-start self-end sm:self-auto"
                                    @click.stop>
                                    <x-button-link-secondary
                                        href="{{ route('spaces.events.edit', [$space, $event]) }}">
                                        <i class="fa-solid fa-pencil"></i><span
                                            class="hidden ml-2 sm:inline">編集</span>
                                    </x-button-link-secondary>
                                    <form method="POST"
                                        action="{{ route('spaces.events.destroy', [$space, $event]) }}"
                                        onsubmit="return confirm('本当に削除しますか？');">
                                        @csrf
                                        @method('DELETE')
                                        <x-button-danger>
                                            <i class="fa-solid fa-trash "></i>
                                            <span class="hidden ml-2 sm:inline">削除</span>
                                        </x-button-danger>
                                    </form>
                                </div>
                            </div>

                            <div x-show="open" x-transition class="mt-2">
                                @if ($event->schedules->isNotEmpty())
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-sm text-left text-gray-500">
                                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="py-3 px-6">公演名</th>
                                                    <th scope="col" class="py-3 px-6">公演日</th>
                                                    <th scope="col" class="py-3 px-6">開場</th>
                                                    <th scope="col" class="py-3 px-6">開演</th>
                                                    <th scope="col" class="py-3 px-6">終演</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($event->schedules->sortBy('performance_date') as $schedule)
                                                    <tr class="bg-white border-b">
                                                        <td class="py-4 px-6 font-medium text-gray-900">
                                                            {{ $schedule->session_name ?? '-' }}
                                                        </td>
                                                        <td class="py-4 px-6">
                                                            {{ $schedule->performance_date ? $schedule->performance_date->locale('ja')->isoFormat('YYYY/MM/DD (ddd)') : '-' }}
                                                        </td>
                                                        <td class="py-4 px-6">
                                                            {{ $schedule->doors_open_time ? \Carbon\Carbon::parse($schedule->doors_open_time)->format('H:i') : '-' }}
                                                        </td>
                                                        <td class="py-4 px-6">
                                                            {{ $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : '-' }}
                                                        </td>
                                                        <td class="py-4 px-6">
                                                            {{ $schedule->end_time ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i') : '-' }}
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
                                                                {{ $sale->app_starts_at ? \Carbon\Carbon::parse($sale->app_starts_at)->locale('ja')->isoFormat('YYYY/MM/DD (ddd) HH:mm') : '' }}
                                                                〜
                                                                {{ $sale->app_ends_at ? \Carbon\Carbon::parse($sale->app_ends_at)->locale('ja')->isoFormat('YYYY/MM/DD (ddd) HH:mm') : '' }}
                                                            </dd>
                                                        </div>
                                                        <div class="flex items-start gap-2">
                                                            <dt class="text-gray-500 flex-shrink-0">結果発表：</dt>
                                                            <dd class="text-gray-900">
                                                                {{ $sale->results_at ? \Carbon\Carbon::parse($sale->results_at)->locale('ja')->isoFormat('YYYY/MM/DD (ddd) HH:mm') : '-' }}
                                                            </dd>
                                                        </div>
                                                        <div class="flex items-start gap-2">
                                                            <dt class="text-gray-500 flex-shrink-0">支払期間：</dt>
                                                            <dd class="text-gray-900">
                                                                {{ $sale->payment_starts_at ? \Carbon\Carbon::parse($sale->payment_starts_at)->locale('ja')->isoFormat('YYYY/MM/DD (ddd) HH:mm') : '' }}
                                                                〜
                                                                {{ $sale->payment_ends_at ? \Carbon\Carbon::parse($sale->payment_ends_at)->locale('ja')->isoFormat('YYYY/MM/DD (ddd) HH:mm') : '' }}
                                                            </dd>
                                                        </div>
                                                    </div>
                                                    @if ($sale->notes)
                                                        <div class="mt-2 pt-2 border-t">
                                                            <p class="text-xs text-gray-600 whitespace-pre-line">
                                                                {{ $sale->notes }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div class="my-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div><strong>会場:</strong> {{ $event->venue ?? '情報なし' }}</div>
                                    <div><strong>公式サイト:</strong>
                                        @if ($event->event_url)
                                            <a href="{{ $event->event_url }}" target="_blank"
                                                class="text-blue-600 hover:underline">
                                                {{ $event->name }}
                                            </a>
                                        @else
                                            情報なし
                                        @endif
                                    </div>
                                    @if (empty($event->performers))
                                        <div><strong>出演:</strong> 情報なし</div>
                                    @else
                                        <div>
                                            <div class="font-semibold"><strong>出演:</strong></div>
                                            <div class="text-gray-700 break-words whitespace-pre-line">
                                                {{ $event->performers }}</div>
                                        </div>
                                    @endif

                                    @if (empty($event->price_info))
                                        <div><strong>料金:</strong> 情報なし</div>
                                    @else
                                        <div>
                                            <div class="font-semibold"><strong>料金:</strong></div>
                                            <div class="text-gray-700 break-words whitespace-pre-line">
                                                {{ $event->price_info }}</div>
                                        </div>
                                    @endif
                                    @if (empty($event->description))
                                        <div><strong>内容:</strong> 情報なし</div>
                                    @else
                                        <div>
                                            <div class="font-semibold"><strong>内容:</strong></div>
                                            <div class="text-gray-700 break-words whitespace-pre-line">
                                                {{ $event->description }}</div>
                                        </div>
                                    @endif
                                    @if (empty($event->internal_memo))
                                        <div><strong>内部メモ:</strong> 情報なし</div>
                                    @else
                                        <div>
                                            <div class="font-semibold"><strong>内部メモ:</strong></div>
                                            <div class="text-gray-700 break-words whitespace-pre-line">
                                                {{ $event->internal_memo }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-gray-500 py-4">
                            まだイベントは登録されていません。
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- カード2: 年表一覧 --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center border-b pb-2 mb-4">
                        <h3 class="text-xl font-bold">年表一覧</h3>
                        <x-button-link-primary href="{{ route('spaces.timelines.create', $space) }}">
                            <i class="fa-solid fa-plus"></i>
                            <span class="hidden ml-2 sm:inline">年表を新規作成</span>
                        </x-button-link-primary>
                    </div>

                    @forelse ($space->timelines as $timeline)
                        <div x-data="{ open: false }" x-cloak class="border rounded-lg pt-4 px-4 mb-4 shadow">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3">
                                <div class="flex items-center">
                                    <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                                        class="flex items-center p-1 mr-0 sm:mr-3 text-gray-800 hover:text-gray-400 focus:outline-none"
                                        aria-label="Toggle details">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4 transform transition-transform duration-200"
                                            :class="{ 'rotate-90': open }" viewBox="0 0 20 20"
                                            fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd"
                                                d="M7.21 14.78a1 1 0 01-1.42-1.42l5-5a1 1 0 011.42 0l5 5a1 1 0 01-1.42 1.42L12 10.41l-4.79 4.37z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <span class="ml-2 text-lg font-semibold">{{ $timeline->name }}</span>
                                    </button>
                                </div>
                                <div class="flex items-center space-x-2 mt-2 sm:mt-0 justify-end sm:justify-start self-end sm:self-auto"
                                    @click.stop>
                                    <x-button-link-secondary
                                        href="{{ route('timelines.edit', $timeline) }}">
                                        <i class="fa-solid fa-pencil"></i><span
                                            class="hidden ml-2 sm:inline">編集</span>
                                    </x-button-link-secondary>
                                    <form method="POST" action="{{ route('timelines.destroy', $timeline) }}"
                                        onsubmit="return confirm('年表を削除すると、関連する全てのデータが失われます。本当に削除しますか？');">
                                        @csrf
                                        @method('DELETE')
                                        <x-button-danger>
                                            <i class="fa-solid fa-trash "></i>
                                            <span class="hidden ml-2 sm:inline">削除</span>
                                        </x-button-danger>
                                    </form>
                                </div>
                            </div>

                            <div x-show="open" x-transition class="mt-2 border-t pt-4">
                                <p class="text-sm text-gray-600 mb-4">{{ $timeline->description ?? '説明がありません。' }}
                                </p>
                                <div class="mb-4">
                                    <x-button-link-primary href="{{ route('timelines.show', $timeline) }}">
                                        <i class="fa-solid fa-timeline mr-2"></i>
                                        <span>年表を見る</span>
                                    </x-button-link-primary>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-gray-500 py-4">
                            まだ年表は作成されていません。
                        </p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
