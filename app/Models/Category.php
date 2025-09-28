<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'space_id',
        'name',
        'order_column',
    ];

    /**
     * このカテゴリが属するスペースを取得
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * このカテゴリが付けられた動画を取得
     */
    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'category_video');
    }
}
