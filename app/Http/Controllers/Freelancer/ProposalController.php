<?php

namespace App\Http\Controllers\Freelancer;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Proposal;
use App\Notifications\NewProposalReceived;
use App\Services\ProposalService;
use Illuminate\Http\Request;

class ProposalController extends Controller
{
    public function __construct(private ProposalService $service) {}

    public function store(Request $request, Project $project)
    {
        abort_if($project->status !== 'open', 422, 'This project is no longer accepting proposals.');
        abort_if(
            Proposal::where('project_id', $project->id)->where('freelancer_id', $request->user()->id)->exists(),
            422, 'You have already submitted a proposal for this project.'
        );

        $data = $request->validate([
            'cover_letter'  => ['required', 'string', 'min:50'],
            'bid_amount'    => ['required', 'numeric', 'min:1'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $proposal = $this->service->store($request->user(), $project->id, $data);

        $project->client->notify(new NewProposalReceived(
            $request->user()->name,
            $project->title
        ));

        return response()->json([
            'data'    => $proposal->load('freelancer:id,name,avatar_path'),
            'message' => 'Proposal submitted.',
        ], 201);
    }

    public function update(Request $request, Proposal $proposal)
    {
        $this->authorize('update', $proposal);

        $data = $request->validate([
            'cover_letter'  => ['sometimes', 'string', 'min:50'],
            'bid_amount'    => ['sometimes', 'numeric', 'min:1'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $proposal->update($data);

        return response()->json(['data' => $proposal->fresh(), 'message' => 'Proposal updated.']);
    }

    public function destroy(Request $request, Proposal $proposal)
    {
        $this->authorize('withdraw', $proposal);

        $proposal->update(['status' => 'withdrawn']);

        return response()->json(['message' => 'Proposal withdrawn.']);
    }

    public function mine(Request $request)
    {
        $proposals = Proposal::where('freelancer_id', $request->user()->id)
            ->with(['project:id,title,status,budget_type,budget_min,budget_max,client_id'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return response()->json($proposals);
    }
}
