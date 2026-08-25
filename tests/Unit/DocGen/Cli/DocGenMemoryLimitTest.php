<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PhpAiToolkit\DocGen\Cli\DocGenMemoryLimit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Cli\DocGenMemoryLimit
 */
#[CoversClass(DocGenMemoryLimit::class)]
final class DocGenMemoryLimitTest extends TestCase
{
    public function testApplyUsesRequestedLimit(): void
    {
        $previous = ini_get('memory_limit');
        ini_set('memory_limit', '384M');

        (new DocGenMemoryLimit())->apply('1G');
        $applied = ini_get('memory_limit');
        ini_set('memory_limit', $previous);

        self::assertSame('1G', $applied);
    }

    public function testApplyLowersLimitWhenExplicitlyRequested(): void
    {
        $previous = ini_get('memory_limit');
        ini_set('memory_limit', '2G');

        (new DocGenMemoryLimit())->apply('768M');
        $applied = ini_get('memory_limit');
        ini_set('memory_limit', $previous);

        self::assertSame('768M', $applied);
    }

    public function testApplyRaisesLimitBelowTheFloor(): void
    {
        $previous = ini_get('memory_limit');
        ini_set('memory_limit', '384M');

        (new DocGenMemoryLimit())->apply(null);
        $applied = ini_get('memory_limit');
        ini_set('memory_limit', $previous);

        self::assertSame(DocGenMemoryLimit::FLOOR, $applied);
    }

    public function testApplyKeepsLimitAboveTheFloor(): void
    {
        $previous = ini_get('memory_limit');
        ini_set('memory_limit', '2G');

        (new DocGenMemoryLimit())->apply(null);
        $applied = ini_get('memory_limit');
        ini_set('memory_limit', $previous);

        self::assertSame('2G', $applied);
    }

    public function testApplyKeepsUnlimitedLimit(): void
    {
        $previous = ini_get('memory_limit');
        ini_set('memory_limit', '-1');

        (new DocGenMemoryLimit())->apply(null);
        $applied = ini_get('memory_limit');
        ini_set('memory_limit', $previous);

        self::assertSame('-1', $applied);
    }

    public function testBytesConvertsSuffixedValues(): void
    {
        self::assertSame(1024, (new DocGenMemoryLimit())->bytes('1K'));
        self::assertSame(536870912, (new DocGenMemoryLimit())->bytes('512M'));
        self::assertSame(1073741824, (new DocGenMemoryLimit())->bytes('1g'));
        self::assertSame(134217728, (new DocGenMemoryLimit())->bytes('134217728'));
    }

    public function testBytesReportsUnlimitedAndUnknownValues(): void
    {
        self::assertSame(-1, (new DocGenMemoryLimit())->bytes('-1'));
        self::assertSame(-1, (new DocGenMemoryLimit())->bytes(' -1 '));
        self::assertSame(-1, (new DocGenMemoryLimit())->bytes('plenty'));
    }
}
