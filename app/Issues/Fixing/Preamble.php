<?php

namespace App\Issues\Fixing;

use Illuminate\Console\Concerns\InteractsWithIO;

abstract class Preamble
{
    use InteractsWithIO;

    public abstract function preamble(): void;
}
