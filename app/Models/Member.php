<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'name',
        'about',
        'traits',
        'description',
        'image',
    ];

    protected $casts = [
        'traits' => 'array',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
