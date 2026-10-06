<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use Guard\Reporting\DirectoryViolation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Reporting\DirectoryViolation
 */
#[CoversClass(DirectoryViolation::class)]
final class DirectoryViolationTest extends TestCase
{
    public function testStoresViolationData(): void
    {
        $violation = new DirectoryViolation('src/A', 'max_files', 'src/*', 26, 25, 'Too many files.');

        self::assertSame('src/A', $violation->path);
        self::assertSame('max_files', $violation->rule);
        self::assertSame('src/*', $violation->pattern);
        self::assertSame(26, $violation->actual);
        self::assertSame(25, $violation->limit);
        self::assertSame('Too many files.', $violation->message);
    }

    public function testStoresNullActualAndLimit(): void
    {
        $violation = new DirectoryViolation('src/notes.txt', 'disallowed_file', 'src/**', null, null, 'Not allowed.');

        self::assertNull($violation->actual);
        self::assertNull($violation->limit);
    }
}
