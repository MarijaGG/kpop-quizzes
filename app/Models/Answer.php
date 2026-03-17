<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'text',
        'points',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
