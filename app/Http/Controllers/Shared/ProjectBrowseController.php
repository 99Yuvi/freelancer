<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectBrowseController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::open()
            ->with(['category:id,name', 'skills:id,name', 'client:id,name,avatar_path'])
            ->withCount('proposals');

        if ($q = $request->q) {
            $query->where(fn($w) =>
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('description', 'like', "%{$q}%")
            );
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->skills) {
            $ids = array_map('intval', (array) $request->skills);
            $query->whereHas('skills', fn($s) => $s->whereIn('skills.id', $ids));
        }

        if ($request->budget_type) {
            $query->where('budget_type', $request->budget_type);
        }

        if ($request->budget_min) {
            $query->where('budget_max', '>=', $request->budget_min);
        }

        $query->orderBy(match ($request->sort) {
            'budget_high' => 'budget_max',
            'budget_low'  => 'budget_min',
            default       => 'created_at',
        }, $request->sort === 'budget_low' ? 'asc' : 'desc');

        // Increment view count on single view
        return response()->json($query->paginate(20));
    }

    public function show(Request $request, Project $project)
    {
        abort_if($project->visibility === 'invite_only' && !auth()->check(), 403);

        $project->increment('view_count');

        return response()->json([
            'data' => $project->load(['category', 'skills', 'client:id,name,avatar_path']),
        ]);
    }
}
