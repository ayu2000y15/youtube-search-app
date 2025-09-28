<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // ここで認可処理を実装できますが、今回はシンプルにtrueにします
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            // イベント基本情報
            'name' => 'required|string|max:255',
            'venue' => 'nullable|string|max:255',
            'performers' => 'nullable|string',
            'price_info' => 'nullable|string',
            'description' => 'nullable|string',
            'event_url' => 'nullable|url|max:2048',
            'internal_memo' => 'nullable|string',

            // 開催日時 (配列としてバリデーション)
            'schedules' => 'required|array|min:1',
            'schedules.*.session_name' => 'nullable|string|max:255',
            'schedules.*.performance_date' => 'required|date',
            'schedules.*.doors_open_time' => 'nullable|date_format:H:i:s,H:i',
            'schedules.*.start_time' => 'nullable|date_format:H:i:s,H:i',
            'schedules.*.end_time' => 'nullable|date_format:H:i:s,H:i|after:schedules.*.start_time',


            // チケット販売情報 (配列としてバリデーション)
            'ticket_sales' => 'nullable|array',
            'ticket_sales.*.sale_method_name' => 'required_with:ticket_sales|string|max:255',
            'ticket_sales.*.app_starts_at' => 'nullable|date',
            'ticket_sales.*.app_ends_at' => 'nullable|date|after_or_equal:ticket_sales.*.app_starts_at',
            'ticket_sales.*.results_at' => 'nullable|date',
            'ticket_sales.*.payment_starts_at' => 'nullable|date',
            'ticket_sales.*.payment_ends_at' => 'nullable|date|after_or_equal:ticket_sales.*.payment_starts_at',
            'ticket_sales.*.notes' => 'nullable|string',
        ];
    }

    /**
     * バリデーションエラーメッセージを日本語で定義
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            // '項目名.ルール名' => 'メッセージ' の形式で記述
            'name.required' => 'イベント名は必ず入力してください。',
            'name.max' => 'イベント名は255文字以内で入力してください。',
            'event_url.url' => 'イベントURLは正しい形式で入力してください。',
            'schedules.required' => '開催日時は少なくとも1つは設定してください。',
            'schedules.*.scheduled_at.required' => '開催日時は必須です。',
        ];
    }

    /**
     * バリデーションの属性名を日本語で定義
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'name' => 'イベント名',
            'venue' => '会場',
            'performers' => '出演',
            'price_info' => '料金',
            'description' => '内容',
            'event_url' => 'イベントURL',
            // 開催日時
            'schedules.*.performance_date' => '公演日',
            'schedules.*.doors_open_time' => '開場時間',
            'schedules.*.start_time' => '開演時間',
            'schedules.*.end_time' => '終演時間',

            // チケット販売情報
            'ticket_sales' => 'チケット販売情報',
            'ticket_sales.*.sale_method_name' => '販売手法の名称',
            'ticket_sales.*.app_starts_at' => '申込受付期間 (開始)',
            'ticket_sales.*.app_ends_at' => '申込受付期間 (終了)',


        ];
    }
}
