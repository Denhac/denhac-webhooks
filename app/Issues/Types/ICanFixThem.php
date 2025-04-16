<?php

namespace App\Issues\Types;

use App\DataCache\AggregateCustomerData;
use App\DataCache\MemberData;
use App\Issues\ChoiceHelper;
use function Laravel\Prompts\info;
use function Laravel\Prompts\search;

trait ICanFixThem
{
    abstract public function fix(): bool;

    protected function issueFixChoice($text = 'How do you want to fix this issue?'): ChoiceHelper
    {
        return new ChoiceHelper($text);
    }
}
