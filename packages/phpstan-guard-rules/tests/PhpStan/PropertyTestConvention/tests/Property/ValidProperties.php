<?php

declare(strict_types=1);

namespace Tests\Property\Fixture;

use PHPUnit\Framework\Attributes\Test as Property;
use PHPUnit\Framework\TestCase;

final class ValidProperties extends TestCase
{
    /** @test */
    #[Property]
    public function satisfiesRoundTrip(): void
    {
        self::assertSame('value', 'value');
    }
}
