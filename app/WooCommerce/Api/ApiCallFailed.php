<?php

namespace App\WooCommerce\Api;


use Throwable;

class ApiCallFailed extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
