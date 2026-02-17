<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\controllers\UserController;
use App\Http\Controllers\SanctumController;

// Protected routes - require Sanctum authentication
Route::middleware('auth:sanctum')->group(function() {
    // Get authenticated user info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Token management routes
    Route::post('/tokens/create', [SanctumController::class, 'createToken']);

    Route::post('/tokens/create-with-abilities', [SanctumController::class, 'createTokenWithAbilities']);

    Route::get('/tokens', [SanctumController::class, 'getTokens']);

    Route::delete('/tokens/{token_id}', [SanctumController::class, 'revokeToken']);

    Route::delete('/tokens', [SanctumController::class, 'revokeAllTokens']);

    Route::get('/tokens/check-ability', [SanctumController::class, 'checkAbility']);

    // Server management routes with authorization checks
    Route::post('/servers/{serverId}/update', [SanctumController::class, 'updateServer']);

    Route::delete('/servers/{serverId}', [SanctumController::class, 'deleteServer']);

    // Example protected routes with ability checking
    Route::get('/orders', function (Request $request) {
        return ['message' => 'Token has both "check-status" and "place-orders" abilities'];
    })->middleware('abilities:check-status,place-orders');

    Route::get('/orders-any', function (Request $request) {
        return ['message' => 'Token has "check-status" or "place-orders" ability'];
    })->middleware('ability:check-status,place-orders');
});

// Public routes (no authentication required)
Route::prefix('users')->group(function(){
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
});