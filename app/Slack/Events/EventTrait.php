<?php

namespace App\Slack\Events;

use App\Slack\SlackApi;
use ReflectionClass;

trait EventTrait
{
    public static function getEvent($eventType)
    {
        return collect(get_declared_classes())
            ->filter(function ($name) use ($eventType) {
                if (!str_starts_with($name, 'App\\Slack\\Events')) {
                    return false;
                }

                $reflect = new ReflectionClass($name);
                if (! $reflect->implementsInterface(\App\Slack\Events\EventInterface::class)) {
                    return false;
                }

                $classTraits = collect(array_keys($reflect->getTraits()));
                if (! $classTraits->contains(\App\Slack\Events\EventTrait::class)) {
                    return false;
                }

                if ($name::eventType() !== $eventType) {
                    return false;
                }

                return true;
            })
            ->first();
    }
}
