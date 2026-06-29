<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::forClient($request->user()->id)
            ->with(['category:id,name', 'skills:id,name'])
            ->withCount('proposals')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return response()->json($projects);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'min:100'],
            'category_id' => ['required', 'exists:categories,id'],
            'skills'      => ['required', 'array', 'min:1', 'max:10'],
            'skills.*'    => ['integer', 'exists:skills,id'],
            'budget_type' => ['required', 'in:fixed,hourly'],
            'budget_min'  => ['nullable', 'numeric', 'min:0'],
            'budget_max'  => ['nullable', 'numeric', 'gte:budget_min'],
            'deadline'    => ['nullable', 'date', 'after:today'],
            'visibility'  => ['nullable', 'in:public,invite_only'],
            'status'      => ['nullable', 'in:draft,open'],
        ]);

        $project = Project::create([
            'client_id'   => $request->user()->id,
            'title'       => $data['title'],
            'description' => $data['description'],
            'category_id' => $data['category_id'],
            'budget_type' => $data['budget_type'],
            'budget_min'  => $data['budget_min'] ?? null,
            'budget_max'  => $data['budget_max'] ?? null,
            'deadline'    => $data['deadline'] ?? null,
            'visibility'  => $data['visibility'] ?? 'public',
            'status'      => $data['status'] ?? 'open',
        ]);

        $project->skills()->sync($data['skills']);

        return response()->json([
            'data'    => $project->load(['category:id,name', 'skills:id,name']),
            'message' => 'Project posted.',
        ], 201);
    }

    public function show(Request $request, Project $project)
    {
        if ($project->client_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json([
            'data' => $project->load(['category', 'skills', 'proposals.freelancer.freelancerProfile']),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'title'       => ['sometimes', 'string', 'max:100'],
            'description' => ['sometimes', 'string', 'min:100'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'skills'      => ['sometimes', 'array', 'min:1', 'max:10'],
            'skills.*'    => ['integer', 'exists:skills,id'],
            'budget_type' => ['sometimes', 'in:fixed,hourly'],
            'budget_min'  => ['nullable', 'numeric', 'min:0'],
            'budget_max'  => ['nullable', 'numeric', 'gte:budget_min'],
            'deadline'    => ['nullable', 'date', 'after:today'],
            'visibility'  => ['nullable', 'in:public,invite_only'],
            'status'      => ['nullable', 'in:draft,open,in_progress,completed,cancelled'],
        ]);

        $project->update(collect($data)->except('skills')->toArray());

        if (isset($data['skills'])) {
            $project->skills()->sync($data['skills']);
        }

        return response()->json([
            'data'    => $project->fresh()->load(['category:id,name', 'skills:id,name']),
            'message' => 'Project updated.',
        ]);
    }

    public function destroy(Request $request, Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json(['message' => 'Project removed.']);
    }
}
