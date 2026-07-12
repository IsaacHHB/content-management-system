<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeUrl implements ValidationRule
{
    public function __construct(private readonly bool $allowRelative = true) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if ($this->allowRelative && str_starts_with($value, '/') && ! str_starts_with($value, '//') && ! str_contains($value, '\\')) {
            return;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        $allowed = in_array($scheme, ['http', 'https', 'mailto', 'tel'], true);
        $validWebUrl = ! in_array($scheme, ['http', 'https'], true) || filter_var($value, FILTER_VALIDATE_URL) !== false;

        if (! $allowed || ! $validWebUrl) {
            $fail('The :attribute must be a safe web, email, telephone, or relative URL.');
        }
    }
}
