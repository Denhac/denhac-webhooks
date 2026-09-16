<?php

namespace App\Http\Controllers;

use App\Aggregates\MembershipAggregate;
use App\Http\Resources\CardUpdateRequestResource;
use App\Models\ActiveCardHolderUpdate;
use App\Models\CardUpdateRequest;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CardUpdateRequestsController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CardUpdateRequestResource::collection(CardUpdateRequest::with('customer')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'updates' => ['present', 'array'],
        ]);

        foreach ($validated['updates'] as $update) {
            try {
                $this->recordCardStatus($update);
            } catch (Throwable $t) {
                report($t);
            }
        }

        return response()->json();
    }

    private function recordCardStatus(mixed $update): void
    {
        $validator = Validator::make(is_array($update) ? $update : [], [
            'id' => ['sometimes', 'nullable', 'integer'],
            'woo_id' => ['required', 'integer', 'min:1'],
            'card' => ['required'],
            'active' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            throw new Exception('Card status update was malformed: '.$validator->errors()->toJson());
        }

        $validated = $validator->validated();

        MembershipAggregate::make($validated['woo_id'])
            ->recordCardStatus((string) $validated['card'], $validated['active'])
            ->persist();
    }

    /**
     * The status of one card update request. There is nothing in the body: the only state this endpoint can offer is
     * the request's own type, which says what we asked for rather than what the card access system did. Superseded by
     * store(), where the reported state arrives with the report.
     */
    public function updateStatus(CardUpdateRequest $cardUpdateRequest)
    {
        try {
            $active = match ($cardUpdateRequest->type) {
                CardUpdateRequest::ACTIVATION_TYPE => true,
                CardUpdateRequest::DEACTIVATION_TYPE => false,
                default => throw new Exception(
                    "Card update request type wasn't one of the expected values: $cardUpdateRequest->type"
                ),
            };

            MembershipAggregate::make($cardUpdateRequest->customer_id)
                ->recordCardStatus($cardUpdateRequest->card, $active)
                ->persist();
        } catch (Throwable $t) {
            // Swallow the error, the client can't handle it
            report($t);
        }
    }

    public function updateActiveCardHolders(Request $request): JsonResponse
    {
        // TODO Make this a validation request
        if (! $request->has('card_holders')) {
            return response(status: StatusCodeInterface::STATUS_BAD_REQUEST)->json([
                'error' => 'Missing argument card_holders',
            ]);
        }

        $parameterBag = $request->json('card_holders');
        ActiveCardHolderUpdate::create([
            'card_holders' => $parameterBag,
        ]);

        return response()->json();
    }
}
