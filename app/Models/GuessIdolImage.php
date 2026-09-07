<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuessIdolImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'member_id',
        'difficulty',
        'image',
    ];
}
