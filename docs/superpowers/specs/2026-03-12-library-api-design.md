# Library Management API — Design Spec

Zadanie rekrutacyjne 300.codes — API do zarządzania biblioteką w Laravel.

## Tech Stack

- Laravel (latest stable)
- SQLite for testing
- Laravel Sanctum for authentication

## Models & Database

### Tables

**authors**
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigIncrements | PK |
| name | string | required |
| last_book_title | string | nullable |
| timestamps | | |

**books**
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigIncrements | PK |
| title | string | required |
| description | text | nullable |
| timestamps | | |

**author_book** (pivot)
| Column | Type | Constraints |
|--------|------|-------------|
| author_id | foreignId | FK → authors |
| book_id | foreignId | FK → books |
| timestamps | | |

### Relationships

- `Author` → `belongsToMany(Book::class)`
- `Book` → `belongsToMany(Author::class)`

## API Endpoints

### Books

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/books` | List all books with authors (paginated) | No |
| GET | `/api/books/{id}` | Book details with authors | No |
| POST | `/api/books` | Create a book | Sanctum |
| PUT | `/api/books/{id}` | Update a book | No |
| DELETE | `/api/books/{id}` | Delete a book | No |

### Authors

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/authors` | List all authors with books (paginated, optional `?search=`) | No |
| GET | `/api/authors/{id}` | Author details with books | No |

### Auth (Sanctum)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/register` | Register user, returns token |
| POST | `/api/login` | Login, returns token |

## Validation

### StoreBookRequest
- `title` — required, string, max:255
- `description` — nullable, string
- `author_ids` — required, array, min:1
- `author_ids.*` — exists:authors,id

### UpdateBookRequest
- `title` — sometimes, required, string, max:255
- `description` — nullable, string
- `author_ids` — sometimes, required, array, min:1
- `author_ids.*` — exists:authors,id

### RegisterRequest
- `name` — required, string
- `email` — required, email, unique:users
- `password` — required, string, min:8, confirmed

### LoginRequest
- `email` — required, email
- `password` — required, string

## API Resources

- `BookResource` — returns book data with nested `AuthorResource` collection
- `AuthorResource` — returns author data with nested `BookResource` collection (when loaded)

## Job: UpdateAuthorLastBookTitle

- Dispatched after `POST /api/books`
- Receives the created Book
- Updates `last_book_title` on each author attached to the book

## Search Filter

`GET /api/authors?search=harry`

Returns authors who have a book with the search string in its title:
```php
whereHas('books', fn($q) => $q->where('title', 'like', "%{$search}%"))
```

## Artisan Command

`php artisan author:create`

- Prompts separately for first name and last name
- Concatenates them into a single `name` value (e.g., `"Jan Kowalski"`)
- Creates a new Author record

## Tests (Feature)

- `POST /api/books` — creates book with valid data (201, stored in DB, authors attached)
- `POST /api/books` — validation failure returns 422
- `DELETE /api/books/{id}` — deletes book (204, removed from DB)
- `DELETE /api/books/{id}` — non-existent book returns 404

## File Structure

```
app/
  Http/
    Controllers/
      AuthController.php
      BookController.php
      AuthorController.php
    Requests/
      StoreBookRequest.php
      UpdateBookRequest.php
      LoginRequest.php
      RegisterRequest.php
    Resources/
      BookResource.php
      AuthorResource.php
  Jobs/
    UpdateAuthorLastBookTitle.php
  Models/
    Author.php
    Book.php
  Console/
    Commands/
      CreateAuthorCommand.php
database/
  migrations/
    create_authors_table.php
    create_books_table.php
    create_author_book_table.php
  factories/
    AuthorFactory.php
    BookFactory.php
tests/
  Feature/
    BookApiTest.php
routes/
  api.php
```
