<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentMonitorController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with([
            'contract.project:id,title',
            'client:id,name',
            'freelancer:id,name',
            'milestone:id,title',
        ])
        ->when($request->status, fn($q, $v) => $q->where('status', $v))
        ->when($request->from,   fn($q, $v) => $q->whereDate('created_at', '>=', $v))
        ->when($request->to,     fn($q, $v) => $q->whereDate('created_at', '<=', $v))
        ->latest();

        if ($request->get('export') === 'csv') {
            return $this->exportCsv($query->get());
        }

        return response()->json($query->paginate(25));
    }

    private function exportCsv($payments)
    {
        $rows  = [['ID', 'Project', 'Client', 'Freelancer', 'Amount', 'Commission', 'Net', 'Status', 'Date']];

        foreach ($payments as $p) {
            $rows[] = [
                $p->id,
                $p->contract?->project?->title,
                $p->client?->name,
                $p->freelancer?->name,
                $p->amount,
                $p->commission_amount,
                $p->net_amount,
                $p->status,
                $p->captured_at?->format('Y-m-d'),
            ];
        }

        $csv = implode("\n", array_map(fn($r) => implode(',', array_map(fn($c) => '"' . addslashes($c) . '"', $r)), $rows));

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="operalyn_payments.csv"',
        ]);
    }
}
