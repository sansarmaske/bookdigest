<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'description',
        'publication_year',
        'genre',
    ];

    protected $casts = [
        'publication_year' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'display_name',
        'is_classic',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_books')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    public function displayName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->title} by {$this->author}"
        );
    }

    public function isClassic(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->publication_year && $this->publication_year <= 1950
        );
    }

    public function shortDescription(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->description ? Str::limit($this->description, 100) : null
        );
    }

    public function scopeByAuthor(Builder $query, string $author): Builder
    {
        return $query->where('author', 'like', "%{$author}%");
    }

    public function scopeByGenre(Builder $query, string $genre): Builder
    {
        return $query->where('genre', 'like', "%{$genre}%");
    }

    public function scopeByYear(Builder $query, int $year): Builder
    {
        return $query->where('publication_year', $year);
    }

    public function scopeClassics(Builder $query): Builder
    {
        return $query->where('publication_year', '<=', 1950);
    }

    public function scopeModern(Builder $query): Builder
    {
        return $query->where('publication_year', '>', 1950);
    }

    public function scopeWithValidData(Builder $query): Builder
    {
        return $query->whereNotNull('title')
            ->whereNotNull('author')
            ->where('title', '!=', '')
            ->where('author', '!=', '');
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('author', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('genre', 'like', "%{$search}%");
        });
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function toArray(): array
    {
        $array = parent::toArray();

        // Remove sensitive or unnecessary data from API responses if needed
        return $array;
    }

    protected static function boot(): void
    {
        parent::boot();

        // Ensure title and author are always trimmed
        static::creating(function ($book) {
            $book->title = trim($book->title);
            $book->author = trim($book->author);
            if ($book->description) {
                $book->description = trim($book->description);
            }
            if ($book->genre) {
                $book->genre = trim($book->genre);
            }
        });

        static::updating(function ($book) {
            $book->title = trim($book->title);
            $book->author = trim($book->author);
            if ($book->description) {
                $book->description = trim($book->description);
            }
            if ($book->genre) {
                $book->genre = trim($book->genre);
            }
        });
    }
}
