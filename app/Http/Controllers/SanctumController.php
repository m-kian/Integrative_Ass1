<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SanctumController extends Controller
{
    /**
     * Create a new API token for the authenticated user.
     */
    public function createToken(Request $request)
    {
        $token = $request->user()->createToken($request->token_name);

        return ['token' => $token->plainTextToken];
    }

    /**
     * Create a new API token with specific abilities.
     */
    public function createTokenWithAbilities(Request $request)
    {
        $token = $request->user()->createToken(
            $request->token_name,
            $request->abilities ?? []
        );

        return ['token' => $token->plainTextToken];
    }

    /**
     * Get all tokens for the authenticated user.
     */
    public function getTokens(Request $request)
    {
        return ['tokens' => $request->user()->tokens];
    }

    /**
     * Revoke a specific token.
     */
    public function revokeToken(Request $request)
    {
        $token = $request->user()->tokens()->find($request->token_id);

        if (!$token) {
            return response(['error' => 'Token not found'], 404);
        }

        $token->delete();

        return ['message' => 'Token revoked successfully'];
    }

    /**
     * Revoke all tokens for the authenticated user.
     */
    public function revokeAllTokens(Request $request)
    {
        $request->user()->tokens()->delete();

        return ['message' => 'All tokens revoked successfully'];
    }

    /**
     * Check if token has a specific ability.
     */
    public function checkAbility(Request $request)
    {
        $ability = $request->query('ability');

        if (!$ability) {
            return response(['error' => 'Ability parameter is required'], 400);
        }

        $hasAbility = $request->user()->tokenCan($ability);

        return [
            'ability' => $ability,
            'has_ability' => $hasAbility
        ];
    }

    /**
     * Example: Update server with authorization checks.
     * Verifies both user ownership and token ability.
     */
    public function updateServer(Request $request, $serverId)
    {
        // In a real application, you would fetch the server from database
        // For this example, we'll assume a mock server object
        $server = (object)[
            'id' => $serverId,
            'user_id' => auth()->id() // Simulating server ownership
        ];

        // Check both user ownership and token ability
        if ($request->user()->id === $server->user_id &&
            $request->user()->tokenCan('server:update')) {
            return ['message' => 'Server updated successfully'];
        }

        return response([
            'error' => 'Unauthorized - insufficient permissions or invalid token ability'
        ], 403);
    }

    /**
     * Example: Delete server with authorization checks.
     * Verifies both user ownership and token ability.
     */
    public function deleteServer(Request $request, $serverId)
    {
        // In a real application, you would fetch the server from database
        $server = (object)[
            'id' => $serverId,
            'user_id' => auth()->id() // Simulating server ownership
        ];

        // Check both user ownership and token ability
        if ($request->user()->id === $server->user_id &&
            $request->user()->tokenCan('server:delete')) {
            return ['message' => 'Server deleted successfully'];
        }

        return response([
            'error' => 'Unauthorized - insufficient permissions or invalid token ability'
        ], 403);
    }
}
