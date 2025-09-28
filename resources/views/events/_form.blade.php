{{-- バリデーションエラーの表示 --}}
@if ($errors->any())
    <div class="mb-4">
        <ul class="mt-3 list-disc list-inside text-sm text-red-600">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@csrf

{{-- 基本情報 --}}
<div class="mb-8">
    <h3 class="text-lg font-semibold border-b mb-4 pb-2">基本情報</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="name" class="block font-medium text-sm text-gray-700">イベント名 <span
                    class="text-red-500">*</span></label>
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $event->name ?? '')" required autofocus />
        </div>
        <div>
            <label for="venue" class="block font-medium text-sm text-gray-700">会場</label>
            <x-text-input id="venue" class="block mt-1 w-full" type="text" name="venue" :value="old('venue', $event->venue ?? '')" />
        </div>
        <div>
            <x-input-label for="event_url" :value="__('イベントURL')" />
            <x-text-input id="event_url" class="block mt-1 w-full" type="url" name="event_url" :value="old('event_url', $event->event_url ?? '')" />
        </div>
        <div class="md:col-span-2">
            <x-input-label for="performers" :value="__('出演')" />
            <textarea id="performers" name="performers" rows="3"
                class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block mt-1 w-full">{{ old('performers', $event->performers ?? '') }}</textarea>
        </div>
        <div class="md:col-span-2">
            <x-input-label for="price_info" :value="__('料金')" />
            <textarea id="price_info" name="price_info" rows="3"
                class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block mt-1 w-full">{{ old('price_info', $event->price_info ?? '') }}</textarea>
        </div>
        <div class="md:col-span-2">
            <x-input-label for="description" :value="__('内容')" />
            <textarea id="description" name="description" rows="5"
                class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block mt-1 w-full">{{ old('description', $event->description ?? '') }}</textarea>
        </div>
        <div class="md:col-span-2">
            <x-input-label for="internal_memo" :value="__('内部用メモ')" />
            <textarea id="internal_memo" name="internal_memo" rows="3"
                class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block mt-1 w-full">{{ old('internal_memo', $event->internal_memo ?? '') }}</textarea>
        </div>
    </div>
</div>

{{-- 開催日時（動的フォーム） --}}
@php
    // Prepare schedules data for Alpine x-data.
    // Accepts: old input (array), Eloquent Collection, or array of models/arrays.
    $schedulesSource = old('schedules', $event->schedules ?? []);

    // If it's a Collection (Eloquent), convert to array of attributes
    if ($schedulesSource instanceof \Illuminate\Support\Collection) {
        $schedulesSource = $schedulesSource->toArray();
    }

    // Normalize into array of simple arrays with formatted date/time strings
    $schedulesData = [];
    if (!empty($schedulesSource) && is_iterable($schedulesSource)) {
        foreach ($schedulesSource as $raw) {
            // $raw may be an array or an object (model)
            $row = is_array($raw) ? $raw : (array) $raw;

            $performance = $row['performance_date'] ?? null;
            // If performance_date is a Carbon/DateTime or string, try to parse and format
            $performanceFormatted = '';
            if (!empty($performance)) {
                // If already a date-only string (YYYY-MM-DD), keep as-is to avoid timezone shifts
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $performance)) {
                    $performanceFormatted = $performance;
                } else {
                    try {
                        // Parse and convert to Japan timezone to avoid UTC offset issues
                        $performanceFormatted = \Carbon\Carbon::parse($performance)->setTimezone('Asia/Tokyo')->format('Y-m-d');
                    } catch (\Exception $e) {
                        $performanceFormatted = (string) $performance;
                    }
                }
            }

            $doors = $row['doors_open_time'] ?? '';
            $start = $row['start_time'] ?? '';
            $end = $row['end_time'] ?? '';

            // Try to format time-only values into H:i when possible
            // Normalize times: if string already in HH:MM or HH:MM:SS, extract/format; otherwise parse and convert to Japan timezone
            if (!empty($doors)) {
                if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $doors)) {
                    $doors = substr($doors, 0, 5);
                } else {
                    try {
                        $doors = \Carbon\Carbon::parse($doors)->setTimezone('Asia/Tokyo')->format('H:i');
                    } catch (\Exception $e) {
                        // keep original
                    }
                }
            }
            if (!empty($start)) {
                if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $start)) {
                    $start = substr($start, 0, 5);
                } else {
                    try {
                        $start = \Carbon\Carbon::parse($start)->setTimezone('Asia/Tokyo')->format('H:i');
                    } catch (\Exception $e) {
                    }
                }
            }
            if (!empty($end)) {
                if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $end)) {
                    $end = substr($end, 0, 5);
                } else {
                    try {
                        $end = \Carbon\Carbon::parse($end)->setTimezone('Asia/Tokyo')->format('H:i');
                    } catch (\Exception $e) {
                    }
                }
            }

            $schedulesData[] = [
                'session_name' => $row['session_name'] ?? '',
                'performance_date' => $performanceFormatted,
                'doors_open_time' => $doors,
                'start_time' => $start,
                'end_time' => $end,
            ];
        }
    }

    if (empty($schedulesData)) {
        $schedulesData = [['session_name' => '', 'performance_date' => '', 'doors_open_time' => '', 'start_time' => '', 'end_time' => '']];
    }
@endphp
<div class="mb-8" x-data='{"schedules": @json($schedulesData)}'>
    <h3 class="text-lg font-semibold border-b mb-4 pb-2">開催日時</h3>
    <template x-for="(schedule, index) in schedules" :key="index">
        <div class="mb-4 p-4 border rounded-md">
            <div class="flex justify-between items-center mb-4">
                <div class="w-full mr-4">
                    <label class="block font-medium text-sm text-gray-700">公演の名称（例: 昼の部）</label>
                    <x-text-input class="block mt-1 w-full" type="text" x-model="schedule.session_name"
                        x-bind:name="'schedules[' + index + '][session_name]'" />
                </div>
                <div class="pt-6">
                    <button type="button" @click="schedules.splice(index, 1)"
                        class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 whitespace-nowrap"
                        x-show="schedules.length > 1">削除</button>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block font-medium text-sm text-gray-700">公演日 <span
                            class="text-red-500">*</span></label>
                    <x-text-input class="block mt-1 w-full" type="date" x-model="schedule.performance_date"
                        x-bind:name="'schedules[' + index + '][performance_date]'" required />
                </div>
                <div>
                    <label class="block font-medium text-sm text-gray-700">開場時間</label>
                    <x-text-input class="block mt-1 w-full" type="time" x-model="schedule.doors_open_time"
                        x-bind:name="'schedules[' + index + '][doors_open_time]'" />
                </div>
                <div>
                    <label class="block font-medium text-sm text-gray-700">開演時間</label>
                    <x-text-input class="block mt-1 w-full" type="time" x-model="schedule.start_time"
                        x-bind:name="'schedules[' + index + '][start_time]'" />
                </div>
                <div>
                    <label class="block font-medium text-sm text-gray-700">終演時間</label>
                    <x-text-input class="block mt-1 w-full" type="time" x-model="schedule.end_time"
                        x-bind:name="'schedules[' + index + '][end_time]'" />
                </div>
            </div>
        </div>
    </template>
    <button type="button"
        @click="schedules.push({ session_name: '', performance_date: '', doors_open_time: '', start_time: '', end_time: '' })"
        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
        <i class="fa-solid fa-plus mr-2"></i> 開催日程を追加
    </button>
</div>

{{-- チケット販売情報（動的フォーム） --}}
@php
    // Normalize ticket sales data similar to schedules: accept old input, Collection, JSON string, or array.
    $ticketSalesSource = old('ticket_sales', $event->ticketSales ?? []);

    if ($ticketSalesSource instanceof \Illuminate\Support\Collection) {
        $ticketSalesSource = $ticketSalesSource->toArray();
    }

    // If it is a JSON string (e.g., old input serialized), try to decode
    if (is_string($ticketSalesSource)) {
        $decoded = json_decode($ticketSalesSource, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $ticketSalesSource = $decoded;
        }
    }

    $ticketSalesData = [];
    if (!empty($ticketSalesSource) && is_iterable($ticketSalesSource)) {
        foreach ($ticketSalesSource as $raw) {
            $row = is_array($raw) ? $raw : (array) $raw;

            // Helper to normalize datetime for datetime-local input (Y-m-d\TH:i)
            $normalizeDatetimeLocal = function ($val) {
                if (empty($val))
                    return '';
                // If already in YYYY-MM-DDTHH:MM format, keep
                if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $val)) {
                    return $val;
                }
                // If contains space separator like 'YYYY-MM-DD HH:MM', convert
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $val)) {
                    return str_replace(' ', 'T', substr($val, 0, 16));
                }
                try {
                    return \Carbon\Carbon::parse($val)->setTimezone('Asia/Tokyo')->format('Y-m-d\\TH:i');
                } catch (\Exception $e) {
                    return (string) $val;
                }
            };

            $ticketSalesData[] = [
                'sale_method_name' => $row['sale_method_name'] ?? '',
                'app_starts_at' => $normalizeDatetimeLocal($row['app_starts_at'] ?? ''),
                'app_ends_at' => $normalizeDatetimeLocal($row['app_ends_at'] ?? ''),
                'results_at' => $normalizeDatetimeLocal($row['results_at'] ?? ''),
                'payment_starts_at' => $normalizeDatetimeLocal($row['payment_starts_at'] ?? ''),
                'payment_ends_at' => $normalizeDatetimeLocal($row['payment_ends_at'] ?? ''),
                'notes' => $row['notes'] ?? '',
            ];
        }
    }

    if (empty($ticketSalesData)) {
        $ticketSalesData = [];
    }
@endphp
<div class="mb-8" x-data='{"ticket_sales": @json($ticketSalesData)}'>
    <h3 class="text-lg font-semibold border-b mb-4 pb-2">チケット販売情報</h3>
    <template x-for="(sale, index) in ticket_sales" :key="index">
        <div class="mb-4 p-4 border rounded-md">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                <div>
                    <label class="block font-medium text-sm text-gray-700">販売手法の名称 <span
                            class="text-red-500">*</span></label>
                    <x-text-input type="text" class="block mt-1 w-full" x-model="sale.sale_method_name"
                        x-bind:name="'ticket_sales[' + index + '][sale_method_name]'" required />
                </div>
                <div class="flex items-end justify-end">
                    <button type="button" @click="ticket_sales.splice(index, 1)"
                        class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">削除</button>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div><x-input-label>申込受付(開始)</x-input-label><x-text-input type="datetime-local"
                        class="block mt-1 w-full" x-model="sale.app_starts_at"
                        x-bind:name="'ticket_sales[' + index + '][app_starts_at]'" /></div>
                <div><x-input-label>申込受付(終了)</x-input-label><x-text-input type="datetime-local"
                        class="block mt-1 w-full" x-model="sale.app_ends_at"
                        x-bind:name="'ticket_sales[' + index + '][app_ends_at]'" /></div>
                <div><x-input-label>支払手続(開始)</x-input-label><x-text-input type="datetime-local"
                        class="block mt-1 w-full" x-model="sale.payment_starts_at"
                        x-bind:name="'ticket_sales[' + index + '][payment_starts_at]'" /></div>
                <div><x-input-label>支払手続(終了)</x-input-label><x-text-input type="datetime-local"
                        class="block mt-1 w-full" x-model="sale.payment_ends_at"
                        x-bind:name="'ticket_sales[' + index + '][payment_ends_at]'" /></div>
                <div><x-input-label>抽選結果発表</x-input-label><x-text-input type="datetime-local" class="block mt-1 w-full"
                        x-model="sale.results_at" x-bind:name="'ticket_sales[' + index + '][results_at]'" /></div>

            </div>
            <div class="mt-4">
                <x-input-label>注意事項</x-input-label>
                <textarea rows="3"
                    class="rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block mt-1 w-full"
                    x-model="sale.notes" x-bind:name="'ticket_sales[' + index + '][notes]'"></textarea>
            </div>
        </div>
    </template>
    <button type="button" @click="ticket_sales.push({})"
        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
        <i class="fa-solid fa-plus mr-2"></i> チケット販売情報を追加
    </button>
</div>