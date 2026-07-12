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

        $allowed = array_map(static fn (mixed $allowedDomain): string => Str::lower(trim((string) $allowedDomain)), config('admin.allowed_domains', []));

        if (! in_array($domain, $allowed, true)) {
            $fail('Administrator accounts must use an official Native Dads Network email address.');
        }
    }
}
