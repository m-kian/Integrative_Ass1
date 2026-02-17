# Database Tables Documentation

## Overview

Your Laravel application uses SQLite for local development. All tables are created through migrations.

## Tables

### 1. users
Stores user account information.

**Columns:**
- `id` (INTEGER PRIMARY KEY) - Unique identifier
- `name` (VARCHAR) - User's name
- `email` (VARCHAR UNIQUE) - User's email address
- `email_verified_at` (TIMESTAMP NULLABLE) - Email verification timestamp
- `password` (VARCHAR) - Encrypted password
- `remember_token` (VARCHAR NULLABLE) - "Remember me" token
- `created_at` (TIMESTAMP) - Creation timestamp
- `updated_at` (TIMESTAMP) - Last update timestamp

**Usage:**
```php
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => Hash::make('password'),
]);
```

### 2. cache
Stores cache data for the application.

**Columns:**
- `key` (VARCHAR PRIMARY KEY) - Cache key identifier
- `value` (TEXT) - Cached value (usually serialized)
- `expiration` (INTEGER) - Expiration timestamp

**Usage:**
Automatically managed by Laravel. Used for:
- Query caching
- View caching
- Configuration caching

### 3. jobs
Stores queued jobs for background processing.

**Columns:**
- `id` (INTEGER PRIMARY KEY) - Unique job ID
- `queue` (VARCHAR) - Queue name
- `payload` (TEXT) - Job payload (serialized)
- `attempts` (INTEGER) - Number of attempts
- `reserved_at` (TIMESTAMP NULLABLE) - Reserved timestamp
- `available_at` (TIMESTAMP) - When job is available
- `created_at` (TIMESTAMP) - Creation timestamp

**Usage:**
For dispatching background jobs:
```php
MyJob::dispatch($data)->delay(now()->addMinutes(5));
```

### 4. personal_access_tokens
Stores API tokens for Sanctum authentication.

**Columns:**
- `id` (INTEGER PRIMARY KEY) - Unique identifier
- `tokenable_type` (VARCHAR) - Polymorphic relation type (e.g., "App\Models\User")
- `tokenable_id` (INTEGER) - Polymorphic relation ID (user ID)
- `name` (TEXT) - Token name/label
- `token` (VARCHAR UNIQUE) - Hashed token (SHA-256)
- `abilities` (TEXT NULLABLE) - JSON array of abilities/scopes
- `last_used_at` (TIMESTAMP NULLABLE) - Last usage time
- `expires_at` (TIMESTAMP NULLABLE) - Expiration time
- `created_at` (TIMESTAMP) - Creation timestamp
- `updated_at` (TIMESTAMP) - Last update timestamp

**Usage:**
```php
// Create API token
$token = $user->createToken('Mobile App', ['read', 'write'])->plainTextToken;

// Get user's tokens
$tokens = $user->tokens;

// Check token ability
if ($request->user()->tokenCan('write')) {
    // Allow action
}

// Revoke token
$user->tokens()->where('id', $tokenId)->delete();
```

## Key Relationships

```
users
  ├── tokens (1:Many) → personal_access_tokens
  └── jobs (1:Many) → jobs
```

## Database Connection

**Driver:** SQLite  
**Location:** `database/database.sqlite`  
**Connection:** Configured in `.env` as:
```
DB_CONNECTION=sqlite
DB_DATABASE=c:/xampp/htdocs/testProject/database/database.sqlite
```

## Common Database Operations

### View all tables
```php
$tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
```

### Check table structure
```php
$columns = DB::select("PRAGMA table_info(users)");
```

### View all users
```php
$users = User::all();
```

### Get user with tokens
```php
$user = User::with('tokens')->find($userId);
```

### Check database size
```
File: database/database.sqlite
Location: c:\xampp\htdocs\testProject\database\
```

## Migrations

Migrations are stored in `database/migrations/`:

1. `0001_01_01_000000_create_users_table.php` - Users table
2. `0001_01_01_000001_create_cache_table.php` - Cache table
3. `0001_01_01_000002_create_jobs_table.php` - Jobs table
4. `2026_02_17_054717_create_personal_access_tokens_table.php` - Sanctum tokens table

## Running Migrations

### Fresh migration (drop all tables and re-create)
```bash
php artisan migrate:fresh
```

### Refresh migration (rollback and re-run)
```bash
php artisan migrate:refresh
```

### Rollback last migration
```bash
php artisan migrate:rollback
```

### Reset migrations
```bash
php artisan migrate:reset
```

## SQLite Features

SQLite is ideal for:
- Local development
- Small projects
- No setup required
- Single file database
- Fast queries

## Upgrading to MySQL (Production)

To switch to MySQL:

1. Update `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=root
DB_PASSWORD=your_password
```

2. Run migrations:
```bash
php artisan migrate
```

## Backing Up SQLite Database

```bash
# Copy the database file
cp database/database.sqlite database/database.backup.sqlite
```

## Related Resources

- [Database Documentation](database/SANCTUM_DATABASE.md)
- [API Routes](routes/api.php)
- [User Model](app/Models/User.php)
- [Sanctum Controller](app/Http/Controllers/SanctumController.php)
- [Custom HasApiTokens Trait](app/Traits/HasApiTokens.php)
