<?php

namespace App\Support;

class Countries
{
    /** @return array<string, string> code => name */
    public static function all(): array
    {
        return config('countries', []);
    }

    public static function isValid(?string $code): bool
    {
        if ($code === null || $code === '') {
            return false;
        }

        return array_key_exists(strtoupper($code), self::all());
    }

    public static function name(string $code): ?string
    {
        return self::all()[strtoupper($code)] ?? null;
    }
}
