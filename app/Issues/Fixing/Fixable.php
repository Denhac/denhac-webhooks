<?php

namespace App\Issues\Fixing;

interface Fixable
{
    function fix(): bool;
}
