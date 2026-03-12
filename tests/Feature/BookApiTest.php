<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_book_creates_book_with_authors(): void
    {
        $user = User::factory()->create();
        $authors = Author::factory()->count(2)->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/books', [
            'title' => 'Test Book',
            'description' => 'A test description',
            'author_ids' => $authors->pluck('id')->toArray(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Test Book');

        $this->assertDatabaseHas('books', ['title' => 'Test Book']);
        $this->assertDatabaseCount('author_book', 2);
    }

    public function test_store_book_validation_fails_without_title(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/books', [
            'description' => 'No title provided',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'author_ids']);
    }

    public function test_delete_book_removes_book(): void
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson("/api/books/{$book->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_delete_nonexistent_book_returns_404(): void
    {
        $response = $this->deleteJson('/api/books/999');

        $response->assertStatus(404);
    }
}
