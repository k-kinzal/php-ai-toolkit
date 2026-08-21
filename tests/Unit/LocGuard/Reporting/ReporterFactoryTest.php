<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Reporting;

use PhpAiToolkit\LocGuard\LocGuardException;
use PhpAiToolkit\LocGuard\Reporting\AiReporter;
use PhpAiToolkit\LocGuard\Reporting\AiViolationFormatter;
use PhpAiToolkit\LocGuard\Reporting\JsonReporter;
use PhpAiToolkit\LocGuard\Reporting\ReporterFactory;
use PhpAiToolkit\LocGuard\Reporting\TextReporter;
use PhpAiToolkit\LocGuard\Reporting\ViolationSorter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
