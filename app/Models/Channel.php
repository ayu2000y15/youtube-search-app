<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'youtube_channel_id',
        'name',
    ];

    /**
     * このチャンネルが属するスペースを取得
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * このチャンネルに属する動画を取得
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }
}
