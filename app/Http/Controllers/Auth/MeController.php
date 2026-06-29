<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load(
            $request->user()->isFreelancer() ? 'freelancerProfile.skills' : 'clientProfile'
        );

        $profile = $user->isFreelancer()
            ? $user->freelancerProfile
            : $user->clientProfile;

        return response()->json([
            'data' => [
                'user'    => $user->only('id', 'name', 'email', 'role', 'status', 'avatar_path', 'email_verified_at'),
                'profile' => $profile,
            ],
        ]);
    }
}
