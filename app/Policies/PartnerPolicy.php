<?php

namespace App\Policies;

class PartnerPolicy extends ContentPolicy
{
    protected function module(): string
    {
        return 'partners';
    }
}
