<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Document;

use Guard\Config\Configuration;
use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Input\DirectoryListing;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\FileSet;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Selection;
use Guard\Input\StructuredFile;
use Guard\Policy\Context;
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use Guard\Policy\PolicyBinding;
use Guard\Structure\Document\Pointer;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

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
#[CoversClass(Pointer::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(\Guard\Structure\Document\Selection::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
final class PointerTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testReadEscapesKeysAndPreservesNull(): void
    {
        $pointer = new Pointer();
        $data = (object) ['a/b' => (object) ['~' => null]];
        self::assertTrue($pointer->read($data, '/a~1b/~0')->exists);
        self::assertNull($pointer->read($data, '/a~1b/~0')->value);
        self::assertFalse($pointer->read($data, '/missing')->exists);
    }

    public function testWriteRefusesScalarParentReplacement(): void
    {
        $this->expectException(PolicyException::class);
        (new Pointer())->write((object) ['x' => false], '/x/y', 'A');
    }

    public function testRefusesOutOfRangeArrayIndex(): void
    {
        $this->expectException(PolicyException::class);
        (new Pointer())->write((object) ['items' => ['A']], '/items/3', 'B');
    }

    public function testTokensRejectsInvalidEscapes(): void
    {
        $this->expectException(PolicyException::class);
        (new Pointer())->tokens('/a~2b');
    }
    public function testReplaceCreatesOnlyMissingObjectParents(): void
    {
        $value = (new Pointer())->replace((object) ['untouched' => true], ['new', 'key'], 'A');
        self::assertSame('{"untouched":true,"new":{"key":"A"}}', json_encode($value));
    }
    public function testWriteDoesNotConvertAnEmptyListToAnObject(): void
    {
        $this->expectException(PolicyException::class);
        (new Pointer())->write([], '/mode', 'A');
    }

    /**
     * @throws JsonException
     */
    public function testWriteAppendsTheNextIndexAndCreatesMissingLists(): void
    {
        $pointer = new Pointer();
        $first = $pointer->write((object) [], '/items/0/name', 'first');
        $second = $pointer->write($first, '/items/1/name', 'second');
        self::assertSame('second', $pointer->read($second, '/items/1/name')->value);
        self::assertSame('{"items":[{"name":"first"},{"name":"second"}]}', json_encode($second));
    }

    public function testContainerChoosesListsOnlyForCanonicalIndexes(): void
    {
        $pointer = new Pointer();
        self::assertSame([], $pointer->container(['0']));
        self::assertInstanceOf(stdClass::class, $pointer->container(['name']));
        self::assertInstanceOf(stdClass::class, $pointer->container(['01']));
    }

}
