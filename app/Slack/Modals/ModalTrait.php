<?php

namespace App\Slack\Modals;

use App\Http\Requests\SlackRequest;
use App\Slack\SlackApi;
use ReflectionClass;

trait ModalTrait
{

    public function push()
    {
        return response()->json([
            'response_action' => 'push',
            'view' => $this,
        ]);
    }

    public function update()
    {
        return response()->json([
            'response_action' => 'update',
            'view' => $this,
        ]);
    }

    public function open($trigger_id)
    {
        /** @var SlackApi $slackApi */
        $slackApi = app(SlackApi::class);

        return $slackApi->views->open($trigger_id, $this);
    }

    protected static function clearViewStack()
    {
        return response()->json([
            'response_action' => 'clear',
        ]);
    }

    /**
     * Get state values from the slack request if there are any.
     *
     * @param SlackRequest $request
     */
    protected static function getStateValues(SlackRequest $request): array
    {
        $payload = $request->payload();

        if (is_null($payload)) {
            return [];
        }

        if (!array_key_exists('view', $payload)) {
            return [];
        }
        $view = $payload['view'];

        if (!array_key_exists('state', $view)) {
            return [];
        }
        $state = $view['state'];

        if (!array_key_exists('values', $state)) {
            return [];
        }
        $values = $state['values'];

        $result = [];

        foreach ($values as $blockId => $blockValues) {
            $result[$blockId] = [];
            foreach ($blockValues as $actionId => $actionValues) {
                if (array_key_exists('selected_option', $actionValues)) {
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
