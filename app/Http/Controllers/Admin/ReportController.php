<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function overview(Request $request)
    {
        $from = $request->get('from', now()->subDays(30)->toDateString());
        $to   = $request->get('to',   now()->toDateString());

        // Daily user registrations
        $userGrowth = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Daily project postings
        $projectVolume = Project::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Revenue by category
        $revByCategory = Payment::where('payments.status', 'captured')
            ->whereBetween('payments.captured_at', [$from, $to])
            ->join('contracts', 'payments.contract_id', '=', 'contracts.id')
            ->join('projects',  'contracts.project_id', '=', 'projects.id')
            ->join('categories','projects.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as category, SUM(payments.amount) as total')
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get();

        // Top freelancers by earnings
        $topFreelancers = User::where('role', 'freelancer')
            ->join('freelancer_profiles', 'users.id', '=', 'freelancer_profiles.user_id')
            ->orderByDesc('freelancer_profiles.total_earnings')
            ->select('users.id', 'users.name', 'freelancer_profiles.total_earnings', 'freelancer_profiles.rating_avg')
            ->limit(10)
            ->get();

        return response()->json(['data' => [
            'user_growth'     => $userGrowth,
            'project_volume'  => $projectVolume,
            'rev_by_category' => $revByCategory,
            'top_freelancers' => $topFreelancers,
            'from'            => $from,
            'to'              => $to,
        ]]);
    }
}
