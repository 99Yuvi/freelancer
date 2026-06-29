<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $contracts = Contract::query()->forUser($user)
            ->with([
                'project:id,title,status',
                'client:id,name,avatar_path',
                'freelancer:id,name,avatar_path',
                'milestones:id,contract_id,title,amount,status,sort_order',
            ])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return response()->json($contracts);
    }

    public function show(Request $request, Contract $contract)
    {
        $this->authorize('view', $contract);

        $contract->load([
            'project:id,title,description,status,budget_type',
            'client:id,name,avatar_path',
            'freelancer:id,name,avatar_path',
            'freelancer.freelancerProfile:id,user_id,headline,rating_avg',
            'milestones.deliveries.files',
            'milestones.payment:id,milestone_id,status,amount,captured_at',
            'conversation:id,contract_id',
        ]);

        return response()->json(['data' => $contract]);
    }
}
