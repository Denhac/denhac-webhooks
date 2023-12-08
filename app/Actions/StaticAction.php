<?php

namespace App\Actions;

use Spatie\QueueableAction\QueueableAction;

trait StaticAction
{
    use QueueableAction;

    /**
     * Note: We specify a return of static here so we get the type hint of the execute method for our action. In reality,
     * we're returning an anonymous class. Hence the DocBlock returning static but the actual return type of object.
     *
     * @param string|null $queue
     * @return static
     */
    public static function queue(string $queue = null): object
    {
        return app(static::class)->onQueue($queue);
    }

    public static function now(): static
    {
        return app(static::class);
    }
}
