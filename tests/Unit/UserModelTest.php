<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_relationship(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->addBook($book);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $user->books());
        $this->assertTrue($user->books->contains($book));
    }

    public function test_books_relationship_orders_by_created_at_desc(): void
    {
        $user = User::factory()->create();

        $firstBook = Book::factory()->create(['title' => 'First Book']);
        $secondBook = Book::factory()->create(['title' => 'Second Book']);
        $thirdBook = Book::factory()->create(['title' => 'Third Book']);

        // Add books with specific timestamps
        $user->books()->attach($firstBook, [
            'read_at' => now(),
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        $user->books()->attach($secondBook, [
            'read_at' => now(),
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);

        $user->books()->attach($thirdBook, [
            'read_at' => now(),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $books = $user->books()->get();

        // Should be ordered by created_at desc (most recent first)
        $this->assertEquals('Second Book', $books->first()->title);
        $this->assertEquals('Third Book', $books->get(1)->title);
        $this->assertEquals('First Book', $books->last()->title);
    }

    public function test_has_book_returns_true_when_user_has_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->addBook($book);

        $this->assertTrue($user->hasBook($book));
    }

    public function test_has_book_returns_false_when_user_does_not_have_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->assertFalse($user->hasBook($book));
    }

    public function test_add_book_successfully_adds_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->assertFalse($user->hasBook($book));

        $user->addBook($book);

        $this->assertTrue($user->hasBook($book));

        // Check pivot data
        $pivotData = $user->books()->where('book_id', $book->id)->first()->pivot;
        $this->assertNotNull($pivotData->read_at);
        $this->assertNotNull($pivotData->created_at);
        $this->assertNotNull($pivotData->updated_at);
    }

    public function test_add_book_with_custom_read_at(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $customReadAt = now()->subWeek();

        $user->addBook($book, $customReadAt);

        $pivotData = $user->books()->where('book_id', $book->id)->first()->pivot;
        $this->assertEquals($customReadAt->format('Y-m-d H:i:s'), $pivotData->read_at);
    }

    public function test_add_book_does_not_duplicate_existing_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->addBook($book);
        $user->addBook($book); // Try to add again

        $this->assertEquals(1, $user->books()->count());
    }

    public function test_remove_book_successfully_removes_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->addBook($book);
        $this->assertTrue($user->hasBook($book));

        $result = $user->removeBook($book);

        $this->assertTrue($result);
        $this->assertFalse($user->hasBook($book));
    }

    public function test_remove_book_returns_false_when_book_not_found(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $result = $user->removeBook($book);

        $this->assertFalse($result);
    }

    public function test_get_book_count_returns_correct_count(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(0, $user->getBookCount());

        $books = Book::factory()->count(3)->create();
        foreach ($books as $book) {
            $user->addBook($book);
        }

        $this->assertEquals(3, $user->getBookCount());

        $user->removeBook($books->first());

        $this->assertEquals(2, $user->getBookCount());
    }

    public function test_fillable_attributes(): void
    {
        $user = new User;

        $expected = ['name', 'email', 'password'];

        $this->assertEquals($expected, $user->getFillable());
    }

    public function test_hidden_attributes(): void
    {
        $user = new User;

        $expected = ['password', 'remember_token'];

        $this->assertEquals($expected, $user->getHidden());
    }

    public function test_casts_attributes(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $user->email_verified_at);
    }

    public function test_password_is_hashed(): void
    {
        $user = User::factory()->create([
            'password' => 'plaintext-password',
        ]);

        $this->assertNotEquals('plaintext-password', $user->password);
        $this->assertTrue(\Hash::check('plaintext-password', $user->password));
    }

    public function test_user_factory_creates_valid_user(): void
    {
        $user = User::factory()->create();

        $this->assertNotEmpty($user->name);
        $this->assertNotEmpty($user->email);
        $this->assertNotEmpty($user->password);
        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue($user->exists);
    }

    public function test_user_to_array_hides_sensitive_data(): void
    {
        $user = User::factory()->create([
            'password' => 'secret-password',
            'remember_token' => 'secret-token',
        ]);

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
    }

    public function test_multiple_users_can_have_same_book(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $book = Book::factory()->create();

        $user1->addBook($book);
        $user2->addBook($book);

        $this->assertTrue($user1->hasBook($book));
        $this->assertTrue($user2->hasBook($book));
        $this->assertEquals(2, $book->users()->count());
    }

    public function test_removing_book_from_one_user_does_not_affect_others(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $book = Book::factory()->create();

        $user1->addBook($book);
        $user2->addBook($book);

        $user1->removeBook($book);

        $this->assertFalse($user1->hasBook($book));
        $this->assertTrue($user2->hasBook($book));
        $this->assertEquals(1, $book->users()->count());
    }

    public function test_user_book_relationship_preserves_pivot_data(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $readAt = now()->subMonth();

        $user->addBook($book, $readAt);

        $userBook = $user->books()->first();
        $this->assertEquals($readAt->format('Y-m-d H:i:s'), $userBook->pivot->read_at);
    }
}
