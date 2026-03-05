<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    // READ ALL USERS
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    // CREATE USER
    public function store(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'sometimes|in:admin,chairman,teacher,student'
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['role'] = $validated['role'] ?? 'student';

        $user = User::create($validated);

        return response()->json([
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    // READ ONE USER
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    // UPDATE USER
    public function update(Request $request, string $id)
{
    $user = User::find($id);

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    $currentUser = $request->user();

    // Only allow role updates if the current user is admin or updating their own profile
    if ($request->has('role') && !$currentUser->isAdmin() && $currentUser->id != $id) {
        return response()->json(['message' => 'Forbidden. Only admins can update user roles.'], 403);
    }

    $validated = $request->validate([
        'name' => 'sometimes|string|max:255',
        'email' => 'sometimes|email|unique:users,email,' . $id, 
        'password' => 'sometimes|min:6',
        'role' => 'sometimes|in:admin,chairman,teacher,student'
    ]);

    if (isset($validated['password'])) {
        $validated['password'] = bcrypt($validated['password']);
    }

    $user->update($validated);

    return response()->json([
        'message' => 'User updated successfully',
        'data' => $user
    ], 200);
}

    // DELETE USER
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $currentUser = request()->user();

        // Only admins can delete users
        if (!$currentUser->isAdmin()) {
            return response()->json(['message' => 'Forbidden. Only admins can delete users.'], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}
