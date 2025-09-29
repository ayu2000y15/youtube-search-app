<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    /**
     * Mass Assignment（一括代入）を許可するカラム
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'space_id',
        'event_category_id',
        'name',
        'venue',
        'performers',
        'price_info',
        'description',
        'event_url',
        'internal_memo',
    ];

    /**
     * このイベントが属するスペースを取得
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * このイベントに紐づく開催日時一覧を取得
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(EventSchedule::class);
    }

    /**
     * このイベントに紐づくチケット販売情報一覧を取得
     */
    public function ticketSales(): HasMany
    {
        return $this->hasMany(TicketSale::class);
    }

    /**
     * このイベントが属するカテゴリを取得
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }
}
