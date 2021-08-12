<?php

namespace App\Actions\Google;

use App\Actions\StaticAction;
use App\Google\GoogleApi;
use Spatie\QueueableAction\QueueableAction;

class RemoveFromGroup
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
        $this->googleApi->group($group)->remove($email);
    }
}
