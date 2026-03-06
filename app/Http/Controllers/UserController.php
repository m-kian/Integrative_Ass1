<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct()
    {
        // Protect endpoints with Spatie permissions
        $this->middleware('permission:view users')->only(['index', 'show']);
        $this->middleware('permission:create users')->only('store');
        $this->middleware('permission:edit users')->only('update');
        $this->middleware('permission:delete users')->only('destroy');
    }

    /**
     * List users with their roles.
     */
    public function index()
    {
        return response()->json(User::with('roles')->paginate(10));
    }

    /**
     * Store a new user with role assignment.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|string|in:Admin,Chairman,Teacher,Student',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name'     => $request->input('name'),
            'email'    => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        $user->assignRole($request->input('role'));

        return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
    }

    /**
     * Show a single user.
     */
    public function show(User $user)
    {
        return response()->json($user->load('roles'));
    }

    /**
     * Update an existing user and sync roles.
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|required|string|max:255',
            'email'    => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:8',
            'role'     => 'sometimes|required|string|in:Admin,Chairman,Teacher,Student',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payload = $request->only(['name', 'email']);

        if ($request->filled('password')) {
            $payload['password'] = Hash::make($request->input('password'));
        }

        if (!empty($payload)) {
            $user->update($payload);
        }

        if ($request->filled('role')) {
            $user->syncRoles([$request->input('role')]);
        }

        return response()->json(['message' => 'User updated successfully', 'user' => $user]);
    }

    /**
     * Delete a user.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
