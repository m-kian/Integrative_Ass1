<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sanctum - API Token Authentication
 * 
 * This migration creates the personal_access_tokens table which stores API tokens
 * used for stateless, token-based authentication via Laravel Sanctum.
 * 
 * Table Schema:
 * - id: Primary key
 * - tokenable_type: Polymorphic relation type (usually "App\Models\User")
 * - tokenable_id: Polymorphic relation ID (user ID)
 * - name: Token name/label for identification
 * - token: The hashed token (unique, 64 chars)
 * - abilities: JSON field containing the token's abilities/scopes
 * - last_used_at: Timestamp of last token usage
 * - expires_at: Optional token expiration timestamp
 * - timestamps: created_at and updated_at
 * 
 * Usage:
 * - Creating token: $token = $user->createToken('token-name', ['ability:name'])->plainTextToken;
 * - Getting tokens: $user->tokens;
 * - Checking ability: $request->user()->tokenCan('ability:name');
 * - Revoking token: $token->delete();
 * 
 * Authentication Header:
 * Authorization: Bearer {plainTextToken}
 * 
 * @see \Laravel\Sanctum\PersonalAccessToken
 * @see App\Models\PersonalAccessToken
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
