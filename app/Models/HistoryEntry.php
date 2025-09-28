<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HistoryEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'vertical_axis_id',
        'history_category_id',
        'axis_type',
        'axis_date',
        'axis_custom_value',
        'display_order',
        'character_name',
        'title',
        'content',
        'related_urls',
        'memo',
    ];

    protected $casts = [
        'axis_date' => 'datetime',
        'related_urls' => 'array',
    ];

    /**
     * この項目が属する縦軸を取得
     */
    public function verticalAxis(): BelongsTo
    {
        return $this->belongsTo(VerticalAxis::class);
    }

    /**
     * この項目が属するカテゴリを取得
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(HistoryCategory::class, 'history_category_id');
    }

    /**
     * この項目に関連する動画一覧を取得 (多対多)
     */
    public function videos(): BelongsToMany
    {
        // 第2引数で中間テーブル名を指定
        return $this->belongsToMany(Video::class, 'history_entry_video');
    }
}
