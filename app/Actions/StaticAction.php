<?php

namespace App\Actions;


use Spatie\QueueableAction\QueueableAction;

trait StaticAction
{
    use QueueableAction;
    /**
     * @param string|null $queue
     * @return static
     */
    public static function queue(?string $queue = null) {
        return app(static::class)->onQueue($queue);
    }

    /**
     * @param string|null $queue
     * @return static
     */
    public static function now() {
        return app(static::class);
    }
}
