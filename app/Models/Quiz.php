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

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
