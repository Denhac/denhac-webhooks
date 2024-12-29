<?php

namespace App\External\Slack\Modals;

use App\External\Slack\SlackApi;
use App\Http\Requests\SlackRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use SlackPhp\BlockKit\Previewer;
use SlackPhp\BlockKit\Surfaces\Modal;

trait ModalTrait
{
    private Modal $modalView;

    public function jsonSerialize(): array
    {
        $this->modalView->validate();

        return $this->modalView->jsonSerialize();
    }

    /**
     * This is for debugging so you can see what your modal looks like in the Slack block kit builder.
     *
     * @return string Block kit url string
     */
    public function preview(): string
    {
        // We have to validate it here again, but most places validate it directly through jsonSerialize.
        $this->modalView->validate();

        return Previewer::new()->preview($this->modalView);
    }

    public function push(): JsonResponse
    {
        return response()->json([
            'response_action' => 'push',
            'view' => $this,
        ]);
    }

    /**
     * This method can't be used except on "Submit" so it can't be used in a block action response. Use updateViaApi
     * instead.
     *
     * See https://api.slack.com/surfaces/modals/using#updating_response
     */
    public function update(): JsonResponse
    {
        return response()->json([
            'response_action' => 'update',
            'view' => $this,
        ]);
    }

    /**
     * This method uses the slack api to respond and handles the view id/hash as well as returning an OK for the api
     * to all be happy. You MUST use this method in response to a block action which is why the request is passed in.
     *
     * If you need to call update on an existing view id, just use the SlackApi class directly.
     */
    public function updateViaApi(SlackRequest $request): Response
    {
        $payload = $request->payload();
        $view = $payload['view'];
        $view_id = $view['id'];
        $view_hash = $view['hash'];

        /** @var SlackApi $api */
        $api = app(SlackApi::class);
        $api->views->update($view_id, $this, $view_hash);

        return response('');
    }

    public function open($trigger_id)
    {
        /** @var SlackApi $slackApi */
        $slackApi = app(SlackApi::class);

        return $slackApi->views->open($trigger_id, $this);
    }

    protected static function clearViewStack(): JsonResponse
    {
        return response()->json([
            'response_action' => 'clear',
        ]);
    }

    /**
     * Get state values from the slack request if there are any.
     */
    protected static function getStateValues(SlackRequest $request): array
    {
        $payload = $request->payload();

        if (is_null($payload)) {
            return [];
        }

        if (! array_key_exists('view', $payload)) {
            return [];
        }
        $view = $payload['view'];

        if (! array_key_exists('state', $view)) {
            return [];
        }
        $state = $view['state'];

        if (! array_key_exists('values', $state)) {
            return [];
        }
        $values = $state['values'];

        $result = [];

        foreach ($values as $blockId => $blockValues) {
            $result[$blockId] = [];
            foreach ($blockValues as $actionId => $actionValues) {
                if ($actionValues['type'] == 'checkboxes') {
                    // Checkboxes are kind of weird with Slack. Instead of just true/false,
                    // their values are either there or not. So we return a dictionary of values.
                    $selectedOptions = $actionValues['selected_options'] ?? [];

                    if (empty($selectedOptions)) {
                        continue; // No selected options
                    }

                    $result[$blockId][$actionId] = [];
                    foreach ($selectedOptions as $option) {
                        $result[$blockId][$actionId][] = $option['value'];
                    }
                } elseif ($actionValues['type'] == 'multi_external_select') {
                    $selected = [];
                    foreach ($actionValues['selected_options'] as $selectedOption) {
                        $selected[] = $selectedOption['value'];
                    }
                    $result[$blockId][$actionId] = $selected;
                } elseif (array_key_exists('selected_option', $actionValues)) {
                    $selected = $actionValues['selected_option'];
                    if (empty($selected)) {
                        $result[$blockId][$actionId] = null;
                    } else {
                        $result[$blockId][$actionId] = $selected['value'];
                    }
                }
            }
        }

        return $result;
    }
}
