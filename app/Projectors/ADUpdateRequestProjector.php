<?php

namespace App\Projectors;

use App\CardUpdateRequest;
use App\StorableEvents\ADUserToBeEnabled;
use App\StorableEvents\ADUserToBeDisabled;
use Spatie\EventSourcing\Projectors\Projector;
use Spatie\EventSourcing\Projectors\ProjectsEvents;

class ADUpdateRequestProjector implements Projector
{
    use ProjectsEvents;
}
