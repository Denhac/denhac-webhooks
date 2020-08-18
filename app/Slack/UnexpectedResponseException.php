<?php

namespace App\Slack;


use Throwable;

class UnexpectedResponseException extends \Exception
{
    public $body;

    public function __construct($message)
    {
        parent::__construct($message, 0);
    }
}
