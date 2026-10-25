<?php
namespace Tests\Property\ErisFixture;
final class ErisProperties extends \PHPUnit\Framework\TestCase
{
    use \Eris\TestTrait;
    /** @test */
    #[\PHPUnit\Framework\Attributes\Test]
    public function satisfiesFixedExample(): void { self::assertSame(1, 1); }
    /** @test */
    #[\PHPUnit\Framework\Attributes\Test]
    public function satisfiesGeneratedValues(): void { $this->forAll(1)->then(static function ($v): void { self::assertSame($v, $v); }); }
}
