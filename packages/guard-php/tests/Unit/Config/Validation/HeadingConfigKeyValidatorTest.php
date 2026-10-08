<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Validation;

use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Diagnostic\PolicyException;
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
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
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
