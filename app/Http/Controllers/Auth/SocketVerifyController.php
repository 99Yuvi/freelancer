<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SocketVerifyController extends Controller
{
    /** Called by Node.js chat server to validate a user's Sanctum token */
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user_id' => $user->id,
            'role'    => $user->role,
            'name'    => $user->name,
        ]);
    }
}
