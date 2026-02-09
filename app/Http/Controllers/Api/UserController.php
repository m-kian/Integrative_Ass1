<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function index()
    {
        return response()->json([
            'users' => [
                ['id' => 1, 'name' => 'Kheanne'],
                ['id' => 2, 'name' => 'Micheal'],
            ]
        ]);
    }
}
