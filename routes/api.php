<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\controllers\UserController;

Route::prefix('users')->group(function(){
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']); 
});

Route::post('/tokens/create', function (Request $request) {
    $token = $request->user()->createToken($request->token_name);

    return ['token' => $token->plainTextToken];
});

Route::post('/tokens/create-with-abilities', function (Request $request) {
    $token = $request->user()->createToken($request->token_name, $request->abilities ?? []);

    return ['token' => $token->plainTextToken];
});

Route::get('/tokens', function (Request $request) {
    return ['tokens' => $request->user()->tokens];
});

Route::post('/server/update', function (Request $request) {
    if ($request->user()->tokenCan('server:update')) {
        return ['message' => 'Server updated successfully'];
    }

    return response(['error' => 'Unauthorized - token does not have server:update ability'], 403);
});

Route::post('/server/delete', function (Request $request) {
    if ($request->user()->tokenCant('server:delete')) {
        return response(['error' => 'Unauthorized - token does not have server:delete ability'], 403);
    }

    return ['message' => 'Server deleted successfully'];
});

Route::get('/orders', function (Request $request) {
    return ['message' => 'Token has both "check-status" and "place-orders" abilities'];
})->middleware(['auth:sanctum', 'abilities:check-status,place-orders']);