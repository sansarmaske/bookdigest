<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3, false),
            'author' => $this->faker->name(),
            'description' => $this->faker->optional(0.7)->paragraph(3),
            'publication_year' => $this->faker->optional(0.8)->numberBetween(1800, date('Y')),
            'genre' => $this->faker->optional(0.6)->randomElement([
                'Fiction',
                'Non-Fiction',
                'Mystery',
                'Science Fiction',
                'Fantasy',
                'Romance',
                'Thriller',
                'Biography',
                'History',
                'Self-Help',
                'Poetry',
                'Drama',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function classic(): static
    {
        return $this->state(fn (array $attributes) => [
            'publication_year' => $this->faker->numberBetween(1800, 1950),
        ]);
    }

    public function modern(): static
    {
        return $this->state(fn (array $attributes) => [
            'publication_year' => $this->faker->numberBetween(1951, date('Y')),
        ]);
    }

    public function withoutDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => null,
        ]);
    }

    public function fiction(): static
    {
        return $this->state(fn (array $attributes) => [
            'genre' => 'Fiction',
        ]);
    }

    public function withGenre(string $genre): static
    {
        return $this->state(fn (array $attributes) => [
            'genre' => $genre,
        ]);
    }
}
