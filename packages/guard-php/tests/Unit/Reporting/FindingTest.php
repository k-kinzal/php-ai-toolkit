<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Reporting\Finding::class)]
final class FindingTest extends TestCase
{
    public function testIdentifiesAnIndividualViolation(): void
    {
        $finding = new \Guard\Reporting\Finding('a.json', 'worker.minimum', 'required', '/workers must be at least 1.');
        self::assertSame('worker.minimum', $finding->rule);
        self::assertSame('a.json', $finding->path);
        self::assertStringContainsString('/workers', $finding->message);
    }

}
