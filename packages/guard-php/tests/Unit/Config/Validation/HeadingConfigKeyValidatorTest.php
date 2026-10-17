<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Validation;

use Guard\Config\Configuration;
use Guard\Config\Validation\HeadingConfigKeyValidator;
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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Validation\HeadingConfigKeyValidator
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
#[CoversClass(HeadingConfigKeyValidator::class)]
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
final class HeadingConfigKeyValidatorTest extends TestCase
{
    public function testRejectUnknownAcceptsKnownKeys(): void
    {
        $this->expectNotToPerformAssertions();

        (new HeadingConfigKeyValidator())->rejectUnknown(['documents' => [], 'scan' => []], ['documents', 'scan', 'report'], 'top-level');
    }

    public function testRejectUnknownNamesTheUnsupportedKey(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml: "report" contains unsupported key "format". Supported keys: reporter, order_by.');

        (new HeadingConfigKeyValidator())->rejectUnknown(['format' => 'ai'], ['reporter', 'order_by'], 'report');
    }

    public function testRejectUnknownRejectsListKeys(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('contains unsupported key "0"');

        (new HeadingConfigKeyValidator())->rejectUnknown(['README.md'], ['headings'], 'documents.README.md');
    }
}
