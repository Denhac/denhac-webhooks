<?php

namespace App\Actions\Google;

use App\Actions\StaticAction;
use App\External\Google\GoogleApi;
use Spatie\QueueableAction\QueueableAction;

class AddToGroup
{
    use QueueableAction;
    use StaticAction;

    private GoogleApi $googleApi;

    public function __construct(GoogleApi $googleApi)
    {
        $this->googleApi = $googleApi;
    }

    public function execute($email, $group)
    {
        $this->googleApi->group($group)->add($email);
    }
}
