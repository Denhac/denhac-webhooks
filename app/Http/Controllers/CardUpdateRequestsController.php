<?php

namespace App\Http\Controllers;

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
}
