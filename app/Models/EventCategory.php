<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'display_order', 'color'];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
