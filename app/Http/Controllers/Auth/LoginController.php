<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function store(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $request->session()->regenerate();
        $user = Auth::user();

        if ($user->status === 'suspended') {
            Auth::logout();
            return response()->json(['message' => 'Your account has been suspended.'], 403);
        }

        return response()->json([
            'data'    => [
                'user' => $user->only('id', 'name', 'email', 'role', 'email_verified_at'),
            ],
            'message' => 'Login successful.',
        ]);
    }
}
