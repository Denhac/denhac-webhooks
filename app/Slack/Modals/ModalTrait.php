<?php

namespace App\Slack\Modals;

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

        return $slackApi->views_open($trigger_id, $this);
    }

    protected static function clearViewStack()
    {
        return response()->json([
            'response_action' => 'clear',
        ]);
    }
}
