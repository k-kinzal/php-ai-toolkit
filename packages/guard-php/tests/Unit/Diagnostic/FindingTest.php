<?php

declare(strict_types=1);

namespace Tests\Unit\Diagnostic;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Diagnostic\Finding
 */
#[CoversClass(\Guard\Diagnostic\Finding::class)]
final class FindingTest extends TestCase
{
    public function testIdentifiesAnIndividualViolation(): void
    {
        $finding = new \Guard\Diagnostic\Finding('a.json', 'worker.minimum', 'required', '/workers must be at least 1.');
        self::assertSame('worker.minimum', $finding->rule);
        self::assertSame('a.json', $finding->path);
        self::assertStringContainsString('/workers', $finding->message);
    }

}
