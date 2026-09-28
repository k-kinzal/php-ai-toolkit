<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Cli\DocGuardReporterOverride;
use Toolkit\DocGuard\Config\DeclaredHeading;
use Toolkit\DocGuard\Config\DocGuardConfig;
use Toolkit\DocGuard\Config\DocumentConfig;
use Toolkit\DocGuard\Config\ReportConfig;

/**
 * @covers \Toolkit\DocGuard\Cli\DocGuardReporterOverride
 * @uses \Toolkit\DocGuard\Config\DeclaredHeading
 * @uses \Toolkit\DocGuard\Config\DocGuardConfig
 * @uses \Toolkit\DocGuard\Config\DocumentConfig
 * @uses \Toolkit\DocGuard\Config\ReportConfig
 */
#[CoversClass(DocGuardReporterOverride::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DocGuardConfig::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(ReportConfig::class)]
final class DocGuardReporterOverrideTest extends TestCase
{
    public function testApplyReplacesReporterAndKeepsTheRest(): void
    {
        $documents = [new DocumentConfig('README.md', [], 6)];
        $config = new DocGuardConfig('/project', 'doc-guard.yaml', $documents, ['*.md'], new ReportConfig('ai', ['rule']));

        $overridden = (new DocGuardReporterOverride())->apply($config, 'json');

        self::assertSame('json', $overridden->report->reporter);
        self::assertSame(['rule'], $overridden->report->orderBy);
        self::assertSame($documents, $overridden->documents);
        self::assertSame(['*.md'], $overridden->scan);
        self::assertSame('doc-guard.yaml', $overridden->configName);
    }

    public function testApplyReturnsConfigWithoutOverride(): void
    {
        $config = new DocGuardConfig('/project', 'doc-guard.yaml', [new DocumentConfig('README.md', [], 6)], [], new ReportConfig('ai', ['rule']));

        self::assertSame($config, (new DocGuardReporterOverride())->apply($config, null));
    }
}
