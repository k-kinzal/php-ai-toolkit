<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Document;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Document\Pointer
 * @uses \Toolkit\Guard\Document\Selection
 * @uses \Toolkit\Guard\Policy\PolicyException
 */
#[CoversClass(\Toolkit\Guard\Document\Pointer::class)]
#[UsesClass(\Toolkit\Guard\Document\Selection::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
final class PointerTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testReadEscapesKeysAndPreservesNull(): void
    {
        $pointer = new \Toolkit\Guard\Document\Pointer();
        $data = (object) ['a/b' => (object) ['~' => null]];
        self::assertTrue($pointer->read($data, '/a~1b/~0')->exists);
        self::assertNull($pointer->read($data, '/a~1b/~0')->value);
        self::assertFalse($pointer->read($data, '/missing')->exists);
    }

    public function testWriteRefusesScalarParentReplacement(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        (new \Toolkit\Guard\Document\Pointer())->write((object) ['x' => false], '/x/y', 'A');
    }

    public function testRefusesOutOfRangeArrayIndex(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        (new \Toolkit\Guard\Document\Pointer())->write((object) ['items' => ['A']], '/items/3', 'B');
    }

    public function testTokensRejectsInvalidEscapes(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        (new \Toolkit\Guard\Document\Pointer())->tokens('/a~2b');
    }
    public function testReplaceCreatesOnlyMissingObjectParents(): void
    {
        $value = (new \Toolkit\Guard\Document\Pointer())->replace((object) ['untouched' => true], ['new', 'key'], 'A');
        self::assertSame('{"untouched":true,"new":{"key":"A"}}', json_encode($value));
    }
    public function testWriteDoesNotConvertAnEmptyListToAnObject(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        (new \Toolkit\Guard\Document\Pointer())->write([], '/mode', 'A');
    }
}
