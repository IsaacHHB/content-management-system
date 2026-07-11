<?php

namespace App\Policies;

class EventPolicy extends ContentPolicy
{
    protected function module(): string
    {
        return 'events';
    }
}
