<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'title',
        'release_date',
        'concept',
        'vibe',
        'concept_traits',
        'description',
        'image',
    ];

    protected $casts = [
        'vibe' => 'array',
        'concept_traits' => 'array',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
