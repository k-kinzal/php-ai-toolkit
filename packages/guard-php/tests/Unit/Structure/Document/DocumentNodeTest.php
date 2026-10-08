<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Document;

use DateTimeImmutable;
use Guard\Structure\Document\DocumentNode;
use PHPUnit\Framework\Attributes\CoversClass;
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
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\Finding::class)]
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
