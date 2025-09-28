<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VerticalAxis extends Model
{
    use HasFactory;

    protected $fillable = [
        'timeline_id',
        'label',
        'display_order',
    ];

    // 明示的にテーブル名を指定（Eloquent の規約に従っているため必須ではないが明示的にする）
    protected $table = 'vertical_axes';

    /**
     * この縦軸が属する年表を取得
     */
    public function timeline(): BelongsTo
    {
        return $this->belongsTo(Timeline::class);
    }

    /**
     * この縦軸に属する項目一覧を取得
     */
    public function historyEntries(): HasMany
    {
        return $this->hasMany(HistoryEntry::class);
    }
}
