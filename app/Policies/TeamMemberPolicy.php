<?php

namespace App\Policies;

class TeamMemberPolicy extends ContentPolicy
{
    protected function module(): string
    {
        return 'team';
    }
}
