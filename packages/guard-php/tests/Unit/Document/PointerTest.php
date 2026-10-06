<?php

declare(strict_types=1);

namespace Tests\Unit\Document;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Document\Pointer
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Document\Selection
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Document\Pointer::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Document\Selection::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class PointerTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testReadEscapesKeysAndPreservesNull(): void
    {
        $pointer = new \Guard\Document\Pointer();
        $data = (object) ['a/b' => (object) ['~' => null]];
        self::assertTrue($pointer->read($data, '/a~1b/~0')->exists);
        self::assertNull($pointer->read($data, '/a~1b/~0')->value);
        self::assertFalse($pointer->read($data, '/missing')->exists);
    }

    public function testWriteRefusesScalarParentReplacement(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        (new \Guard\Document\Pointer())->write((object) ['x' => false], '/x/y', 'A');
    }

    public function testRefusesOutOfRangeArrayIndex(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        (new \Guard\Document\Pointer())->write((object) ['items' => ['A']], '/items/3', 'B');
    }

    public function testTokensRejectsInvalidEscapes(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        (new \Guard\Document\Pointer())->tokens('/a~2b');
    }
    public function testReplaceCreatesOnlyMissingObjectParents(): void
    {
        $value = (new \Guard\Document\Pointer())->replace((object) ['untouched' => true], ['new', 'key'], 'A');
        self::assertSame('{"untouched":true,"new":{"key":"A"}}', json_encode($value));
    }
    public function testWriteDoesNotConvertAnEmptyListToAnObject(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        (new \Guard\Document\Pointer())->write([], '/mode', 'A');
    }
}
