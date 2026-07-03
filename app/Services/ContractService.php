<?php

namespace App\Services;

use App\Events\ContractCreated;
use App\Models\Contract;
use App\Models\Proposal;
use App\Models\Setting;
use App\Notifications\ContractCreated as ContractCreatedNotification;
use Illuminate\Support\Facades\DB;

class ContractService
{
    public function __construct(private ProposalService $proposalService) {}

    public function createFromProposal(Proposal $proposal): Contract
    {
        return DB::transaction(function () use ($proposal) {
            $proposal = \App\Models\Proposal::lockForUpdate()->find($proposal->id);

            // Idempotent: if this proposal was already accepted, return the existing contract
            if ($proposal->status === 'accepted') {
                return Contract::where('proposal_id', $proposal->id)->firstOrFail();
            }

            $contract = Contract::create([
                'proposal_id'    => $proposal->id,
                'project_id'     => $proposal->project_id,
                'client_id'      => $proposal->project->client_id,
                'freelancer_id'  => $proposal->freelancer_id,
                'total_amount'   => $proposal->bid_amount,
                'commission_rate'=> Setting::get('commission_rate', 12.00),
                'status'         => 'active',
                'started_at'     => now(),
            ]);

            $proposal->update(['status' => 'accepted']);
            $this->proposalService->rejectOthers($proposal);
            $proposal->project->update(['status' => 'in_progress']);

            event(new ContractCreated($contract));

            $contract->freelancer->notify(new ContractCreatedNotification(
                $proposal->project->title
            ));

            return $contract;
        });
    }
}
