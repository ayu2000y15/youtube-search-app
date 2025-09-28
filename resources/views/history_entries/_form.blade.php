{{-- resources/views/history_entries/_form.blade.php --}}

@csrf

{{-- バリデーションエラーの表示 --}}
@if ($errors->any())
    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="space-y-6">
    {{-- 縦軸とカテゴリ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="vertical_axis_id" class="block font-medium text-sm text-gray-700">縦軸 <span
                    class="text-red-500">*</span></label>
            <select id="vertical_axis_id" name="vertical_axis_id"
                class="mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                required>
                @foreach($verticalAxes as $id => $label)
                    <option value="{{ $id }}" @selected(old('vertical_axis_id', $entry->vertical_axis_id ?? '') == $id)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="history_category_id" class="block font-medium text-sm text-gray-700">カテゴリ <span
                    class="text-red-500">*</span></label>
            <select id="history_category_id" name="history_category_id"
                class="mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                required>
                @foreach($categories as $id => $name)
                    <option value="{{ $id }}" @selected(old('history_category_id', $entry->history_category_id ?? '') == $id)>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- 軸の型と値 --}}
    <div x-data="{ axisType: '{{ old('axis_type', $entry->axis_type ?? '') }}' }">
        <div>
            <label class="block font-medium text-sm text-gray-700">軸の型</label>
            <div class="mt-2 space-x-4">
                <label class="inline-flex items-center">
                    <input type="radio" x-model="axisType" name="axis_type" value="date"
                        class="text-indigo-600 border-gray-300 focus:ring-indigo-500">
                    <span class="ml-2">リアル日付</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" x-model="axisType" name="axis_type" value="custom"
                        class="text-indigo-600 border-gray-300 focus:ring-indigo-500">
                    <span class="ml-2">カスタム値</span>
                </label>
            </div>
        </div>

        <div x-show="axisType === 'date'" class="mt-4">
            <x-input-label for="axis_date" :value="__('日付')" />
            <x-text-input id="axis_date" name="axis_date" type="datetime-local" class="mt-1 block w-full"
                :value="old('axis_date', $entry->axis_date ?? '')" />
        </div>

        <div x-show="axisType === 'custom'" class="mt-4">
            <x-input-label for="axis_custom_value" :value="__('カスタム値（例: 幼少期, 15歳）')" />
            <x-text-input id="axis_custom_value" name="axis_custom_value" type="text" class="mt-1 block w-full"
                :value="old('axis_custom_value', $entry->axis_custom_value ?? '')" />
        </div>
    </div>

    {{-- タイトルとキャラクター名 --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="title" class="block font-medium text-sm text-gray-700">タイトル <span
                    class="text-red-500">*</span></label>
            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $entry->title ?? '')" required />
        </div>
        <div>
            <x-input-label for="character_name" :value="__('キャラクター名')" />
            <x-text-input id="character_name" name="character_name" type="text" class="mt-1 block w-full"
                :value="old('character_name', $entry->character_name ?? '')" />
        </div>
    </div>

    {{-- 内容とメモ --}}
    <div>
        <x-input-label for="content" :value="__('内容')" />
        <textarea id="content" name="content" rows="5"
            class="mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('content', $entry->content ?? '') }}</textarea>
    </div>
    <div>
        <x-input-label for="memo" :value="__('メモ')" />
        <textarea id="memo" name="memo" rows="3"
            class="mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('memo', $entry->memo ?? '') }}</textarea>
    </div>

    {{-- 関連URL --}}
    <div x-data="{ urls: {{ json_encode(old('related_urls', $entry->related_urls ?? [])) }} }">
        <h3 class="text-lg font-semibold border-b pb-2">関連URL</h3>
        <div class="mt-4 space-y-4">
            <template x-for="(url, index) in urls" :key="index">
                <div class="flex items-center space-x-2">
                    <x-text-input type="text" x-model="url.label" ::name="'related_urls[' + index + '][label]'"
                        placeholder="ラベル" class="w-1/3" />
                    <x-text-input type="url" x-model="url.url" ::name="'related_urls[' + index + '][url]'"
                        placeholder="https://..." class="flex-grow" />
                    <button type="button" @click="urls.splice(index, 1)"
                        class="px-3 py-2 bg-red-500 text-white rounded-md hover:bg-red-600" title="削除">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </template>
        </div>
        <button type="button" @click="urls.push({ label: '', url: '' })"
            class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
            ＋ URLを追加
        </button>
    </div>

    {{-- 関連動画 --}}
    <div>
        <x-input-label for="videos" :value="__('関連動画（複数選択可）')" />
        <select id="videos" name="videos[]" multiple
            class="mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 h-40">
            @foreach($videos as $id => $title)
                <option value="{{ $id }}" @selected(in_array($id, old('videos', $entry->videos->pluck('id')->all() ?? [])))>
                    {{ $title }}
                </option>
            @endforeach
        </select>
    </div>
</div>