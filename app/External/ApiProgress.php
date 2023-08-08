<?php

namespace App\External;


use Illuminate\Console\Concerns\InteractsWithIO;

interface ApiProgress
{
    function setProgress($current, $max): void;

    function step(): void;
}
