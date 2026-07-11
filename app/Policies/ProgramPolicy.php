<?php

namespace App\Policies;

class ProgramPolicy extends ContentPolicy
{
    protected function module(): string
    {
        return 'programs';
    }
}
