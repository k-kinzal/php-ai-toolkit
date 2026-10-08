<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Document;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Document\Selection
 */
#[CoversClass(\Guard\Structure\Document\Selection::class)]
final class SelectionTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testDistinguishesMissingFromNull(): void
    {
        $missing = new \Guard\Structure\Document\Selection(false, null);
        $present = new \Guard\Structure\Document\Selection(true, null);
        self::assertFalse($missing->exists);
        self::assertTrue($present->exists);
        self::assertNull($present->value);
    }

}
