<?php

namespace Tests\Unit\Services\Locations;

use App\Services\Locations\ValidateLocationAddressAction;
use InvalidArgumentException;
use Tests\TestCase;

class ValidateLocationAddressActionTest extends TestCase
{
    public function test_accepts_full_address(): void
    {
        $result = (new ValidateLocationAddressAction)->execute(
            "123 Main St\nSuite 4",
            'us',
            '94103'
        );

        $this->assertSame("123 Main St\nSuite 4", $result['address_street']);
        $this->assertSame('US', $result['country_code']);
        $this->assertSame('94103', $result['postal_code']);
    }

    public function test_empty_address_parts_normalize_to_null(): void
    {
        $result = (new ValidateLocationAddressAction)->execute('  ', '', '  ');

        $this->assertNull($result['address_street']);
        $this->assertNull($result['country_code']);
        $this->assertNull($result['postal_code']);
    }

    public function test_postal_without_country_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Country is required');

        (new ValidateLocationAddressAction)->execute(null, null, '94103');
    }

    public function test_invalid_country_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ValidateLocationAddressAction)->execute(null, 'ZZ', null);
    }

    public function test_invalid_postal_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ValidateLocationAddressAction)->execute(null, 'US', '!!');
    }
}
