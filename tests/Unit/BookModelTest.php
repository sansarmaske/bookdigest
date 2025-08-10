<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_attributes(): void
    {
        $book = new Book;

        $expected = [
            'title',
            'author',
            'description',
            'publication_year',
            'genre',
        ];

        $this->assertEquals($expected, $book->getFillable());
    }

    public function test_casts_attributes_correctly(): void
    {
        $book = Book::factory()->create([
            'publication_year' => '2020',
        ]);

        $this->assertIsInt($book->publication_year);
        $this->assertInstanceOf(\Carbon\Carbon::class, $book->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $book->updated_at);
    }

    public function test_users_relationship(): void
    {
        $book = Book::factory()->create();
        $user = User::factory()->create();

        $user->addBook($book);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $book->users());
        $this->assertTrue($book->users->contains($user));
    }

    public function test_display_name_attribute(): void
    {
        $book = Book::factory()->create([
            'title' => 'Test Book',
            'author' => 'Test Author',
        ]);

        $this->assertEquals('Test Book by Test Author', $book->display_name);
    }

    public function test_is_classic_attribute(): void
    {
        $classicBook = Book::factory()->create(['publication_year' => 1945]);
        $modernBook = Book::factory()->create(['publication_year' => 1960]);
        $noYearBook = Book::factory()->create(['publication_year' => null]);

        $this->assertTrue($classicBook->is_classic);
        $this->assertFalse($modernBook->is_classic);
        $this->assertFalse($noYearBook->is_classic);
    }

    public function test_short_description_attribute(): void
    {
        $longDescription = str_repeat('A', 150);
        $bookWithLongDescription = Book::factory()->create(['description' => $longDescription]);
        $bookWithShortDescription = Book::factory()->create(['description' => 'Short desc']);
        $bookWithoutDescription = Book::factory()->create(['description' => null]);

        $this->assertLessThanOrEqual(103, strlen($bookWithLongDescription->short_description)); // 100 + "..."
        $this->assertEquals('Short desc', $bookWithShortDescription->short_description);
        $this->assertNull($bookWithoutDescription->short_description);
    }

    public function test_by_author_scope(): void
    {
        Book::factory()->create(['author' => 'John Smith']);
        Book::factory()->create(['author' => 'Jane Doe']);
        Book::factory()->create(['author' => 'John Doe']);

        $johnBooks = Book::byAuthor('John')->get();
        $smithBooks = Book::byAuthor('Smith')->get();

        $this->assertCount(2, $johnBooks);
        $this->assertCount(1, $smithBooks);
    }

    public function test_by_genre_scope(): void
    {
        Book::factory()->create(['genre' => 'Science Fiction']);
        Book::factory()->create(['genre' => 'Fantasy Fiction']);
        Book::factory()->create(['genre' => 'Horror']);

        $fictionBooks = Book::byGenre('Fiction')->get();
        $scienceBooks = Book::byGenre('Science')->get();

        $this->assertCount(2, $fictionBooks);
        $this->assertCount(1, $scienceBooks);
    }

    public function test_by_year_scope(): void
    {
        Book::factory()->create(['publication_year' => 2020]);
        Book::factory()->create(['publication_year' => 2021]);
        Book::factory()->create(['publication_year' => 2020]);

        $books2020 = Book::byYear(2020)->get();
        $books2021 = Book::byYear(2021)->get();

        $this->assertCount(2, $books2020);
        $this->assertCount(1, $books2021);
    }

    public function test_classics_scope(): void
    {
        Book::factory()->create(['publication_year' => 1945]);
        Book::factory()->create(['publication_year' => 1950]);
        Book::factory()->create(['publication_year' => 1951]);
        Book::factory()->create(['publication_year' => 2000]);

        $classics = Book::classics()->get();

        $this->assertCount(2, $classics);
    }

    public function test_modern_scope(): void
    {
        Book::factory()->create(['publication_year' => 1945]);
        Book::factory()->create(['publication_year' => 1950]);
        Book::factory()->create(['publication_year' => 1951]);
        Book::factory()->create(['publication_year' => 2000]);

        $modern = Book::modern()->get();

        $this->assertCount(2, $modern);
    }

    public function test_with_valid_data_scope(): void
    {
        Book::factory()->create(['title' => 'Valid Title', 'author' => 'Valid Author']);
        Book::factory()->create(['title' => '', 'author' => 'Valid Author']);
        Book::factory()->create(['title' => 'Valid Title', 'author' => '']);
        Book::factory()->create(['title' => null, 'author' => 'Valid Author']);

        $validBooks = Book::withValidData()->get();

        $this->assertCount(1, $validBooks);
        $this->assertEquals('Valid Title', $validBooks->first()->title);
    }

    public function test_search_scope(): void
    {
        Book::factory()->create([
            'title' => 'The Great Gatsby',
            'author' => 'F. Scott Fitzgerald',
            'description' => 'A classic American novel',
            'genre' => 'Fiction',
        ]);

        Book::factory()->create([
            'title' => '1984',
            'author' => 'George Orwell',
            'description' => 'Dystopian social science fiction',
            'genre' => 'Science Fiction',
        ]);

        $gatsbyResults = Book::search('Gatsby')->get();
        $fictionResults = Book::search('Fiction')->get();
        $authorResults = Book::search('Orwell')->get();
        $descriptionResults = Book::search('Dystopian')->get();

        $this->assertCount(1, $gatsbyResults);
        $this->assertCount(2, $fictionResults); // Both books match
        $this->assertCount(1, $authorResults);
        $this->assertCount(1, $descriptionResults);
    }

    public function test_route_key_name(): void
    {
        $book = new Book;

        $this->assertEquals('id', $book->getRouteKeyName());
    }

    public function test_boot_trims_whitespace_on_create(): void
    {
        $book = Book::create([
            'title' => '  Trimmed Title  ',
            'author' => '  Trimmed Author  ',
            'description' => '  Trimmed Description  ',
            'genre' => '  Trimmed Genre  ',
        ]);

        $this->assertEquals('Trimmed Title', $book->title);
        $this->assertEquals('Trimmed Author', $book->author);
        $this->assertEquals('Trimmed Description', $book->description);
        $this->assertEquals('Trimmed Genre', $book->genre);
    }

    public function test_boot_trims_whitespace_on_update(): void
    {
        $book = Book::factory()->create();

        $book->update([
            'title' => '  Updated Title  ',
            'author' => '  Updated Author  ',
            'description' => '  Updated Description  ',
            'genre' => '  Updated Genre  ',
        ]);

        $this->assertEquals('Updated Title', $book->title);
        $this->assertEquals('Updated Author', $book->author);
        $this->assertEquals('Updated Description', $book->description);
        $this->assertEquals('Updated Genre', $book->genre);
    }

    public function test_boot_handles_null_values(): void
    {
        $book = Book::create([
            'title' => 'Valid Title',
            'author' => 'Valid Author',
            'description' => null,
            'genre' => null,
        ]);

        $this->assertEquals('Valid Title', $book->title);
        $this->assertEquals('Valid Author', $book->author);
        $this->assertNull($book->description);
        $this->assertNull($book->genre);
    }

    public function test_appended_attributes_in_array(): void
    {
        $book = Book::factory()->create([
            'title' => 'Test Book',
            'author' => 'Test Author',
            'publication_year' => 1945,
        ]);

        $array = $book->toArray();

        $this->assertArrayHasKey('display_name', $array);
        $this->assertArrayHasKey('is_classic', $array);
        $this->assertEquals('Test Book by Test Author', $array['display_name']);
        $this->assertTrue($array['is_classic']);
    }

    public function test_factory_creates_valid_book(): void
    {
        $book = Book::factory()->create();

        $this->assertNotEmpty($book->title);
        $this->assertNotEmpty($book->author);
        $this->assertInstanceOf(Book::class, $book);
        $this->assertTrue($book->exists);
    }

    public function test_factory_classic_state(): void
    {
        $book = Book::factory()->classic()->create();

        $this->assertLessThanOrEqual(1950, $book->publication_year);
        $this->assertTrue($book->is_classic);
    }

    public function test_factory_modern_state(): void
    {
        $book = Book::factory()->modern()->create();

        $this->assertGreaterThan(1950, $book->publication_year);
        $this->assertFalse($book->is_classic);
    }

    public function test_factory_without_description_state(): void
    {
        $book = Book::factory()->withoutDescription()->create();

        $this->assertNull($book->description);
        $this->assertNull($book->short_description);
    }

    public function test_factory_with_genre_state(): void
    {
        $book = Book::factory()->withGenre('Mystery')->create();

        $this->assertEquals('Mystery', $book->genre);
    }
}
