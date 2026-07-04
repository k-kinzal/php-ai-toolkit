<?php

declare(strict_types=1);

namespace Tests\Fixture\NoRedundantAssertInstanceOf;

use PHPUnit\Framework\TestCase;

final class FirstReporter implements ReporterInterface
{
    public function report(): string
    {
        return 'first';
    }
}

final class SecondReporter implements ReporterInterface
{
    public function report(): string
    {
        return 'second';
    }
}

final class AllowedAssertInstanceOf extends TestCase
{
    public function testUnknownObjectCanBeAsserted(object $reporter): void
    {
        self::assertInstanceOf(ReporterInterface::class, $reporter);
    }

    public function testMixedValueCanBeAsserted(mixed $reporter): void
    {
        self::assertInstanceOf(ReporterInterface::class, $reporter);
    }

    public function testUnionOfMultipleImplementationsIsNotReported(bool $useFirst): void
    {
        $reporter = $useFirst ? new FirstReporter() : new SecondReporter();

        self::assertInstanceOf(ReporterInterface::class, $reporter);
    }

    public function testDynamicExpectedTypeIsNotReported(string $expected): void
    {
        $reporter = new Reporter();

        self::assertInstanceOf($expected, $reporter);
    }

    public function testAssertNotInstanceOfIsNotReported(): void
    {
        $reporter = new Reporter();

        self::assertNotInstanceOf(SecondReporter::class, $reporter);
    }
}
