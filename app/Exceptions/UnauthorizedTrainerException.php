<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class UnauthorizedTrainerException extends Exception
{
    public function __construct(int $trainerId, int $equipmentId, ?Throwable $previous = null)
    {
        $message = "Trainer $trainerId attempted to train for equipment $equipmentId but was not authorized to do so";
        parent::__construct($message, 0, $previous);
    }
}
