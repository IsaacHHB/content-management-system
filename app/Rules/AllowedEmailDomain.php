<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class AllowedEmailDomain implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domain = Str::lower(Str::afterLast((string) $value, '@'));

        if (! in_array($domain, config('admin.allowed_domains', []), true)) {
            $fail('Administrator accounts must use an official Native Dads Network email address.');
        }
    }
}
