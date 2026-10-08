<?php

declare(strict_types=1);

namespace Tests\Unit\Policy;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\FieldConstraints
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
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Structure\Document\Constraint
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Policy\RuleEvaluator
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Structure\ParsedDocument
 * @uses \Guard\Policy\Diagnostic\FieldMessage
 */
#[CoversClass(\Guard\Policy\FieldConstraints::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\DataDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\DocumentFailure::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\DocumentNode::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\Json5Reader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\PhpConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\PhpDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\Pointer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\TomlEncoder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\XmlDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\Constraint::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\RuleEvaluator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\Finding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\ParsedDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Diagnostic\FieldMessage::class)]
final class PolicyTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testInputsCanDeclareOnlyMetadataAndEvaluateWithoutAFileCollector(): void
    {
        $policy = new \Guard\Policy\FieldConstraints([]);
        $context = new \Guard\Policy\Context('/unused', '/unused/guard.yaml', false);
        self::assertSame([], $policy->inputs($context));
        self::assertSame([], $policy->evaluate(new \Guard\Input\InputSet([]), $context)->findings);
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testEvaluateReturnsAPlanWithoutWritingFiles(): void
    {
        $policy = new \Guard\Policy\FieldConstraints([]);
        $context = new \Guard\Policy\Context('/unused', '/unused/guard.yaml', true);
        self::assertSame([], $policy->evaluate(new \Guard\Input\InputSet([]), $context)->changes);
    }
}
