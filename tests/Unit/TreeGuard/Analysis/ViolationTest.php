<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Analysis;

use PhpAiToolkit\TreeGuard\Analysis\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\TreeGuard\Analysis\Violation
 */
#[CoversClass(Violation::class)]
final class ViolationTest extends TestCase
{
    public function testStoresViolationData(): void
    {
        $violation = new Violation('src/A', 'max_files', 'src/*', 26, 25, 'Too many files.');

        self::assertSame('src/A', $violation->path);
        self::assertSame('max_files', $violation->rule);
        self::assertSame('src/*', $violation->pattern);
        self::assertSame(26, $violation->actual);
        self::assertSame(25, $violation->limit);
        self::assertSame('Too many files.', $violation->message);
    }

    public function testStoresNullActualAndLimit(): void
    {
        $violation = new Violation('src/notes.txt', 'disallowed_file', 'src/**', null, null, 'Not allowed.');

        self::assertNull($violation->actual);
        self::assertNull($violation->limit);
    }
}
