<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use PhpAiToolkit\Doctest\Parser\Example;
use PhpAiToolkit\Doctest\Parser\ExampleExtractor;
use PhpAiToolkit\Doctest\Scanner\Target;
use PhpAiToolkit\Doctest\Scanner\TargetKind;
use PhpAiToolkit\PhpStan\Rule\PhpDoc\RunnableExampleDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\PhpDoc\RunnableExampleDetector
 * @uses \PhpAiToolkit\Doctest\Parser\Example
 * @uses \PhpAiToolkit\Doctest\Parser\ExampleExtractor
 * @uses \PhpAiToolkit\Doctest\Scanner\Target
 * @uses \PhpAiToolkit\Doctest\Scanner\TargetKind
 */
#[CoversClass(RunnableExampleDetector::class)]
#[UsesClass(Example::class)]
#[UsesClass(ExampleExtractor::class)]
#[UsesClass(Target::class)]
#[UsesClass(TargetKind::class)]
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
