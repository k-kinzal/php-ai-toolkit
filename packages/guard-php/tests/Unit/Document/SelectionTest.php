<?php

declare(strict_types=1);

namespace Tests\Unit\Document;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Document\Selection
 */
#[CoversClass(\Guard\Document\Selection::class)]
final class SelectionTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testDistinguishesMissingFromNull(): void
    {
        $missing = new \Guard\Document\Selection(false, null);
        $present = new \Guard\Document\Selection(true, null);
        self::assertFalse($missing->exists);
        self::assertTrue($present->exists);
        self::assertNull($present->value);
    }

}
