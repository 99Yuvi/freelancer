<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SocketTokenController extends Controller
{
    /** Issue (or reissue) a Sanctum token for Socket.io auth */
    public function show(Request $request)
    {
        $user = $request->user();

        // Revoke any existing socket tokens so they don't accumulate
        $user->tokens()->where('name', 'socket')->delete();

        $token = $user->createToken('socket')->plainTextToken;

        return response()->json(['token' => $token]);
    }
}
