<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\MilestoneDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DeliveryFileController extends Controller
{
    /** Return a signed 1-hour URL for a private delivery file */
    public function download(Request $request, MilestoneDelivery $delivery, int $fileId)
    {
        $file = $delivery->files()->findOrFail($fileId);

        // Only parties to the contract may download
        $contract = $delivery->milestone->contract;
        abort_unless(
            $request->user()->id === $contract->client_id ||
            $request->user()->id === $contract->freelancer_id,
            403
        );

        $filename = $file->original_name ?? basename($file->file_path);
        $mime     = $file->mime_type ?? 'application/octet-stream';

        return response()->stream(function () use ($file) {
            echo Storage::disk('private')->get($file->file_path);
        }, 200, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }
}
