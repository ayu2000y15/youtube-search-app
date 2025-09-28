<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dialogue extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_id',
        'timestamp',
        'speaker',
        'dialogue',
    ];

    /**
     * この文字起こしが属する動画を取得
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
