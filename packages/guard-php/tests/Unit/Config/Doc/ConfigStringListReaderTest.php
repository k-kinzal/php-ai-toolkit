<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Doc;

use Guard\Config\Doc\ConfigStringListReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Doc\ConfigStringListReader
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ConfigStringListReader::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class ConfigStringListReaderTest extends TestCase
{
    public function testReadReturnsStringsOrDefault(): void
    {
        self::assertSame(['*.md', 'docs/**/*.md'], (new ConfigStringListReader())->read(['scan' => ['*.md', 'docs/**/*.md']], 'scan', [], ''));
        self::assertSame(['path'], (new ConfigStringListReader())->read([], 'order_by', ['path'], 'report'));
    }

    public function testReadRejectsScalar(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml: "scan" must be a list of strings.');

        (new ConfigStringListReader())->read(['scan' => '*.md'], 'scan', [], '');
    }

    public function testReadRejectsEmptyEntries(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml: "report.order_by" must be a list of strings.');

        (new ConfigStringListReader())->read(['order_by' => ['path', '']], 'order_by', [], 'report');
    }
}
