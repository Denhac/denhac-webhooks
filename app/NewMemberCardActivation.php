<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string wooCustomerId
 * @property int cardNumber
 * @property string state
 */
class NewMemberCardActivation extends Model
{
    public const SUBMITTED = 'submitted';
    public const CARD_SENT_FOR_ACTIVATION = 'card-sent-for-activation';
    public const CARD_ACTIVATED = 'card-activated';
    public const SCAN_FAILED = 'scan-failed';
    public const SUCCESS = 'success';


    protected $fillable = [
        'woo_customer_id',
        'card_number',
        'state',
    ];

    public static function search($customer, $card): ?NewMemberCardActivation
    {
        if($customer instanceof Customer) {
            $customer = $customer->id;
        }
        if($card instanceof Card) {
            $card = $card->number;
        }

        return NewMemberCardActivation::where('woo_customer_id', $customer)
            ->where('card_number', ltrim($card, '0'))
            ->first();
    }
}
