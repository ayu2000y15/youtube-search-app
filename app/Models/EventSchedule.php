<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSchedule extends Model
{
    use HasFactory;

    /**
     * Mass Assignment（一括代入）を許可するカラム
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'session_name',
        'performance_date',
        'doors_open_time',
        'start_time',
        'end_time',
    ];

    /**
     * 型を変換する属性
     *
     * @var array
     */
    protected $casts = [
        'performance_date' => 'date', // date型にキャスト
    ];

    /**
     * この開催日時が属するイベントを取得
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
