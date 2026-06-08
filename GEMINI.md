# Project Instructions

## Testing
- **Environment Isolation:** ALWAYS run tests using `APP_ENV=testing php artisan test` to ensure the MySQL database is not affected.
- **Database:** Tests are configured to use an In-Memory SQLite database.
- **Safeguards:** `tests/TestCase.php` contains a safety check to abort if the environment is not `testing` or the connection is not `sqlite`.

## Database
- **Primary Database:** MySQL (Connection: `mysql`, Database: `warehouse`).
- **Migrations:** All new item types or schema changes must be reflected in migrations.
