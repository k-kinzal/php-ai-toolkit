<?php

declare(strict_types=1);

namespace Tests\Unit\Extension;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Extension\Registry
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Document\DataDocument
 * @uses \Guard\Document\DocumentFailure
 * @uses \Guard\Document\DocumentNode
 * @uses \Guard\Document\Json5Reader
 * @uses \Guard\Document\PhpConfigReader
 * @uses \Guard\Document\PhpDocument
 * @uses \Guard\Document\Pointer
 * @uses \Guard\Document\Selection
 * @uses \Guard\Document\TomlEncoder
 * @uses \Guard\Document\XmlDocument
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\Constraint
 * @uses \Guard\Policy\FieldConstraints
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Policy\RuleEvaluator
 * @uses \Guard\Reporting\Finding
 * @uses \Guard\Structure\DocumentStructurer
 * @uses \Guard\Structure\ParsedDocument
 * @uses \Guard\Structure\Php\TokenParser
 * @uses \Guard\Structure\Php\Tokens
 * @uses \Guard\Structure\Source
 */
#[CoversClass(\Guard\Extension\Registry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\DataDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\DocumentFailure::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\DocumentNode::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\Json5Reader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\PhpConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\PhpDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\Pointer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\TomlEncoder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\XmlDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Extension\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Constraint::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\FieldConstraints::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\RuleEvaluator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\DocumentStructurer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\ParsedDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\TokenParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Tokens::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Source::class)]
final class RegistryTest extends TestCase
{
    public function testAddStructureRejectsDuplicateIdsWithoutReplacingTheOriginal(): void
    {
        $registry = new \Guard\Extension\Registry();
        $parser = new \Guard\Structure\Php\TokenParser();
        $registry->addStructure('tokens', $parser);
        self::assertSame(['tokens' => $parser], $registry->structures());
        $this->expectException(\Guard\Policy\PolicyException::class);
        $registry->addStructure('tokens', new \Guard\Structure\Php\TokenParser());
    }
    public function testAddPolicyRejectsDuplicateIds(): void
    {
        $registry = new \Guard\Extension\Registry();
        $policy = new \Guard\Policy\FieldConstraints([]);
        $registry->addPolicy('policy', $policy);
        $this->expectException(\Guard\Policy\PolicyException::class);
        $registry->addPolicy('policy', $policy);
    }
    public function testStructuresPreservesDistinctRegisteredFormats(): void
    {
        $registry = new \Guard\Extension\Registry();
        $registry->addStructure('json', new \Guard\Structure\DocumentStructurer('json'));
        $registry->addStructure('xml', new \Guard\Structure\DocumentStructurer('xml'));
        self::assertSame(['json', 'xml'], array_keys($registry->structures()));
    }
    public function testPoliciesKeepsRegistrationOrderIndependentlyOfReportOrder(): void
    {
        $registry = new \Guard\Extension\Registry();
        $registry->addPolicy('first', new \Guard\Policy\FieldConstraints([]), 20);
        $registry->addPolicy('second', new \Guard\Policy\FieldConstraints([]), 0);
        self::assertSame(['first', 'second'], array_map(static fn (\Guard\Extension\PolicyBinding $binding): string => $binding->id, $registry->policies()));
    }
    public function testAddStructureRejectsEmptyId(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        (new \Guard\Extension\Registry())->addStructure('', new \Guard\Structure\Php\TokenParser());
    }
    public function testAddPolicyRejectsEmptyId(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        (new \Guard\Extension\Registry())->addPolicy('', new \Guard\Policy\FieldConstraints([]));
    }
}
