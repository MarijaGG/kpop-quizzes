<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'member_id',
        'name',
        'settings',
        'image',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
