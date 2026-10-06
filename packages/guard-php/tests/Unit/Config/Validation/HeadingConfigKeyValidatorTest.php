<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Validation;

use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Validation\HeadingConfigKeyValidator
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(HeadingConfigKeyValidator::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
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
