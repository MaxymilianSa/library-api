# Library API

## Instalacja i uruchomienie

```bash
git clone git@github.com:MaxymilianSa/library-api.git
cd library-api
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

API dostępne pod `http://localhost:8000/api`

## Testy

```bash
php artisan test
```
