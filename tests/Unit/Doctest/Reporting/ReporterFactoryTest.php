<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Reporting;

use PhpAiToolkit\Doctest\DoctestException;
use PhpAiToolkit\Doctest\Reporting\AiReporter;
use PhpAiToolkit\Doctest\Reporting\JsonReporter;
use PhpAiToolkit\Doctest\Reporting\ReporterFactory;
use PhpAiToolkit\Doctest\Reporting\ResultSorter;
use PhpAiToolkit\Doctest\Reporting\TextReporter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReporterFactory::class)]
#[UsesClass(AiReporter::class)]
#[UsesClass(TextReporter::class)]
#[UsesClass(JsonReporter::class)]
#[UsesClass(ResultSorter::class)]
final class ReporterFactoryTest extends TestCase
{
    public function testCreateReturnsTheNamedReporter(): void
    {
        $factory = new ReporterFactory();

        self::assertInstanceOf(AiReporter::class, $factory->create('ai'));
        self::assertInstanceOf(TextReporter::class, $factory->create('text'));
        self::assertInstanceOf(JsonReporter::class, $factory->create('json'));
    }

    public function testCreateRejectsAnUnknownReporter(): void
    {
        $this->expectException(DoctestException::class);
        $this->expectExceptionMessage('Unknown doctest reporter: xml');

        (new ReporterFactory())->create('xml');
    }
}
