<?php

namespace App\External;


interface ApiProgress
{
    function setProgress($current, $max): void;
}