<?php

declare(strict_types=1);

namespace Tests\Unit\Policy;

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
use Guard\Policy\Diagnostic\FieldMessage;
use Guard\Policy\FieldConstraints;
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use Guard\Policy\PolicyBinding;
use Guard\Policy\Rule;
use Guard\Policy\RuleEvaluator;
use Guard\Structure\Document\Constraint;
use Guard\Structure\Document\DataDocument;
use Guard\Structure\Document\DocumentFailure;
use Guard\Structure\Document\DocumentNode;
use Guard\Structure\Document\Json5Reader;
use Guard\Structure\Document\PhpConfigReader;
use Guard\Structure\Document\PhpDocument;
use Guard\Structure\Document\Pointer;
use Guard\Structure\Document\TomlEncoder;
use Guard\Structure\Document\XmlDocument;
use Guard\Structure\ParsedDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\PolicyBinding
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Structure\Document\DataDocument
 * @uses \Guard\Structure\Document\DocumentFailure
 * @uses \Guard\Structure\Document\DocumentNode
 * @uses \Guard\Structure\Document\Json5Reader
 * @uses \Guard\Structure\Document\PhpConfigReader
 * @uses \Guard\Structure\Document\PhpDocument
 * @uses \Guard\Structure\Document\Pointer
 * @uses \Guard\Structure\Document\Selection
 * @uses \Guard\Structure\Document\TomlEncoder
 * @uses \Guard\Structure\Document\XmlDocument
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Structure\Document\Constraint
 * @uses \Guard\Policy\FieldConstraints
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Policy\RuleEvaluator
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Structure\ParsedDocument
 * @uses \Guard\Policy\Diagnostic\FieldMessage
 */
#[CoversClass(PolicyBinding::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(DataDocument::class)]
#[UsesClass(DocumentFailure::class)]
#[UsesClass(DocumentNode::class)]
#[UsesClass(Json5Reader::class)]
#[UsesClass(PhpConfigReader::class)]
#[UsesClass(PhpDocument::class)]
#[UsesClass(Pointer::class)]
#[UsesClass(\Guard\Structure\Document\Selection::class)]
#[UsesClass(TomlEncoder::class)]
#[UsesClass(XmlDocument::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(Constraint::class)]
#[UsesClass(FieldConstraints::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Rule::class)]
#[UsesClass(RuleEvaluator::class)]
#[UsesClass(Finding::class)]
#[UsesClass(ParsedDocument::class)]
#[UsesClass(FieldMessage::class)]
final class PolicyBindingTest extends TestCase
{
    /**

     */
    public function testDoesNotImposeAFileCategoryOnRegisteredPolicies(): void
    {
        $binding = new PolicyBinding('custom', new FieldConstraints([]), 42);
        self::assertSame('custom', $binding->id);
        self::assertSame(42, $binding->reportOrder);
    }
}
