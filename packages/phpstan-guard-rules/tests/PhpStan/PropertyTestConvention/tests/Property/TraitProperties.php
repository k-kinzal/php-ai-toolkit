<?php

namespace Tests\Property\Fixture;

final class TraitProperties extends \PHPUnit\Framework\TestCase
{
    use \Eris\TestTrait;

    /** @test */
    #[\PHPUnit\Framework\Attributes\Test]
    public function satisfiesIdentity(): void {}
}
