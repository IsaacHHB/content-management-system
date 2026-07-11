<?php

namespace App\Policies;

class PagePolicy extends ContentPolicy
{
    protected function module(): string
    {
        return 'pages';
    }
}
