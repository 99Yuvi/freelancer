<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\UpdateFreelancerRatingJob;
use App\Models\AuditLog;
use App\Models\FreelancerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        $profiles = FreelancerProfile::with(['user:id,name,email,avatar_path', 'documents'])
            ->when($request->status ?? 'pending', fn($q, $v) => $q->where('verification_status', $v))
            ->latest()
            ->paginate(25);

        return response()->json($profiles);
    }

    public function update(Request $request, FreelancerProfile $profile)
    {
        $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'notes'  => ['required_if:status,rejected', 'nullable', 'string'],
        ]);

        $profile->update([
            'verification_status' => $request->status,
            'verification_notes'  => $request->notes,
        ]);

        AuditLog::record($request->user(), "verification.{$request->status}", $profile->user, [
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => "Verification {$request->status}.",
            'data'    => $profile->fresh(),
        ]);
    }

    /** Stream a private verification document (no redirect → CORS headers included) */
    public function documentUrl(Request $request, FreelancerProfile $profile, int $docId)
    {
        $doc      = $profile->documents()->findOrFail($docId);
        $ext      = strtolower(pathinfo($doc->file_path, PATHINFO_EXTENSION));
        $mimeMap  = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
        $filename = $doc->original_name ?? basename($doc->file_path);
        $mime     = $doc->mime_type ?? $mimeMap[$ext] ?? 'application/octet-stream';

        return response()->stream(function () use ($doc) {
            echo Storage::disk('private')->get($doc->file_path);
        }, 200, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }
}
