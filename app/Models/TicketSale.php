<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketSale extends Model
{
    use HasFactory;

    /**
     * Mass Assignment（一括代入）を許可するカラム
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'sale_method_name',
        'app_starts_at',
        'app_ends_at',
        'results_at',
        'payment_starts_at',
        'payment_ends_at',
        'notes',
    ];

    /**
     * 日付として扱うカラム
     *
     * @var array
     */
    protected $casts = [
        'app_starts_at' => 'datetime',
        'app_ends_at' => 'datetime',
        'results_at' => 'datetime',
        'payment_starts_at' => 'datetime',
        'payment_ends_at' => 'datetime',
    ];

    /**
     * このチケット販売情報が属するイベントを取得
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
