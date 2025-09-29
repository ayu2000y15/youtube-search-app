<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'color' => 'required|string|size:7|starts_with:#', // #xxxxxx形式
            'display_order' => 'nullable|integer',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'カテゴリ名',
            'color' => 'カラーコード',
            'display_order' => '表示順',
        ];
    }
}
