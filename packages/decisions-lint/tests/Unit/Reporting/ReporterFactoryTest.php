<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Reporting\AiReporter;
use DecisionsLint\Reporting\CheckstyleReporter;
use DecisionsLint\Reporting\GithubReporter;
use DecisionsLint\Reporting\JsonReporter;
use DecisionsLint\Reporting\ReporterFactory;
use DecisionsLint\Reporting\TextReporter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Reporting\ReporterFactory
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Reporting\AiReporter
 * @uses \DecisionsLint\Reporting\CheckstyleReporter
 * @uses \DecisionsLint\Reporting\GithubReporter
 * @uses \DecisionsLint\Reporting\JsonReporter
 * @uses \DecisionsLint\Reporting\TextReporter
 */
#[CoversClass(ReporterFactory::class)]
#[UsesClass(LintException::class)]
#[UsesClass(AiReporter::class)]
#[UsesClass(CheckstyleReporter::class)]
#[UsesClass(GithubReporter::class)]
#[UsesClass(JsonReporter::class)]
#[UsesClass(TextReporter::class)]
final class ReporterFactoryTest extends TestCase
{
    public function testCreateReturnsTheReporterOfEachFormat(): void
    {
        $factory = new ReporterFactory();
        self::assertInstanceOf(TextReporter::class, $factory->create('text'));
        self::assertInstanceOf(TextReporter::class, $factory->create('human'));
        self::assertInstanceOf(AiReporter::class, $factory->create('ai'));
        self::assertInstanceOf(JsonReporter::class, $factory->create('json'));
        self::assertInstanceOf(CheckstyleReporter::class, $factory->create('checkstyle'));
        self::assertInstanceOf(GithubReporter::class, $factory->create('github'));
    }

    public function testCreateRejectsAnUnknownFormat(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Unsupported --format "xml". Use --format=text, human, ai, json, checkstyle, github.');
        (new ReporterFactory())->create('xml');
    }
}
