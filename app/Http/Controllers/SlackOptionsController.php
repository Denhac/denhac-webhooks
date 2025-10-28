<?php

namespace App\Http\Controllers;

use App\External\Slack\ClassFinder;
use App\External\Slack\Modals\HasExternalOptions;
use App\Http\Requests\SlackRequest;
use ReflectionClass;
use SlackPhp\BlockKit\Collections\OptionGroupCollection;
use SlackPhp\BlockKit\Collections\OptionSet;
use SlackPhp\BlockKit\Parts\OptionGroup;
use SlackPhp\BlockKit\Surfaces\OptionsResult;

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

        /** @var HasExternalOptions $modalClassName */

        $optionsResult = $modalClassName::getExternalOptions($request);

        $this->limitOptionsResults($optionsResult);

        return $optionsResult;
    }

    private function limitOptionsResults(OptionsResult $optionsResult): void
    {
        // We can only have 100 top level options.
        $optionsResult->options = $this->limitOptionSet($optionsResult->options);

        $optionGroups = $optionsResult->optionGroups;
        // We can only have 100 option groups, with 100 options each.
        if($optionGroups->count() > 100) {
            $newOptionGroups = OptionGroupCollection::new();

            $counter = 0;
            foreach($optionGroups->getIterator() as $component) {
                $newOptionGroups->append($component);
                $counter++;

                if($counter == 100) {
                    break;
                }
            }

            $optionGroups = $newOptionGroups;
        }

        for ($i = 0; $i < $optionGroups->count(); $i++) {
            /** @var OptionGroup $optionGroup */
            $optionGroup = $optionGroups->offsetGet($i);

            $optionGroup->options = $this->limitOptionSet($optionGroup->options);

            $optionGroups->offsetSet($i, $optionGroup);
        }
    }

    private function limitOptionSet(OptionSet $optionSet): OptionSet
    {
        // If we're below 100, we don't need to limit it
        if($optionSet->count() <= 100) {
            return $optionSet;
        }

        // There doesn't seem to be an easy way to access the internal components, but we can iterate over them.
        $newOptionSet = OptionSet::new();
        $counter = 0;
        foreach($optionSet->getIterator() as $component) {
            $newOptionSet->append($component);
            $counter++;

            if($counter == 100) {
                break;
            }
        }

        return $newOptionSet;
    }
}
