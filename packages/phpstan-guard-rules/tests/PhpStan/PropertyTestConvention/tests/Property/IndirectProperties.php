<?php

declare(strict_types=1);

namespace Tests\Property\Fixture;

final class IndirectProperties extends \PHPStan\Testing\RuleTestCase {
    /** @test */
    #[\PHPUnit\Framework\Attributes\Test]
    public function satisfiesIdentity(): void { self::assertTrue(true); }
}
