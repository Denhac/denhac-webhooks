<?php

namespace App\Http\Controllers;

use App\Aggregates\MembershipAggregate;
use App\Http\Resources\CardUpdateRequestResource;
use App\Models\ActiveCardHolderUpdate;
use App\Models\CardUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CardUpdateRequestsController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CardUpdateRequestResource::collection(CardUpdateRequest::with('customer')->get());
    }

    public function updateStatus(Request $request, CardUpdateRequest $cardUpdateRequest)
    {
        $status = $request->json('status');

        try {
            MembershipAggregate::make($cardUpdateRequest->customer_id)
                ->updateCardStatus($cardUpdateRequest, $status)
                ->persist();
        } catch (\Throwable $t) {
            // Swallow the error, the client can't handle it
            report($t);
        }
    }

    public function updateActiveCardHolders(Request $request): JsonResponse
    {
        // TODO Make this a validation request
        if (! $request->has('card_holders')) {
            return response()->json([
                'error' => 'Missing argument card_holders',
            ]);
        }

        $parameterBag = $request->json('card_holders');
        ActiveCardHolderUpdate::create([
            'card_holders' => $parameterBag,
        ]);
    }
}
