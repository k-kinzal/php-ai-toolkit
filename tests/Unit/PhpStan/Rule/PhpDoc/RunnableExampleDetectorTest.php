<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\Doctest\Parser\Example;
use Toolkit\Doctest\Parser\ExampleExtractor;
use Toolkit\Doctest\Scanner\Target;
use Toolkit\Doctest\Scanner\TargetKind;
use Toolkit\PhpStan\Rule\PhpDoc\RunnableExampleDetector;

/**
 * @covers \Toolkit\PhpStan\Rule\PhpDoc\RunnableExampleDetector
 * @uses \Toolkit\Doctest\Parser\Example
 * @uses \Toolkit\Doctest\Parser\ExampleExtractor
 * @uses \Toolkit\Doctest\Scanner\Target
 * @uses \Toolkit\Doctest\Scanner\TargetKind
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
