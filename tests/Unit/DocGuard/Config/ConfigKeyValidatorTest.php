<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Config\ConfigKeyValidator;
use Toolkit\DocGuard\DocGuardException;

/**
 * @covers \Toolkit\DocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\DocGuard\DocGuardException
 */
#[CoversClass(ConfigKeyValidator::class)]
#[UsesClass(DocGuardException::class)]
final class ConfigKeyValidatorTest extends TestCase
{
    public function testRejectUnknownAcceptsKnownKeys(): void
    {
        $this->expectNotToPerformAssertions();

        (new ConfigKeyValidator())->rejectUnknown(['documents' => [], 'scan' => []], ['documents', 'scan', 'report'], 'top-level');
    }

    public function testRejectUnknownNamesTheUnsupportedKey(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml: "report" contains unsupported key "format". Supported keys: reporter, order_by.');

        (new ConfigKeyValidator())->rejectUnknown(['format' => 'ai'], ['reporter', 'order_by'], 'report');
    }

    public function testRejectUnknownRejectsListKeys(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('contains unsupported key "0"');

        (new ConfigKeyValidator())->rejectUnknown(['README.md'], ['headings'], 'documents.README.md');
    }
}
