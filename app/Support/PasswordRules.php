<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;
use ReflectionClass;

class PasswordRules
{
    /**
     * Build an HTML `passwordrules` attribute string from a Password rule.
     *
     * Mirrors the Password::toPasswordRulesString() helper available in
     * Laravel 13 so the frontend can hint password managers about the
     * configured requirements while the app targets Laravel 12.
     */
    public static function toString(?Password $password = null): string
    {
        $password ??= Password::default();

        $reflection = new ReflectionClass($password);

        $value = static function (string $property) use ($reflection, $password): mixed {
            $prop = $reflection->getProperty($property);
            $prop->setAccessible(true);

            return $prop->getValue($password);
        };

        $rules = [];

        $rules[] = 'minlength: '.$value('min').';';

        if ($max = $value('max')) {
            $rules[] = 'maxlength: '.$max.';';
        }

        if ($value('mixedCase')) {
            $rules[] = 'required: lower;';
            $rules[] = 'required: upper;';
        } elseif ($value('letters')) {
            $rules[] = 'required: lower, upper;';
        }

        if ($value('numbers')) {
            $rules[] = 'required: digit;';
        }

        if ($value('symbols')) {
            $rules[] = 'required: special;';
        }

        return implode(' ', $rules);
    }
}
