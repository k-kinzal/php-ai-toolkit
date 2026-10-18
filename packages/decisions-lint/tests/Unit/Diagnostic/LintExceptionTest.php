<?php

declare(strict_types=1);

namespace Tests\Unit\Diagnostic;

use DecisionsLint\Diagnostic\LintException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Diagnostic\LintException
 */
#[CoversClass(LintException::class)]
final class LintExceptionTest extends TestCase
{
    public function testCarriesTheMessageThatNamesTheFix(): void
    {
        $exception = new LintException('decisions.yaml: provider.name must be one of "jev", "openai". Replace it with one of those values.');
        self::assertSame('decisions.yaml: provider.name must be one of "jev", "openai". Replace it with one of those values.', $exception->getMessage());
    }
}
