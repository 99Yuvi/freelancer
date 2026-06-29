<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\ClientProfile;
use App\Models\FreelancerProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    public function store(RegisterRequest $request)
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => $request->password,
                'role'     => $request->role,
            ]);

            match ($request->role) {
                'freelancer' => FreelancerProfile::create(['user_id' => $user->id]),
                'client'     => ClientProfile::create(['user_id' => $user->id]),
                default      => null,
            };

            return $user;
        });

        event(new Registered($user));

        return response()->json([
            'data'    => ['user' => $user->only('id', 'name', 'email', 'role', 'created_at')],
            'message' => 'Account created. Check your email to verify.',
        ], 201);
    }
}
