<?php

namespace App\Jobs;

use App\Models\Book;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateAuthorLastBookTitle implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Book $book
    ) {}

    public function handle(): void
    {
        $this->book->authors->each(function ($author) {
            $author->update(['last_book_title' => $this->book->title]);
        });
    }
}
