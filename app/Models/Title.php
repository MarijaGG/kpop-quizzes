<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Title extends Model
{
    use HasFactory;

    protected $fillable = [
        'title_key',
        'title_label',
    ];

    public function userAwards(): HasMany
    {
        return $this->hasMany(UserTitle::class);
    }
}
