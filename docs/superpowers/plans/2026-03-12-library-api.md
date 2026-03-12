# Library Management API — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a Laravel API for managing a library (books & authors) with Sanctum auth, queued jobs, search filtering, and an Artisan command.

**Architecture:** Classic Laravel — resourceful controllers, Form Requests for validation, API Resources for response transformation, many-to-many via pivot table. Sanctum token auth protects book creation.

**Tech Stack:** Laravel (latest), SQLite (testing), Laravel Sanctum

**Spec:** `docs/superpowers/specs/2026-03-12-library-api-design.md`

---

## Chunk 1: Project Setup & Database

### Task 1: Create Laravel Project

**Files:**
- Create: entire Laravel project at `/Users/maksymiliansapa/Sites/library-api/`

- [ ] **Step 1: Install Laravel**

```bash
cd /Users/maksymiliansapa/Sites
composer create-project laravel/laravel library-api
```

- [ ] **Step 2: Install Sanctum**

```bash
cd /Users/maksymiliansapa/Sites/library-api
composer require laravel/sanctum
php artisan install:api
```

- [ ] **Step 3: Verify project runs**

```bash
cd /Users/maksymiliansapa/Sites/library-api
php artisan --version
```
Expected: Laravel version output

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "chore: initial Laravel project with Sanctum"
```

---

### Task 2: Create Migrations

**Files:**
- Create: `database/migrations/xxxx_create_authors_table.php`
- Create: `database/migrations/xxxx_create_books_table.php`
- Create: `database/migrations/xxxx_create_author_book_table.php`

- [ ] **Step 1: Generate migrations**

```bash
php artisan make:migration create_authors_table
php artisan make:migration create_books_table
php artisan make:migration create_author_book_table
```

- [ ] **Step 2: Define authors table**

```php
Schema::create('authors', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('last_book_title')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 3: Define books table**

```php
Schema::create('books', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 4: Define author_book pivot table**

```php
Schema::create('author_book', function (Blueprint $table) {
    $table->foreignId('author_id')->constrained()->cascadeOnDelete();
    $table->foreignId('book_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->primary(['author_id', 'book_id']);
});
```

- [ ] **Step 5: Run migrations**

```bash
php artisan migrate
```
Expected: All migrations run successfully

- [ ] **Step 6: Commit**

```bash
git add database/migrations/
git commit -m "feat: add authors, books, and pivot table migrations"
```

---

### Task 3: Create Models & Factories

**Files:**
- Create: `app/Models/Author.php`
- Create: `app/Models/Book.php`
- Create: `database/factories/AuthorFactory.php`
- Create: `database/factories/BookFactory.php`

- [ ] **Step 1: Generate models and factories**

```bash
php artisan make:model Author -f
php artisan make:model Book -f
```

- [ ] **Step 2: Define Author model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'last_book_title'];

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class)->withTimestamps();
    }
}
```

- [ ] **Step 3: Define Book model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description'];

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class)->withTimestamps();
    }
}
```

- [ ] **Step 4: Define AuthorFactory**

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AuthorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
        ];
    }
}
```

- [ ] **Step 5: Define BookFactory**

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
        ];
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add app/Models/ database/factories/
git commit -m "feat: add Author and Book models with factories"
```

---

## Chunk 2: API Resources, Requests & Auth

### Task 4: Create API Resources

**Files:**
- Create: `app/Http/Resources/BookResource.php`
- Create: `app/Http/Resources/AuthorResource.php`

- [ ] **Step 1: Generate resources**

```bash
php artisan make:resource BookResource
php artisan make:resource AuthorResource
```

- [ ] **Step 2: Define BookResource**

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'authors' => AuthorResource::collection($this->whenLoaded('authors')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

- [ ] **Step 3: Define AuthorResource**

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'last_book_title' => $this->last_book_title,
            'books' => BookResource::collection($this->whenLoaded('books')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Resources/
git commit -m "feat: add BookResource and AuthorResource"
```

---

### Task 5: Create Form Requests

**Files:**
- Create: `app/Http/Requests/StoreBookRequest.php`
- Create: `app/Http/Requests/UpdateBookRequest.php`
- Create: `app/Http/Requests/RegisterRequest.php`
- Create: `app/Http/Requests/LoginRequest.php`

- [ ] **Step 1: Generate request classes**

```bash
php artisan make:request StoreBookRequest
php artisan make:request UpdateBookRequest
php artisan make:request RegisterRequest
php artisan make:request LoginRequest
```

- [ ] **Step 2: Define StoreBookRequest**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'author_ids' => ['required', 'array', 'min:1'],
            'author_ids.*' => ['exists:authors,id'],
        ];
    }
}
```

- [ ] **Step 3: Define UpdateBookRequest**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'author_ids' => ['sometimes', 'required', 'array', 'min:1'],
            'author_ids.*' => ['exists:authors,id'],
        ];
    }
}
```

- [ ] **Step 4: Define RegisterRequest**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
```

- [ ] **Step 5: Define LoginRequest**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/
git commit -m "feat: add form request validation classes"
```

---

### Task 6: Create AuthController (Sanctum)

**Files:**
- Create: `app/Http/Controllers/AuthController.php`

- [ ] **Step 1: Generate controller**

```bash
php artisan make:controller AuthController
```

- [ ] **Step 2: Implement AuthController**

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/AuthController.php
git commit -m "feat: add AuthController with register and login"
```

---

## Chunk 3: Job, Controllers & Routes

### Task 7: Create UpdateAuthorLastBookTitle Job

**Files:**
- Create: `app/Jobs/UpdateAuthorLastBookTitle.php`

- [ ] **Step 1: Generate job**

```bash
php artisan make:job UpdateAuthorLastBookTitle
```

- [ ] **Step 2: Implement job**

```php
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
```

- [ ] **Step 3: Commit**

```bash
git add app/Jobs/UpdateAuthorLastBookTitle.php
git commit -m "feat: add UpdateAuthorLastBookTitle job"
```

---

### Task 8: Create BookController

**Files:**
- Create: `app/Http/Controllers/BookController.php`

- [ ] **Step 1: Generate controller**

```bash
php artisan make:controller BookController
```

- [ ] **Step 2: Implement BookController**

```php
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
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/BookController.php
git commit -m "feat: add BookController with CRUD operations"
```

---

### Task 9: Create AuthorController

**Files:**
- Create: `app/Http/Controllers/AuthorController.php`

- [ ] **Step 1: Generate controller**

```bash
php artisan make:controller AuthorController
```

- [ ] **Step 2: Implement AuthorController**

```php
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
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/AuthorController.php
git commit -m "feat: add AuthorController with index and show"
```

---

### Task 10: Define API Routes

**Files:**
- Modify: `routes/api.php`

- [ ] **Step 1: Define all API routes**

```php
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Books
Route::get('/books', [BookController::class, 'index']);
Route::get('/books/{book}', [BookController::class, 'show']);
Route::put('/books/{book}', [BookController::class, 'update']);
Route::delete('/books/{book}', [BookController::class, 'destroy']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/books', [BookController::class, 'store']);
});

// Authors
Route::get('/authors', [AuthorController::class, 'index']);
Route::get('/authors/{author}', [AuthorController::class, 'show']);
```

- [ ] **Step 2: Verify routes**

```bash
php artisan route:list --path=api
```
Expected: All defined routes listed

- [ ] **Step 3: Commit**

```bash
git add routes/api.php
git commit -m "feat: define API routes with Sanctum middleware"
```

---

## Chunk 4: Command & Tests

### Task 11: Create Artisan Command

**Files:**
- Create: `app/Console/Commands/CreateAuthorCommand.php`

- [ ] **Step 1: Generate command**

```bash
php artisan make:command CreateAuthorCommand
```

- [ ] **Step 2: Implement command**

```php
<?php

namespace App\Console\Commands;

use App\Models\Author;
use Illuminate\Console\Command;

class CreateAuthorCommand extends Command
{
    protected $signature = 'author:create';
    protected $description = 'Create a new author';

    public function handle(): int
    {
        $firstName = $this->ask('What is the author\'s first name?');
        $lastName = $this->ask('What is the author\'s last name?');

        $author = Author::create([
            'name' => "$firstName $lastName",
        ]);

        $this->info("Author '{$author->name}' created successfully (ID: {$author->id}).");

        return Command::SUCCESS;
    }
}
```

- [ ] **Step 3: Verify command is registered**

```bash
php artisan list | grep author
```
Expected: `author:create` visible in the list

- [ ] **Step 4: Commit**

```bash
git add app/Console/Commands/CreateAuthorCommand.php
git commit -m "feat: add author:create Artisan command"
```

---

### Task 12: Write Feature Tests

**Files:**
- Create: `tests/Feature/BookApiTest.php`

- [ ] **Step 1: Create test file**

```php
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
```

- [ ] **Step 2: Run tests**

```bash
php artisan test --filter=BookApiTest
```
Expected: All 4 tests pass

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/BookApiTest.php
git commit -m "test: add feature tests for book store and delete"
```

---

### Task 13: Final Verification

- [ ] **Step 1: Run full test suite**

```bash
php artisan test
```
Expected: All tests pass

- [ ] **Step 2: Verify all routes**

```bash
php artisan route:list --path=api
```

- [ ] **Step 3: Final commit (if any cleanup needed)**

```bash
git add -A
git commit -m "chore: final cleanup"
```
