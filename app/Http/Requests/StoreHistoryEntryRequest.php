<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHistoryEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // リクエストは nested (timelines/{timeline}/history-entries) と
        // shallow (history-entries/{history_entry}) の両方で使われるため、
        // timeline がルートに無ければ history_entry から導出して安全に扱う
        $timeline = $this->route('timeline');
        if (!$timeline && $this->route('history_entry')) {
            $he = $this->route('history_entry');
            // history_entry 側に縦軸または年表が紐づいていない可能性があるため null 安全に
            $timeline = $he->verticalAxis?->timeline ?? null;
        }

        $space = $timeline?->space ?? null;
        $categoryIds = $space ? $space->historyCategories()->pluck('id')->all() : [];

        // vertical_axis の存在チェックに timeline があれば timeline_id 条件を付与する
        $verticalAxisRule = Rule::exists('vertical_axes', 'id');
        if ($timeline) {
            $verticalAxisRule = $verticalAxisRule->where('timeline_id', $timeline->id);
        }

        return [
            'vertical_axis_id' => array_merge(['required', 'integer'], [$verticalAxisRule]),
            'history_category_id' => ['required', 'integer', Rule::in($categoryIds)],
            'axis_type' => 'nullable|string|in:date,custom',
            'axis_date' => 'nullable|required_if:axis_type,date|date',
            'axis_custom_value' => 'nullable|required_if:axis_type,custom|string|max:255',
            'display_order' => 'nullable|integer',
            'character_name' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'related_urls' => 'nullable|array',
            'related_urls.*.label' => 'required_with:related_urls|string|max:255',
            'related_urls.*.url' => 'required_with:related_urls|url|max:2048',
            'memo' => 'nullable|string',
            'videos' => 'nullable|array',
            'videos.*' => 'integer|exists:videos,id',
        ];
    }

    public function attributes(): array
    {
        return [
            'vertical_axis_id' => '縦軸',
            'history_category_id' => 'カテゴリ',
            'axis_type' => '軸の型',
            'axis_date' => '日付',
            'axis_custom_value' => 'カスタム値',
            'character_name' => 'キャラクター名',
            'title' => 'タイトル',
            'content' => '内容',
            'related_urls' => '関連URL',
            'related_urls.*.label' => '関連URLのラベル',
            'related_urls.*.url' => '関連URL',
            'videos' => '関連動画',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attributeは必須項目です。',
            'required_if' => '軸の型が「:value」の場合、:attributeは必須です。',
            'required_with' => '関連URLを入力する場合、:attributeは必須です。',
            'integer' => ':attributeは整数で指定してください。',
            'string' => ':attributeは文字列で入力してください。',
            'url' => ':attributeは有効なURL形式で入力してください。',
            'in' => '選択された:attributeが無効です。',
            'exists' => '選択された:attributeが存在しません。',
        ];
    }
}
