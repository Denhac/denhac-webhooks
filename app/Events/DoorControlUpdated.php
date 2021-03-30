<?php

namespace App\Events;

use App\WinDSX\Door;
use Carbon\Carbon;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class DoorControlUpdated implements ShouldBroadcast
{
    use Dispatchable;

    public int $duration;
    public array $doors;

    /**
     * Open requests close when this expires. Close requests stay closed when this expires. This allows the caller to
     * have a mix of open and close door requests at the same time while knowing that the door won't suddenly open at
     * some point in the future. New updates on the same device override the previous update.
     *
     * The reason an int is allowed for expires is in the event we want to say "hold this door open for 5 seconds". If
     * that event takes 1 second to get to our Pi and the clocks are enough by 3 seconds, the door will only open for 1
     * second. This has the tradeoff that Carbon times like "close at 11:00 PM" might be a few seconds off.
     *
     * @param Carbon|int $expires When does this update expire. Either as a date or in seconds.
     * @param Door ...$doors The list of door objects that we want to update.
     */
    public function __construct(Carbon|int $expires, Door ...$doors)
    {
        if (is_int($expires)) {
            $this->duration = $expires;
        } else {
            $this->duration = $expires->getTimestamp() - now()->getTimestamp();
        }

        $this->doors = [];
        foreach($doors as $door) {
            $this->doors[] = $door->toRelay();
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return PrivateChannel
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('doors');
    }
}
