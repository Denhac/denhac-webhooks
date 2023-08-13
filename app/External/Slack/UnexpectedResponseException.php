<?php

namespace App\External\Slack;

class UnexpectedResponseException extends \Exception
{
    public $body;

    public function __construct($message)
    {
        parent::__construct($message, 0);
    }
}
