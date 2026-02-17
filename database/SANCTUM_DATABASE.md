# Sanctum - API Token Authentication Database Documentation

## Overview

Laravel Sanctum provides a lightweight authentication system for SPAs (Single Page Applications) and simple APIs. This document explains how Sanctum uses the database to manage API tokens and authentication.

## Database Tables

### personal_access_tokens

The `personal_access_tokens` table stores all API tokens used for stateless authentication.

#### Schema

```php
Schema::create('personal_access_tokens', function (Blueprint $table) {
    $table->id();                                    // Primary key
    $table->morphs('tokenable');                    // Polymorphic relation (user)
    $table->text('name');                           // Token name/label
    $table->string('token', 64)->unique();          // Hashed token (SHA-256)
    $table->text('abilities')->nullable();          // JSON: token abilities/scopes
    $table->timestamp('last_used_at')->nullable();  // Last usage timestamp
    $table->timestamp('expires_at')->nullable();    // Expiration timestamp (indexed)
    $table->timestamps();                           // created_at, updated_at
});
```

#### Column Details

- **id**: Unique identifier for the token record
- **tokenable_type**: The class type of the tokenable model (e.g., `App\Models\User`)
- **tokenable_id**: The ID of the associated user
- **name**: User-friendly name for the token (e.g., "Mobile App Token", "Desktop Client")
- **token**: The SHA-256 hashed token value (unique, 64 characters)
- **abilities**: JSON array containing token capabilities (e.g., `["read", "write", "admin"]`)
- **last_used_at**: Tracks when the token was last used for API requests
- **expires_at**: Optional expiration date; null means no expiration
- **created_at**: Timestamp when the token was created
- **updated_at**: Timestamp last updated

## Token Lifecycle

### 1. Token Creation

```php
// Create a basic token
$token = $user->createToken('API Token')->plainTextToken;

// Create a token with specific abilities
$token = $user->createToken('Mobile App', ['read', 'write'])->plainTextToken;
```

The plain text token is returned only once. It's immediately hashed and stored in the database.

### 2. Token Storage

```
Database Storage (Hashed):       User Receives (Plain Text):
------------------------------   ----------------------------
$2y$10$abc123...                 14|xyzABC...token...
```

- Plain text tokens follow format: `{ID}|{HASH}`
- The hash is computed using SHA-256
- The plain text token is NEVER stored in the database
- Always display the plain text token immediately after creation

### 3. Token Usage

When a client makes a request with the token:

```bash
curl -X GET http://api.example.com/user \
  -H "Authorization: Bearer 14|xyzABC...token..."
```

Sanctum:
1. Extracts the token from the Authorization header
2. Hashes it using SHA-256
3. Searches the database for a matching hashed token
4. Verifies the token hasn't expired
5. Loads the associated user
6. Updates `last_used_at` timestamp

### 4. Token Revocation

```php
// Revoke specific token
$user->tokens()->where('id', $tokenId)->delete();

// Revoke all tokens
$user->tokens()->delete();

// Revoke tokens except current
$user->tokens()->where('id', '!=', $currentTokenId)->delete();
```

## Token Abilities (Scopes)

Token abilities define what actions a token can perform.

### Creating Tokens with Abilities

```php
$token = $user->createToken('API Token', [
    'read:posts',
    'write:posts',
    'delete:posts',
    'admin'
])->plainTextToken;
```

### Checking Token Abilities

```php
// Check if token has ability
if ($request->user()->tokenCan('write:posts')) {
    // Allow action
}

// Check if token lacks ability
if ($request->user()->tokenCant('admin')) {
    // Deny action
}
```

### Using Ability Middleware

```php
// Require ALL abilities
Route::put('/posts/{id}', function () {
    // ...
})->middleware(['auth:sanctum', 'abilities:write:posts,edit:posts']);

// Require ANY ability
Route::get('/posts', function () {
    // ...
})->middleware(['auth:sanctum', 'ability:read:posts,read:all']);
```

## Database Queries

### Get All Tokens for a User

```php
$tokens = $user->tokens;
$tokens = $user->tokens()->get();
```

Query:
```sql
SELECT * FROM personal_access_tokens 
WHERE tokenable_type = 'App\Models\User' 
AND tokenable_id = ?;
```

### Get Active Tokens (Non-Expired)

```php
$tokens = $user->tokens()
    ->where(function ($query) {
        $query->whereNull('expires_at')
            ->orWhere('expires_at', '>', now());
    })
    ->get();
```

### Get Recently Used Tokens

```php
$recentTokens = $user->tokens()
    ->whereNotNull('last_used_at')
    ->orderBy('last_used_at', 'desc')
    ->take(5)
    ->get();
```

### Find Token by Hash

```php
$token = PersonalAccessToken::where('token', hash('sha256', $plainToken))->first();
```

## Security Best Practices

### 1. Token Display

```php
// GOOD: Display immediately after creation
return response()->json([
    'token' => $token->plainTextToken
]);

// Then never save or log the plain text token
```

### 2. Token Expiration

```php
// Create token with expiration
$token = $user->createToken('Temporary Token');
$token->accessToken->expires_at = now()->addHours(24);
$token->accessToken->save();
```

### 3. Token Rotation

```php
// Revoke old token and create new one
$user->tokens()->delete();
$newToken = $user->createToken('New Token')->plainTextToken;
```

### 4. Ability Validation

```php
// In Controller
if (!$request->user()->tokenCan('write:posts')) {
    return response()->json(['error' => 'Unauthorized'], 403);
}
```

### 5. Rate Limiting

Consider implementing rate limiting based on token:

```php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Limited routes
});
```

## Maintenance

### Cleanup Expired Tokens

```php
// Delete expired tokens manually
PersonalAccessToken::where('expires_at', '<', now())->delete();

// Schedule in `app/Console/Kernel.php`
$schedule->command('sanctum:prune-expired')->hourly();
```

### Monitor Token Usage

```php
$unused = $user->tokens()
    ->whereNull('last_used_at')
    ->where('created_at', '<', now()->subDays(7))
    ->get();
```

## Custom PersonalAccessToken Model

For company-specific logic, extend the Sanctum model:

```php
// app/Models/PersonalAccessToken.php
namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    // Add custom methods and properties
}
```

Register in `AppServiceProvider`:

```php
use Laravel\Sanctum\Sanctum;

Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
```

## Database Indexes

The migration creates indexes on:

- **token** (unique): Fast token lookup during authentication
- **expires_at**: Efficient expired token queries
- **tokenable_type, tokenable_id**: Efficient user token retrieval

## Migration

Run migration to create the table:

```bash
php artisan migrate
```

Rollback if needed:

```bash
php artisan migrate:rollback
```

## Related Files

- Migration: `database/migrations/2026_02_17_054717_create_personal_access_tokens_table.php`
- Model: `app/Models/PersonalAccessToken.php`
- Controller: `app/Http/Controllers/SanctumController.php`
- Routes: `routes/api.php`
- Config: `config/sanctum.php`

## References

- [Laravel Sanctum Documentation](https://laravel.com/docs/11.x/sanctum)
- [Database Schema](https://laravel.com/docs/11.x/schema)
- [Token-Based Authentication](https://tools.ietf.org/html/draft-ietf-oauth-v2-bearer-01)
