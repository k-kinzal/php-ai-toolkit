<?php

declare(strict_types=1);

namespace Tests\Fixture\NoRedundantAssertInstanceOf;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

interface ReporterInterface
{
    public function report(): string;
}

final class Reporter implements ReporterInterface
{
    public function report(): string
    {
        return 'ok';
    }
}

final class RedundantAssertInstanceOf extends TestCase
{
    public function testStaticSelfCallWithConcreteInstance(): void
    {
        $reporter = new Reporter();

        self::assertInstanceOf(ReporterInterface::class, $reporter);
    }

    public function testInstanceCallWithExactConcreteType(): void
    {
        $reporter = new Reporter();

        $this->assertInstanceOf(Reporter::class, $reporter);
    }

    public function testAssertClassCallWithConcreteInstance(): void
    {
        $reporter = new Reporter();

        Assert::assertInstanceOf(ReporterInterface::class, $reporter);
    }
}
