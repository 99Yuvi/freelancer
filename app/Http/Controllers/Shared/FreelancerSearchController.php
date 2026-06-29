<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\FreelancerProfile;
use App\Models\User;
use Illuminate\Http\Request;

class FreelancerSearchController extends Controller
{
    public function index(Request $request)
    {
        $query = FreelancerProfile::with(['user:id,name,avatar_path', 'skills:id,name'])
            ->where('verification_status', 'approved')
            ->whereHas('user', fn($u) => $u->where('status', 'active'));

        if ($q = $request->q) {
            $query->where(fn($w) =>
                $w->where('headline', 'like', "%{$q}%")
                  ->orWhere('bio', 'like', "%{$q}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$q}%"))
            );
        }

        if ($request->skills) {
            $ids = array_map('intval', (array) $request->skills);
            $query->whereHas('skills', fn($s) => $s->whereIn('skills.id', $ids));
        }

        if ($request->availability) {
            $query->where('availability', $request->availability);
        }

        if ($request->rate_min) {
            $query->where('hourly_rate', '>=', $request->rate_min);
        }

        if ($request->rate_max) {
            $query->where('hourly_rate', '<=', $request->rate_max);
        }

        $query->orderBy(match ($request->sort) {
            'rating'     => 'rating_avg',
            'rate_asc'   => 'hourly_rate',
            'rate_desc'  => 'hourly_rate',
            default      => 'rating_avg',
        }, $request->sort === 'rate_asc' ? 'asc' : 'desc');

        return response()->json($query->paginate(20));
    }

    public function show(User $user)
    {
        abort_if(!$user->isFreelancer(), 404);

        $profile = $user->freelancerProfile()->with([
            'skills', 'portfolio.images', 'experiences', 'educations', 'certifications',
        ])->firstOrFail();

        $reviews = \App\Models\Review::where('reviewee_id', $user->id)
            ->public()
            ->with('reviewer:id,name,avatar_path')->latest()->take(10)->get();

        return response()->json([
            'data' => [
                'user'    => $user->only('id', 'name', 'avatar_path'),
                'profile' => $profile,
                'reviews' => $reviews,
            ],
        ]);
    }
}
