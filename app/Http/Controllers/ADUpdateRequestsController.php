<?php

namespace App\Http\Controllers;

use App\ADUpdateRequest;
use App\Aggregates\MembershipAggregate;
use App\Http\Resources\ADUpdateRequestResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ADUpdateRequestsController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ADUpdateRequestResource::collection(ADUpdateRequest::with('customer')->get());
    }

    public function updateStatus(Request $request, ADUpdateRequest $cardUpdateRequest)
    {
        $status = $request->json('status');

        try {
            MembershipAggregate::make($cardUpdateRequest->customer_id)
                ->updateADStatus($cardUpdateRequest, $status)
                ->persist();
        } catch (\Throwable $t) {
            // Swallow the error, the client can't handle it
            report($t);
        }
    }
}
