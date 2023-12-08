<?php

namespace App\Actions;

use Spatie\QueueableAction\QueueableAction;

trait StaticAction
{
    use QueueableAction;

    public static function queue(string $queue = null): static
    {
        return app(static::class)->onQueue($queue);
    }

    /**
     * @param  string|null  $queue
     */
    public static function now(): static
    {
        return app(static::class);
    }
}
