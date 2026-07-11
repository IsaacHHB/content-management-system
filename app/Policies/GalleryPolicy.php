<?php

namespace App\Policies;

class GalleryPolicy extends ContentPolicy
{
    protected function module(): string
    {
        return 'galleries';
    }
}
