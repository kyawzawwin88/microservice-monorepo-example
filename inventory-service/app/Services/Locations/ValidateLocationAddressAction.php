<?php

namespace App\Services\Locations;

use App\Support\Countries;
use InvalidArgumentException;

class ValidateLocationAddressAction
{
    public const STREET_MAX_LENGTH = 500;

    public const POSTAL_MIN_LENGTH = 2;

    public const POSTAL_MAX_LENGTH = 20;

    /**
     * @return array{address_street: ?string, country_code: ?string, postal_code: ?string}
     */
    public function execute(?string $street, ?string $countryCode, ?string $postalCode): array
    {
        $street = $this->normalize($street);
        $countryCode = $this->normalize($countryCode);
        $postalCode = $this->normalize($postalCode);

        if ($countryCode !== null) {
            $countryCode = strtoupper($countryCode);
        }

        if ($street !== null && strlen($street) > self::STREET_MAX_LENGTH) {
            throw new InvalidArgumentException(
                'Street or locality must be '.self::STREET_MAX_LENGTH.' characters or fewer.'
            );
        }

        if ($postalCode !== null && ! preg_match('/^[A-Za-z0-9 -]{'.self::POSTAL_MIN_LENGTH.','.self::POSTAL_MAX_LENGTH.'}$/', $postalCode)) {
            throw new InvalidArgumentException(
                'Postal code must be '.self::POSTAL_MIN_LENGTH.'–'.self::POSTAL_MAX_LENGTH.' characters and contain only letters, numbers, spaces, and hyphens.'
            );
        }

        if ($postalCode !== null && $countryCode === null) {
            throw new InvalidArgumentException('Country is required when a postal code is provided.');
        }

        if ($countryCode !== null && ! Countries::isValid($countryCode)) {
            throw new InvalidArgumentException('Country must be a recognized country code.');
        }

        return [
            'address_street' => $street,
            'country_code' => $countryCode,
            'postal_code' => $postalCode,
        ];
    }

    private function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
