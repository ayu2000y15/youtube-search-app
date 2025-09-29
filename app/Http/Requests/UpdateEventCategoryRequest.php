<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 自分自身の名前は重複チェックから除外する
            'name' => ['required', 'string', 'max:255', Rule::unique('event_categories')->ignore($this->event_category)],
            'color' => 'required|string|size:7|starts_with:#',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'カテゴリ名',
            'color' => 'カラーコード',
        ];
    }
}
