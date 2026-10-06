<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Doc;

use Guard\Policy\Doc\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Doc\Violation
 */
#[CoversClass(Violation::class)]
final class ViolationTest extends TestCase
{
    public function testGetExposesViolationFields(): void
    {
        $violation = new Violation('README.md', 9, 'renamed_heading', '## Usage', '## Getting Started', 'Renamed.');

        self::assertSame('README.md', $violation->path);
        self::assertSame(9, $violation->line);
        self::assertSame('renamed_heading', $violation->rule);
        self::assertSame('## Usage', $violation->expected);
        self::assertSame('## Getting Started', $violation->actual);
        self::assertSame('Renamed.', $violation->message);
    }
}
