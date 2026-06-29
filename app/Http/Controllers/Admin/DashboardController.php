<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\FreelancerProfile;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $now   = now();
        $month = $now->startOfMonth();

        return response()->json(['data' => [
            'total_users'           => User::count(),
            'total_freelancers'     => User::where('role', 'freelancer')->count(),
            'total_clients'         => User::where('role', 'client')->count(),
            'pending_verifications' => FreelancerProfile::where('verification_status', 'pending')->count(),
            'active_projects'       => Project::where('status', 'open')->count(),
            'active_contracts'      => Contract::where('status', 'active')->count(),
            'gmv_total'             => (float) Payment::where('status', 'captured')->sum('amount'),
            'commission_total'      => (float) Payment::where('status', 'captured')->sum('commission_amount'),
            'gmv_this_month'        => (float) Payment::where('status', 'captured')
                                            ->whereMonth('captured_at', $now->month)
                                            ->whereYear('captured_at', $now->year)
                                            ->sum('amount'),
            'new_users_this_month'  => User::whereDate('created_at', '>=', now()->startOfMonth())->count(),
        ]]);
    }
}
