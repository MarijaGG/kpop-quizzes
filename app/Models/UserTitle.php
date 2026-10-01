<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTitle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title_id',
        'awarded_at',
    ];

    protected function casts(): array
    {
        return [
            'awarded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    public function getTitleKeyAttribute(): ?string
    {
        return $this->title?->title_key;
    }

    public function getTitleLabelAttribute(): ?string
    {
        return $this->title?->title_label;
    }
}