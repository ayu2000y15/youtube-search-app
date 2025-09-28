<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Space extends Model
{
    use HasFactory;

    /**
     * マスアサインメント可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'visibility',
        'invite_token',
    ];

    /**
     * このスペースを所有するユーザーを取得
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * このスペースに属するチャンネルを取得
     */
    public function channels(): HasMany
    {
        return $this->hasMany(Channel::class);
    }

    /**
     * このスペースに属する再生リストを取得
     */
    public function playlists(): HasMany
    {
        return $this->hasMany(Playlist::class);
    }

    /**
     * このスペースに属するカテゴリを取得
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * このスペースに属する動画を取得
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    /**
     * ゲスト用の公開URLを取得
     */
    public function getGuestUrl(): ?string
    {
        if ($this->visibility === 2 && $this->slug) {
            // 全体公開の場合は/{slug}
            return route('guest.space.public', $this->slug);
        } elseif ($this->visibility === 1 && $this->slug && $this->invite_token) {
            // 限定公開の場合は/{slug}/{invite_token}
            return route('guest.space.invite', [$this->slug, $this->invite_token]);
        }

        return null;
    }

    /**
     * 招待トークンを生成
     */
    public function generateInviteToken(): string
    {
        do {
            $token = Str::random(32);
        } while (self::where('invite_token', $token)->exists());

        $this->invite_token = $token;
        $this->save();

        return $token;
    }

    /**
     * 公開範囲のラベルを取得
     */
    public function getVisibilityLabel(): string
    {
        return match ($this->visibility) {
            0 => '自分のみ',
            1 => '限定公開',
            2 => '全体公開',
            default => '不明',
        };
    }
}
