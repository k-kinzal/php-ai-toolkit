<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Diagnostic;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Diagnostic\DocGenException;

/**
 * @coversNothing
 */
#[CoversNothing]
final class DocGenExceptionTest extends TestCase
{
    public function testCarriesMessage(): void
    {
        $exception = new DocGenException('Unknown option: --bogus');

        self::assertSame('Unknown option: --bogus', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
    }
}
