<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Proposal;
use App\Notifications\ProposalRejected;
use App\Notifications\ProposalShortlisted;
use App\Services\ContractService;
use Illuminate\Http\Request;

class ProposalController extends Controller
{
    public function __construct(private ContractService $contractService) {}

    public function index(Request $request, Project $project)
    {
        $this->authorize('viewProposals', $project);

        $proposals = $project->proposals()
            ->with([
                'freelancer:id,name,avatar_path',
                'freelancer.freelancerProfile:id,user_id,headline,hourly_rate,rating_avg,rating_count,verification_status',
            ])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy(match ($request->sort) {
                'bid_low'  => 'bid_amount',
                'bid_high' => 'bid_amount',
                default    => 'created_at',
            }, $request->sort === 'bid_high' ? 'desc' : 'asc')
            ->paginate(20);

        return response()->json($proposals);
    }

    public function shortlist(Request $request, Proposal $proposal)
    {
        $this->authorize('shortlist', $proposal);
        $proposal->update(['status' => 'shortlisted']);

        $proposal->freelancer->notify(new ProposalShortlisted(
            $proposal->project->title
        ));

        return response()->json(['message' => 'Proposal shortlisted.', 'data' => $proposal->fresh()]);
    }

    public function reject(Request $request, Proposal $proposal)
    {
        $this->authorize('reject', $proposal);

        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $proposal->update([
            'status'          => 'rejected',
            'rejected_reason' => $request->reason,
        ]);

        $proposal->freelancer->notify(new ProposalRejected(
            $proposal->project->title
        ));

        return response()->json(['message' => 'Proposal rejected.']);
    }

    public function accept(Request $request, Proposal $proposal)
    {
        $this->authorize('accept', $proposal);

        $contract = $this->contractService->createFromProposal($proposal);

        return response()->json([
            'data'    => ['contract_id' => $contract->id],
            'message' => 'Freelancer hired. Contract is now active.',
        ], 201);
    }
}
