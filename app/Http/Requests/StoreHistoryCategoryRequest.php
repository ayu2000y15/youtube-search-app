<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHistoryCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'display_order' => 'nullable|integer',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'カテゴリ名',
            'display_order' => '表示順',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => ':attributeは必ず入力してください。',
            'name.string' => ':attributeは文字列で入力してください。',
            'name.max' => ':attributeは255文字以内で入力してください。',
            'display_order.integer' => ':attributeは整数で入力してください。',
        ];
    }
}
