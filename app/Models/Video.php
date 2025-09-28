<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'channel_id',
        'youtube_video_id',
        'title',
        'thumbnail_url',
        'published_at',
        'video_type',
        'view_count',
        'like_count',
        'comment_count',
        'description',
        'tags',
        'category_id',
        'language',
        'statistics_updated_at',
    ];

    /**
     * published_atをdatetimeオブジェクトとして扱う
     */
    protected $casts = [
        'published_at' => 'datetime',
        'statistics_updated_at' => 'datetime',
        'tags' => 'array',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'comment_count' => 'integer',
    ];

    /**
     * この動画が属するスペースを取得
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * この動画が属するチャンネルを取得
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * この動画に紐づく文字起こしを取得
     */
    public function dialogues(): HasMany
    {
        return $this->hasMany(Dialogue::class);
    }

    /**
     * この動画が属する再生リストを取得
     */
    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(Playlist::class, 'playlist_video');
    }

    /**
     * この動画に付けられたカテゴリを取得
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_video');
    }
}
