<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\NoTraitUseInTestClass;

use PHPUnit\Framework\TestCase;

trait HelperTrait
{
    public function helperMethod(): string
    {
        return 'hello';
    }
}

class WithTrait extends TestCase
{
    use HelperTrait;

    public function testSomething(): void
    {
        self::assertSame('hello', $this->helperMethod());
    }
}
