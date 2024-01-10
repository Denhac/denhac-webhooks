<?php

namespace App\Http\Controllers;

use App\External\Slack\ClassFinder;
use App\External\Slack\SlackOptions;
use App\Http\Requests\SlackRequest;

class SlackOptionsController extends Controller
{
    public function __invoke(SlackRequest $request)
    {
        $payload = $request->payload();

        if ($payload['type'] == 'block_suggestion') {
            return $this->blockSuggestion($request);
        }

        throw new \Exception('Slack options payload has unknown type');
    }

    private function blockSuggestion(SlackRequest $request)
    {
        $payload = $request->payload();

        $callback_id = $payload['view']['callback_id'];

        $modalClass = ClassFinder::getModal($callback_id);
        if (is_null($modalClass)) {
            throw new \Exception("Slack options payload has unknown callback id: $callback_id");
        }

        $options = $modalClass::getOptions($request);

        if (is_a($options, SlackOptions::class)) {
            $value = $request->payload()['value'] ?? null;
            $options->filterByValue($value);
        }

        return $options;
    }
}
