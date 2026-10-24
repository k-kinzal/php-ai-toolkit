<?php

declare(strict_types=1);

namespace Tests\Property\Fixture;

class InvalidTest
{
    public function testSomething(): void {}
    public function providerValues(): array { return []; }
    protected function setUp(): void {}
    public function satisfies(): void {}
    public function satisfieslowerCase(): void {}
    public function satisfiesMissingMarker(): void {}
    /** @test */
    #[\PHPUnit\Framework\Attributes\Test]
    protected static function satisfiesVisibility(int $value): void {}
}
