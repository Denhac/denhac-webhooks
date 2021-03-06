<?php

namespace App\Slack\Modals;

use App\Slack\SlackApi;
use ReflectionClass;

trait ModalTrait
{
    public static function getModal($callbackId)
    {
        return collect(get_declared_classes())
            ->filter(function ($name) use ($callbackId) {
                if (strpos($name, 'App\\Slack\\Modals') !== 0) {
                    return false;
                }

                $reflect = new ReflectionClass($name);
                if (! $reflect->implementsInterface(\App\Slack\Modals\ModalInterface::class)) {
                    return false;
                }

                $classTraits = collect(array_keys($reflect->getTraits()));
                if (! $classTraits->contains(\App\Slack\Modals\ModalTrait::class)) {
                    return false;
                }

                if ($name::callbackId() !== $callbackId) {
                    return false;
                }

                return true;
            })
            ->first();
    }

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

        $slackApi->views_open($trigger_id, $this);
    }

    protected static function clearViewStack()
    {
        return response()->json([
            'response_action' => 'clear',
        ]);
    }
}
