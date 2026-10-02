<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Config\DeclaredHeading;
use Toolkit\DocGuard\Config\DocGuardConfig;
use Toolkit\DocGuard\Config\DocumentConfig;
use Toolkit\DocGuard\Config\ReportConfig;

/**
 * @covers \Toolkit\DocGuard\Config\DocGuardConfig
 * @uses \Toolkit\DocGuard\Config\DeclaredHeading
 * @uses \Toolkit\DocGuard\Config\DocumentConfig
 * @uses \Toolkit\DocGuard\Config\ReportConfig
 */
#[CoversClass(DocGuardConfig::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(ReportConfig::class)]
final class DocGuardConfigTest extends TestCase
{
    public function testGetExposesResolvedConfiguration(): void
    {
        $documents = [new DocumentConfig('README.md', [], 6)];
        $report = new ReportConfig('ai', ['path']);
        $config = new DocGuardConfig('/project', 'doc-guard.yaml', $documents, ['*.md'], $report);

        self::assertSame('/project', $config->root);
        self::assertSame('doc-guard.yaml', $config->configName);
        self::assertSame($documents, $config->documents);
        self::assertSame(['*.md'], $config->scan);
        self::assertSame($report, $config->report);
    }
}
