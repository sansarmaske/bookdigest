<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'title',
        'author',
        'description',
        'publication_year',
        'genre',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_books')
                    ->withPivot('read_at')
                    ->withTimestamps();
    }
}
