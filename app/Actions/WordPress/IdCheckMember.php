<?php

namespace App\Actions\WordPress;

use App\External\WooCommerce\Api\WooCommerceApi;
use Carbon\Carbon;
use Spatie\QueueableAction\QueueableAction;

class IdCheckMember
{
    use QueueableAction;

    public function __construct(private WooCommerceApi $wooCommerceApi)
    {
    }

    public function execute(
        int $customerId,
        string $firstName,
        string $lastName,
        string $card,
        Carbon $birthday,
        int $idCheckerId,
    )
    {
        $this->wooCommerceApi->customers
            ->update($customerId, [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'meta_data' => [
                    [
                        'key' => 'access_card_number',
                        'value' => $card,
                    ],
                    [
                        'key' => 'account_birthday',
                        'value' => $birthday->format('Y-m-d'),
                    ],
                    [
                        'key' => 'id_was_checked_by',
                        'value' => $idCheckerId,
                    ],
                    [
                        'key' => 'id_was_checked_when',
                        'value' => Carbon::now(),
                    ],
                    [
                        'key' => 'id_was_checked',
                        'value' => true,
                    ],
                ],
            ]);
    }
}
