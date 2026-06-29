<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(['data' => $request->user()->clientProfile]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name' => ['nullable', 'string', 'max:120'],
            'website'      => ['nullable', 'url', 'max:255'],
            'industry'     => ['nullable', 'string', 'max:80'],
            'location'     => ['nullable', 'string', 'max:100'],
            'bio'          => ['nullable', 'string', 'max:1000'],
        ]);

        $profile = $request->user()->clientProfile;
        $profile->update($validated);

        return response()->json([
            'data'    => $profile->fresh(),
            'message' => 'Profile updated.',
        ]);
    }
}
