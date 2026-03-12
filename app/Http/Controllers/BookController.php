<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Jobs\UpdateAuthorLastBookTitle;
use App\Models\Book;
use Illuminate\Http\JsonResponse;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::with('authors')->paginate(15);

        return BookResource::collection($books);
    }

    public function show(Book $book): BookResource
    {
        $book->load('authors');

        return new BookResource($book);
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = Book::create($request->only(['title', 'description']));
        $book->authors()->attach($request->author_ids);
        $book->load('authors');

        UpdateAuthorLastBookTitle::dispatch($book);

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        $book->update($request->only(['title', 'description']));

        if ($request->has('author_ids')) {
            $book->authors()->sync($request->author_ids);
        }

        $book->load('authors');

        return new BookResource($book);
    }

    public function destroy(Book $book): JsonResponse
    {
        $book->delete();

        return response()->json(null, 204);
    }
}
