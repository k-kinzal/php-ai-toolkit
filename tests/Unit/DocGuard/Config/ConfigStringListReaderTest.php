<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Config\ConfigStringListReader;
use Toolkit\DocGuard\DocGuardException;

/**
 * @covers \Toolkit\DocGuard\Config\ConfigStringListReader
 * @uses \Toolkit\DocGuard\DocGuardException
 */
#[CoversClass(ConfigStringListReader::class)]
#[UsesClass(DocGuardException::class)]
final class ConfigStringListReaderTest extends TestCase
{
    public function testReadReturnsStringsOrDefault(): void
    {
        self::assertSame(['*.md', 'docs/**/*.md'], (new ConfigStringListReader())->read(['scan' => ['*.md', 'docs/**/*.md']], 'scan', [], ''));
        self::assertSame(['path'], (new ConfigStringListReader())->read([], 'order_by', ['path'], 'report'));
    }

    public function testReadRejectsScalar(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml: "scan" must be a list of strings.');

        (new ConfigStringListReader())->read(['scan' => '*.md'], 'scan', [], '');
    }

    public function testReadRejectsEmptyEntries(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml: "report.order_by" must be a list of strings.');

        (new ConfigStringListReader())->read(['order_by' => ['path', '']], 'order_by', [], 'report');
    }
}
