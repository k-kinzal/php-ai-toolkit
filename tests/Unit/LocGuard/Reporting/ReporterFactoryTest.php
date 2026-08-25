<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\LocGuardException;
use Toolkit\LocGuard\Reporting\AiReporter;
use Toolkit\LocGuard\Reporting\AiViolationFormatter;
use Toolkit\LocGuard\Reporting\JsonReporter;
use Toolkit\LocGuard\Reporting\ReporterFactory;
use Toolkit\LocGuard\Reporting\TextReporter;
use Toolkit\LocGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\LocGuard\Reporting\ReporterFactory
 * @uses \Toolkit\LocGuard\Reporting\AiReporter
 * @uses \Toolkit\LocGuard\Reporting\AiViolationFormatter
 * @uses \Toolkit\LocGuard\Reporting\JsonReporter
 * @uses \Toolkit\LocGuard\Reporting\TextReporter
 * @uses \Toolkit\LocGuard\Reporting\ViolationSorter
 */
#[CoversClass(ReporterFactory::class)]
#[UsesClass(AiReporter::class)]
#[UsesClass(AiViolationFormatter::class)]
#[UsesClass(JsonReporter::class)]
#[UsesClass(TextReporter::class)]
#[UsesClass(ViolationSorter::class)]
final class ReporterFactoryTest extends TestCase
{
    public function testCreateReturnsKnownReporters(): void
    {
        $factory = new ReporterFactory();

        self::assertSame(AiReporter::class, $factory->create('ai')::class);
        self::assertSame(TextReporter::class, $factory->create('text')::class);
        self::assertSame(JsonReporter::class, $factory->create('json')::class);
    }

    public function testCreateRejectsUnknownReporter(): void
    {
        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('Unknown LocGuard reporter');

        (new ReporterFactory())->create('xml');
    }
}
