<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Document;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Document\Pointer
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Structure\Document\Selection
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(\Guard\Structure\Document\Pointer::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Structure\Document\Selection::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
final class PointerTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testReadEscapesKeysAndPreservesNull(): void
    {
        $pointer = new \Guard\Structure\Document\Pointer();
        $data = (object) ['a/b' => (object) ['~' => null]];
        self::assertTrue($pointer->read($data, '/a~1b/~0')->exists);
        self::assertNull($pointer->read($data, '/a~1b/~0')->value);
        self::assertFalse($pointer->read($data, '/missing')->exists);
    }

    public function testWriteRefusesScalarParentReplacement(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        (new \Guard\Structure\Document\Pointer())->write((object) ['x' => false], '/x/y', 'A');
    }

    public function testRefusesOutOfRangeArrayIndex(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        (new \Guard\Structure\Document\Pointer())->write((object) ['items' => ['A']], '/items/3', 'B');
    }

    public function testTokensRejectsInvalidEscapes(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        (new \Guard\Structure\Document\Pointer())->tokens('/a~2b');
    }
    public function testReplaceCreatesOnlyMissingObjectParents(): void
    {
        $value = (new \Guard\Structure\Document\Pointer())->replace((object) ['untouched' => true], ['new', 'key'], 'A');
        self::assertSame('{"untouched":true,"new":{"key":"A"}}', json_encode($value));
    }
    public function testWriteDoesNotConvertAnEmptyListToAnObject(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        (new \Guard\Structure\Document\Pointer())->write([], '/mode', 'A');
    }
}
