<?php

namespace App\Http\Controllers;

use App\External\Slack\ClassFinder;
use App\External\Slack\Modals\HasExternalOptions;
use App\Http\Requests\SlackRequest;
use ReflectionClass;

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

        $modalClassName = ClassFinder::getModal($callback_id);
        if (is_null($modalClassName)) {
            throw new \Exception("Slack options payload has unknown callback id: $callback_id");
        }
        $reflect = new ReflectionClass($modalClassName);
        if (! array_key_exists(HasExternalOptions::class, $reflect->getTraits())) {
            throw new \Exception('Requested external options from Slack modal that does not implement the external options trait.');
        }

        return $modalClassName::getExternalOptions($request);
    }
}
