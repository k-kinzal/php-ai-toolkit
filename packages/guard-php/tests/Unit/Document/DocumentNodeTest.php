<?php

declare(strict_types=1);

namespace Tests\Unit\Document;

use DateTimeImmutable;
use Guard\Document\DocumentNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \Guard\Document\DocumentNode
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(DocumentNode::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
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
