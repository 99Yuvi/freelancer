<?php

namespace App\Jobs;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(private readonly int $paymentId) {}

    public function handle(): void
    {
        $payment = Payment::with([
            'contract.project:id,title',
            'client:id,name',
            'client.clientProfile:user_id,company_name',
            'freelancer:id,name',
        ])->findOrFail($this->paymentId);

        if (!$payment->isCaptured()) return;

        $year          = now()->year;
        $invoiceNumber = 'INV-' . $year . '-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);

        $html = view('invoices.payment', [
            'payment'       => $payment,
            'invoiceNumber' => $invoiceNumber,
            'issuedDate'    => $payment->captured_at->format('d M Y'),
        ])->render();

        $pdf  = Pdf::loadHTML($html)->setPaper('a4');
        $path = "invoices/{$year}/{$invoiceNumber}.pdf";

        Storage::disk('private')->put($path, $pdf->output());

        $payment->update(['invoice_path' => $path]);
    }
}
