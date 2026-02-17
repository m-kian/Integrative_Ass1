<?php

namespace App\Traits;

use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * HasApiTokens Trait
 * 
 * This trait provides convenient methods for managing API tokens on a model.
 * It extends the functionality of Laravel Sanctum's HasApiTokens trait with
 * additional helper methods for token management, ability checking, and token lifecycle.
 * 
 * Usage:
 * Add this trait to your User model or any model that should support API tokens.
 * 
 * @mixin \Illuminate\Foundation\Auth\User
 */
trait HasApiTokens
{
    use \Laravel\Sanctum\HasApiTokens;

    /**
     * Get the model's API tokens.
     * 
     * @return MorphMany
     */
    public function tokens(): MorphMany
    {
        return $this->morphMany(PersonalAccessToken::class, 'tokenable');
    }

    /**
     * Get all active (non-expired) tokens.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveTokens()
    {
        return $this->tokens()
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();
    }

    /**
     * Get all expired tokens.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiredTokens()
    {
        return $this->tokens()
            ->where('expires_at', '<=', now())
            ->get();
    }

    /**
     * Get unused tokens (never used).
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnusedTokens()
    {
        return $this->tokens()
            ->whereNull('last_used_at')
            ->get();
    }

    /**
     * Get recently used tokens.
     * 
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentTokens($limit = 5)
    {
        return $this->tokens()
            ->whereNotNull('last_used_at')
            ->orderBy('last_used_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get a token by name.
     * 
     * @param string $name
     * @return PersonalAccessToken|null
     */
    public function getTokenByName($name)
    {
        return $this->tokens()
            ->where('name', $name)
            ->first();
    }

    /**
     * Create a token with optional expiration.
     * 
     * @param string $name
     * @param array $abilities
     * @param int|null $expiresInMinutes
     * @return \Laravel\Sanctum\NewAccessToken
     */
    public function createTokenWithExpiration($name, $abilities = ['*'], $expiresInMinutes = null)
    {
        $token = $this->createToken($name, $abilities);

        if ($expiresInMinutes !== null) {
            $token->accessToken->expires_at = now()->addMinutes($expiresInMinutes);
            $token->accessToken->save();
        }

        return $token;
    }

    /**
     * Revoke a specific token by ID.
     * 
     * @param int|string $tokenId
     * @return bool
     */
    public function revokeToken($tokenId)
    {
        return (bool) $this->tokens()
            ->where('id', $tokenId)
            ->delete();
    }

    /**
     * Revoke all tokens except specified ones.
     * 
     * @param array $tokenIds
     * @return int
     */
    public function revokeAllTokensExcept($tokenIds = [])
    {
        return $this->tokens()
            ->when(!empty($tokenIds), function ($query) use ($tokenIds) {
                $query->whereNotIn('id', $tokenIds);
            })
            ->delete();
    }

    /**
     * Revoke all tokens.
     * 
     * @return int
     */
    public function revokeAllTokens()
    {
        return $this->tokens()->delete();
    }

    /**
     * Check if model has any active tokens.
     * 
     * @return bool
     */
    public function hasActiveTokens()
    {
        return $this->tokens()
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Check if model has tokens with specific ability.
     * 
     * @param string $ability
     * @return bool
     */
    public function hasTokensWithAbility($ability)
    {
        return $this->tokens()
            ->whereJsonContains('abilities', $ability)
            ->exists();
    }

    /**
     * Get all tokens with a specific ability.
     * 
     * @param string $ability
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTokensWithAbility($ability)
    {
        return $this->tokens()
            ->whereJsonContains('abilities', $ability)
            ->get();
    }

    /**
     * Add ability to all tokens.
     * 
     * @param string $ability
     * @return int
     */
    public function addAbilityToAllTokens($ability)
    {
        $updated = 0;
        
        foreach ($this->tokens as $token) {
            $abilities = json_decode($token->abilities, true) ?? [];
            
            if (!in_array($ability, $abilities)) {
                $abilities[] = $ability;
                $token->abilities = json_encode($abilities);
                $token->save();
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Remove ability from all tokens.
     * 
     * @param string $ability
     * @return int
     */
    public function removeAbilityFromAllTokens($ability)
    {
        $updated = 0;
        
        foreach ($this->tokens as $token) {
            $abilities = json_decode($token->abilities, true) ?? [];
            
            if (in_array($ability, $abilities)) {
                $abilities = array_filter($abilities, function ($a) use ($ability) {
                    return $a !== $ability;
                });
                $token->abilities = json_encode(array_values($abilities));
                $token->save();
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Get token statistics.
     * 
     * @return array
     */
    public function getTokenStatistics()
    {
        return [
            'total' => $this->tokens()->count(),
            'active' => $this->getActiveTokens()->count(),
            'expired' => $this->getExpiredTokens()->count(),
            'unused' => $this->getUnusedTokens()->count(),
            'last_used' => $this->tokens()
                ->whereNotNull('last_used_at')
                ->latest('last_used_at')
                ->value('last_used_at'),
            'oldest_token' => $this->tokens()
                ->oldest('created_at')
                ->value('created_at'),
        ];
    }
}
