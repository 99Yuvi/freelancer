<?php

namespace App\Listeners;

use App\Events\ContractCreated;
use App\Models\Conversation;

class CreateConversationOnContractCreated
{
    public function handle(ContractCreated $event): void
    {
        $contract = $event->contract;

        Conversation::firstOrCreate(
            ['contract_id' => $contract->id],
            ['client_id' => $contract->client_id, 'freelancer_id' => $contract->freelancer_id]
        );
    }
}
