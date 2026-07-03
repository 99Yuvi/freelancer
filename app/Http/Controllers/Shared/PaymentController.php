<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $payments = Payment::where(
            $user->isClient() ? 'client_id' : 'freelancer_id',
            $user->id
        )
        ->with([
            'contract.project:id,title',
            $user->isClient() ? 'freelancer:id,name' : 'client:id,name',
            'milestone:id,title',
        ])
        ->when($request->status, fn($q) => $q->where('status', $request->status))
        ->latest('captured_at')
        ->paginate(20);

        return response()->json($payments);
    }

    public function show(Request $request, Payment $payment)
    {
        abort_unless(
            $request->user()->id === $payment->client_id ||
            $request->user()->id === $payment->freelancer_id,
            403
        );

        return response()->json(['data' => $payment->load(['contract.project:id,title', 'milestone:id,title'])]);
    }

    public function invoice(Request $request, Payment $payment)
    {
        abort_unless(
            $request->user()->id === $payment->client_id ||
            $request->user()->id === $payment->freelancer_id,
            403
        );

        abort_unless($payment->invoice_path, 404, 'Invoice not yet generated.');

        $filename = basename($payment->invoice_path);

        return response()->stream(function () use ($payment) {
            echo Storage::disk('private')->get($payment->invoice_path);
        }, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }
}
