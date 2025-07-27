<?php

namespace App\Actions\WordPress;

use App\External\WooCommerce\Api\WooCommerceApi;
use Illuminate\Support\Facades\Log;
use Spatie\QueueableAction\QueueableAction;

class AddUserMembership
{
    use QueueableAction;

    public function __construct(
        private readonly WooCommerceApi $wooCommerceApi
    )
    {
    }

    public function execute($actorId, $memberId, $planId): void
    {
        $this->wooCommerceApi->members->addMembership($memberId, $planId);
        Log::info('AddUserMembership: Customer ' . $actorId . ' granted user plan id ' . $planId . ' to Customer ' . $memberId);
    }
}
