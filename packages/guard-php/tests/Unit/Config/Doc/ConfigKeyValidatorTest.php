<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Doc;

use Guard\Config\Doc\ConfigKeyValidator;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Doc\ConfigKeyValidator
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ConfigKeyValidator::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class ConfigKeyValidatorTest extends TestCase
{
    public function testRejectUnknownAcceptsKnownKeys(): void
    {
        $this->expectNotToPerformAssertions();

        (new ConfigKeyValidator())->rejectUnknown(['documents' => [], 'scan' => []], ['documents', 'scan', 'report'], 'top-level');
    }

    public function testRejectUnknownNamesTheUnsupportedKey(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml: "report" contains unsupported key "format". Supported keys: reporter, order_by.');

        (new ConfigKeyValidator())->rejectUnknown(['format' => 'ai'], ['reporter', 'order_by'], 'report');
    }

    public function testRejectUnknownRejectsListKeys(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('contains unsupported key "0"');

        (new ConfigKeyValidator())->rejectUnknown(['README.md'], ['headings'], 'documents.README.md');
    }
}
