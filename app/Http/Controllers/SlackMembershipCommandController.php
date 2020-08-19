<?php

namespace App\Http\Controllers;

use App\Http\Requests\SlackSlashCommandRequest;
use App\Slack\SlackApi;
use Illuminate\Support\Facades\Log;
use Jeremeamia\Slack\BlockKit\Slack;

class SlackMembershipCommandController extends Controller
{
    public function __invoke(SlackSlashCommandRequest $request, SlackApi $api)
    {
        Log::info($request->getContent());

        $modalView = Slack::newModal()
            ->callbackId("membership-command-modal")
            ->title("What do you want to do?")
            ->submit("Submit");

        $modalView->newInput()
            ->label("Membership Option")
            ->blockId("membership-option")
            ->newSelectMenu()
            ->forExternalOptions()
            ->actionId("text1234")
            ->placeholder("Select an Item")
            ->minQueryLength(0);

//        Log::info(Slack::newRenderer()->forJson()->render($modalView));

        $api->views_open($request->get('trigger_id'), $modalView);

        return response("");
    }
}
