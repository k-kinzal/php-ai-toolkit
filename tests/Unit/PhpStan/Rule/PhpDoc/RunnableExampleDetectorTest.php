<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use PhpAiToolkit\DocGen\Analysis\Doctest\DocExample;
use PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use PhpAiToolkit\PhpStan\Rule\PhpDoc\RunnableExampleDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RunnableExampleDetector::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocExample::class)]
final class RunnableExampleDetectorTest extends TestCase
{
    public function testHasRunnableExampleAcceptsATagBlockWithCode(): void
    {
        self::assertTrue((new RunnableExampleDetector())->hasRunnableExample("/**\n * @example Adding\n * add(1, 2) // => 3\n */"));
    }

    public function testHasRunnableExampleAcceptsAPhpFence(): void
    {
        self::assertTrue((new RunnableExampleDetector())->hasRunnableExample("/**\n * ```php\n * add(1, 2) // => 3\n * ```\n */"));
    }

    public function testHasRunnableExampleRejectsDisplayOnlyAndMissingExamples(): void
    {
        self::assertFalse((new RunnableExampleDetector())->hasRunnableExample("/**\n * @example add(1, 2)\n */"));
        self::assertFalse((new RunnableExampleDetector())->hasRunnableExample("/**\n * Summary.\n */"));
        self::assertFalse((new RunnableExampleDetector())->hasRunnableExample(null));
    }
}
