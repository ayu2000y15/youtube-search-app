<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Timeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'name',
        'description',
        'horizontal_axis_label',
    ];

    /**
     * この年表が属するスペースを取得
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * この年表が持つ縦軸一覧を取得
     */
    public function verticalAxes(): HasMany
    {
        return $this->hasMany(VerticalAxis::class);
    }

    /**
     * この年表に属する全ての項目を縦軸経由で取得
     */
    public function historyEntries(): HasManyThrough
    {
        return $this->hasManyThrough(HistoryEntry::class, VerticalAxis::class);
    }
}
