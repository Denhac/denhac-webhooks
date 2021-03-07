<?php

namespace App\Slack\Events;


interface EventInterface
{
    public static function eventType(): string;
}
