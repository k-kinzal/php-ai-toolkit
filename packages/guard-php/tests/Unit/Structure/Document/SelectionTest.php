<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Document;

use Guard\Structure\Document\Selection;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Document\Selection
 */
#[CoversClass(Selection::class)]
final class SelectionTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testDistinguishesMissingFromNull(): void
    {
        $missing = new Selection(false, null);
        $present = new Selection(true, null);
        self::assertFalse($missing->exists);
        self::assertTrue($present->exists);
        self::assertNull($present->value);
    }

}
