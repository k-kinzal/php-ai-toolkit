<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Document;

use DateTimeImmutable;
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
use Guard\Structure\Document\DocumentNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \Guard\Structure\Document\DocumentNode
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(DocumentNode::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
final class DocumentNodeTest extends TestCase
{
    public function testNativePreservesMapListScalarAndParserObjectTypes(): void
    {
        $date = new DateTimeImmutable('2026-01-01');
        $input = ['emptyMap' => new stdClass(), 'emptyList' => [], 'nested' => ['null' => null, 'bool' => false, 'float' => 1.0, 'date' => $date]];
        self::assertEquals($input, (new DocumentNode($input))->native());
    }

    public function testNativeReturnsIndependentNestedContainers(): void
    {
        $input = new stdClass();
        $input->child = new stdClass();
        $input->child->value = 'original';
        $node = new DocumentNode($input);
        $first = $node->native();
        self::assertInstanceOf(stdClass::class, $first);
        self::assertInstanceOf(stdClass::class, $first->child);
        $first->child->value = 'modified';
        $second = $node->native();
        self::assertEquals($input, $second);
    }

    public function testNativeRetainsAliasesWithinEachIndependentCopy(): void
    {
        $shared = new stdClass();
        $shared->value = 0;
        $root = new stdClass();
        $root->first = $shared;
        $root->second = $shared;
        $node = new DocumentNode($root);
        $first = $node->native();
        $second = $node->native();
        self::assertInstanceOf(stdClass::class, $first);
        self::assertInstanceOf(stdClass::class, $second);
        self::assertSame($first->first, $first->second);
        self::assertNotSame($first->first, $second->first);
    }

    public function testNativePreservesRecursiveObjectReferences(): void
    {
        $root = new stdClass();
        $root->self = $root;
        $copy = (new DocumentNode($root))->native();
        self::assertInstanceOf(stdClass::class, $copy);
        self::assertNotSame($root, $copy);
        self::assertSame($copy, $copy->self);
    }
}
