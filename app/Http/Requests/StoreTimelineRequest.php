<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'horizontal_axis_label' => 'nullable|string|max:255',
            'vertical_axes' => 'required|array|min:1',
            'vertical_axes.*.id' => 'nullable|integer|exists:vertical_axes,id',
            'vertical_axes.*.label' => 'required|string|max:255',
            'vertical_axes.*.display_order' => 'nullable|integer',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '年表タイトル',
            'description' => '年表の説明',
            'horizontal_axis_label' => '横軸のラベル',
            'vertical_axes' => '縦軸',
            'vertical_axes.*.label' => '縦軸のラベル',
            'vertical_axes.*.display_order' => '縦軸の表示順',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => ':attributeは必ず入力してください。',
            'vertical_axes.required' => ':attributeは少なくとも1つは設定してください。',
            'vertical_axes.*.label.required' => ':attributeは必須です。',
        ];
    }
}
