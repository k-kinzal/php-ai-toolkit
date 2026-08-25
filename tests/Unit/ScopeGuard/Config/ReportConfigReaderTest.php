<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Config;

use PhpAiToolkit\ScopeGuard\Config\ConfigScalarReader;
use PhpAiToolkit\ScopeGuard\Config\ConfigStringListReader;
use PhpAiToolkit\ScopeGuard\Config\ReportConfig;
use PhpAiToolkit\ScopeGuard\Config\ReportConfigReader;
use PhpAiToolkit\ScopeGuard\ScopeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\ScopeGuard\Config\ReportConfigReader
 * @uses \PhpAiToolkit\ScopeGuard\Config\ConfigScalarReader
 * @uses \PhpAiToolkit\ScopeGuard\Config\ConfigStringListReader
 * @uses \PhpAiToolkit\ScopeGuard\Config\ReportConfig
 * @uses \PhpAiToolkit\ScopeGuard\ScopeGuardException
 */
#[CoversClass(ReportConfigReader::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ScopeGuardException::class)]
final class ReportConfigReaderTest extends TestCase
{
    /**
     * @throws ScopeGuardException
     */
    public function testReadDefaultsToTheAiReporter(): void
    {
        self::assertSame('ai', (new ReportConfigReader())->read([])->reporter);
    }

    /**
     * @throws ScopeGuardException
     */
    public function testReadDefaultsToPathAndLineOrdering(): void
    {
        self::assertSame(['path', 'line'], (new ReportConfigReader())->read([])->orderBy);
    }

    /**
     * @throws ScopeGuardException
     */
    public function testReadAcceptsAConfiguredReporter(): void
    {
        self::assertSame('json', (new ReportConfigReader())->read(['reporter' => 'json'])->reporter);
    }

    /**
     * @throws ScopeGuardException
     */
    public function testReadRejectsAnUnknownReporter(): void
    {
        $this->expectException(ScopeGuardException::class);

        (new ReportConfigReader())->read(['reporter' => 'xml']);
    }

    /**
     * @throws ScopeGuardException
     */
    public function testReadRejectsAnUnknownOrderField(): void
    {
        $this->expectException(ScopeGuardException::class);

        (new ReportConfigReader())->read(['order_by' => ['size']]);
    }

    /**
     * @throws ScopeGuardException
     */
    public function testReadRejectsANonMapping(): void
    {
        $this->expectException(ScopeGuardException::class);

        (new ReportConfigReader())->read('ai');
    }
}
