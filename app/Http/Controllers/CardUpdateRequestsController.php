<?php

namespace App\Http\Controllers;

use App\ActiveCardHolderUpdate;
use App\Aggregates\MembershipAggregate;
use App\CardUpdateRequest;
use App\Http\Resources\CardUpdateRequestResource;
use Illuminate\Http\Request;

class CardUpdateRequestsController extends Controller
{
    public function index()
    {
        return CardUpdateRequestResource::collection(CardUpdateRequest::with("customer")->get());
    }

    public function updateStatus(Request $request, CardUpdateRequest $cardUpdateRequest)
    {
        $status = $request->json("status");

        MembershipAggregate::make($cardUpdateRequest->customer_id)
            ->updateCardStatus($cardUpdateRequest, $status)
            ->persist();
    }

    public function updateActiveCardHolders(Request $request)
    {
        // TODO Make this a validation request
        if(!$request->has("card_holders")) {
            return response()->json([
                "error" => "Missing argument card_holders"
            ]);
        }

        $parameterBag = $request->json("card_holders");
//        dd($parameterBag);
        ActiveCardHolderUpdate::create([
            "card_holders" => $parameterBag
        ]);
    }
}
