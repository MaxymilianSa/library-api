<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthorResource;
use App\Models\Author;

class AuthorController extends Controller
{
    public function index()
    {
        $query = Author::with('books');

        if ($search = request('search')) {
            $query->whereHas('books', fn($q) => $q->where('title', 'like', "%{$search}%"));
        }

        return AuthorResource::collection($query->paginate(15));
    }

    public function show(Author $author): AuthorResource
    {
        $author->load('books');

        return new AuthorResource($author);
    }
}
