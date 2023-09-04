<?php

namespace App\External;

interface ApiProgress
{
    public function setProgress($current, $max): void;

    public function step(): void;
}
