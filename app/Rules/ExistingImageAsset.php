<?php

namespace App\Rules;

use App\Models\MediaAsset;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ExistingImageAsset implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value) || ! MediaAsset::query()->whereKey((int) $value)->where('type', 'image')->exists()) {
            $fail('The selected :attribute must be an available image.');
        }
    }
}
