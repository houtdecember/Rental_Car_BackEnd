<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use HashContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|min:2',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string',
                'role' => 'nullable|in:user,admin',
                'image' => 'nullable|string'
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'role' => $validated['role'] ?? 'user',
                'image' => $validated['image'] ?? null
            ]);

            return response([
                'message' => "Register Success",
                'data' => $user
            ], 201);
        } catch (\Exception $e) {
            return response([
                'message' => "Registration failed",
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response([
                    'message' => "Invalid email or password"
                ], 401);
            }

            $token = $user->createToken('token-api')->plainTextToken;
            return response([
                'message' => "Login successful",
                'data' => $user,
                'token' => $token
            ], 200);
        } catch (\Exception $e) {
            return response([
                'message' => "Login failed",
                'error' => $e->getMessage()
            ], 400);
        }
    }
}