<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function books()
    {
        return $this->belongsToMany(Book::class, 'user_books')
            ->withPivot('read_at')
            ->withTimestamps()
            ->orderByPivot('created_at', 'desc');
    }

    public function hasBook(Book $book): bool
    {
        return $this->books()->where('book_id', $book->id)->exists();
    }

    public function addBook(Book $book, ?\DateTimeInterface $readAt = null): void
    {
        if (! $this->hasBook($book)) {
            $this->books()->attach($book, [
                'read_at' => $readAt ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function removeBook(Book $book): bool
    {
        return $this->books()->detach($book) > 0;
    }

    public function getBookCount(): int
    {
        return $this->books()->count();
    }
}
