<?php

namespace App\Policies;

class PostPolicy extends ContentPolicy
{
    protected function module(): string
    {
        return 'posts';
    }
}
