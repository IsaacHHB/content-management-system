<?php

namespace App\Contracts;

interface SoftDeletableContent
{
    /** @return bool */
    public function restore();

    /** @return bool|null */
    public function forceDelete();
}
