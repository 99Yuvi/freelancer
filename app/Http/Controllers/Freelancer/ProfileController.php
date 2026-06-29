<?php

namespace App\Http\Controllers\Freelancer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use App\Models\VerificationDocument;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $profile = $request->user()
            ->freelancerProfile
            ->load(['skills', 'experiences', 'educations', 'certifications', 'portfolio.images']);

        return response()->json(['data' => $profile]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'headline'     => ['nullable', 'string', 'max:120'],
            'bio'          => ['nullable', 'string', 'max:2000'],
            'hourly_rate'  => ['nullable', 'numeric', 'min:0'],
            'availability' => ['nullable', 'in:available,busy,unavailable'],
            'skills'       => ['nullable', 'array'],
            'skills.*'     => ['integer', 'exists:skills,id'],
        ]);

        $profile = $request->user()->freelancerProfile;
        $profile->update(collect($validated)->except('skills')->toArray());

        if (isset($validated['skills'])) {
            $profile->skills()->sync($validated['skills']);
        }

        return response()->json([
            'data'    => $profile->fresh()->load('skills'),
            'message' => 'Profile updated.',
        ]);
    }

    public function uploadResume(Request $request)
    {
        $request->validate([
            'resume' => ['required', File::types(['pdf'])->max(5 * 1024)],
        ]);

        $profile = $request->user()->freelancerProfile;

        // Delete old resume
        if ($profile->resume_path) {
            Storage::disk('private')->delete($profile->resume_path);
        }

        $path = $request->file('resume')->store('resumes/' . $request->user()->id, 'private');
        $profile->update(['resume_path' => $path]);

        return response()->json(['message' => 'Resume uploaded.', 'data' => ['path' => $path]]);
    }

    public function submitVerification(Request $request)
    {
        $request->validate([
            'id_front' => ['sometimes', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
            'id_back'  => ['sometimes', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
            'selfie'   => ['sometimes', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
        ]);

        $profile = $request->user()->freelancerProfile;

        if ($profile->verification_status === 'approved') {
            return response()->json(['message' => 'Already verified.'], 422);
        }

        foreach (['id_front', 'id_back', 'selfie'] as $docType) {
            if (!$request->hasFile($docType)) continue;

            $existing = $profile->documents()->where('doc_type', $docType)->first();
            if ($existing) {
                Storage::disk('private')->delete($existing->file_path);
                $existing->delete();
            }

            $path = $request->file($docType)->store("verification/{$profile->id}", 'private');
            $profile->documents()->create(['doc_type' => $docType, 'file_path' => $path]);
        }

        if ($profile->verification_status === 'unsubmitted') {
            $profile->update(['verification_status' => 'pending']);
        }

        return response()->json([
            'message' => 'Documents submitted for review.',
            'data'    => $profile->fresh()->load('documents'),
        ]);
    }
}
