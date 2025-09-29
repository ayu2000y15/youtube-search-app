@csrf

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

{{-- 年表の基本情報 --}}
<div class="space-y-6">
    <div>
        <x-input-label for="name" :value="__('年表タイトル')" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $timeline->name ?? '')" required autofocus />
    </div>
    <div>
        <x-input-label for="description" :value="__('年表の説明')" />
        <textarea id="description" name="description" rows="3"
            class="mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('description', $timeline->description ?? '') }}</textarea>
    </div>
    <div>
        <x-input-label for="horizontal_axis_label" :value="__('横軸のラベル（例: 年代、日付）')" />
        <x-text-input id="horizontal_axis_label" name="horizontal_axis_label" type="text" class="mt-1 block w-full"
            :value="old('horizontal_axis_label', $timeline->horizontal_axis_label ?? '')" />
    </div>
</div>

{{-- 縦軸の動的フォーム (ドラッグ＆ドロップ対応) --}}
{{-- 縦軸の動的フォーム (デバッグコード) --}}
<div class="mt-8 border-t pt-6" x-data='{
        "verticalAxes": @json(old("vertical_axes", isset($timeline) ? $timeline->verticalAxes->sortBy("display_order")->values() : [['label' => '', 'id' => null]])),
        "nextId": 1
    }' x-init="
        console.log('1. 初期化直後のデータ:', JSON.parse(JSON.stringify(verticalAxes)));

        verticalAxes.forEach(axis => {
            if (!axis.id) {
                axis.temp_id = nextId++;
            }
        });

        console.log('2. temp_id割り当て後のデータ:', JSON.parse(JSON.stringify(verticalAxes)));
    ">
    <h3 class="text-lg font-semibold mb-4">縦軸の定義</h3>
    <div class="space-y-4" x-sortable x-model="verticalAxes">
        <template x-for="(axis, index) in verticalAxes" :key="axis.id || 'new-' + axis.temp_id">
            <div class="flex items-center space-x-2 bg-gray-50 p-2 rounded-md" x-sortable-item>
                <div x-sortable-handle class="cursor-move text-gray-400 hover:text-gray-600 pr-2" title="並び替え">
                    <i class="fa-solid fa-grip-vertical"></i>
                </div>
                <div class="flex-grow">
                    <x-input-label ::for="'axis_label_' + (axis.id || axis.temp_id)">ラベル</x-input-label>
                    <x-text-input ::id="'axis_label_' + (axis.id || axis.temp_id)" x-model="axis.label"
                        ::name="'vertical_axes[' + index + '][label]'" type="text" class="mt-1 block w-full"
                        placeholder="例: リリース、ライブ活動" required />
                    <input type="hidden" :name="'vertical_axes[' + index + '][id]'" x-model="axis.id">
                    <input type="hidden" :name="'vertical_axes[' + index + '][display_order]'" :value="index">
                </div>
                <div class="pt-5">
                    <button type="button" @click="verticalAxes.splice(index, 1)" x-show="verticalAxes.length > 1"
                        class="px-3 py-2 bg-red-500 text-white rounded-md hover:bg-red-600" title="削除">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        </template>
    </div>
    <button type="button" @click="verticalAxes.push({ id: null, label: '', temp_id: nextId++ })"
        class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
        <i class="fa-solid fa-plus"></i> 縦軸を追加
    </button>
</div>