<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'debut_date',
        'concept',
        'about',
        'description',
        'image',
    ];

    public function members()
    {
        return $this->hasMany(Member::class);
    }

    public function albums()
    {
        return $this->hasMany(Album::class);
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }
}
