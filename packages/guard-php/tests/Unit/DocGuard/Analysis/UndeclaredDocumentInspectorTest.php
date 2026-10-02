<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Analysis\UndeclaredDocumentInspector;
use Toolkit\DocGuard\Analysis\Violation;
use Toolkit\DocGuard\Analysis\ViolationFactory;
use Toolkit\DocGuard\Config\DeclaredHeading;
use Toolkit\DocGuard\Config\DocGuardConfig;
use Toolkit\DocGuard\Config\DocumentConfig;
use Toolkit\DocGuard\Config\ReportConfig;
use Toolkit\DocGuard\Filesystem\DocGuardPathResolver;
use Toolkit\DocGuard\Filesystem\MarkdownFileFinder;
use Toolkit\DocGuard\Markdown\Heading;

/**
 * @covers \Toolkit\DocGuard\Analysis\UndeclaredDocumentInspector
 * @uses \Toolkit\DocGuard\Config\DeclaredHeading
 * @uses \Toolkit\DocGuard\Config\DocGuardConfig
 * @uses \Toolkit\DocGuard\Filesystem\DocGuardPathResolver
 * @uses \Toolkit\DocGuard\Config\DocumentConfig
 * @uses \Toolkit\DocGuard\Markdown\Heading
 * @uses \Toolkit\DocGuard\Filesystem\MarkdownFileFinder
 * @uses \Toolkit\DocGuard\Config\ReportConfig
 * @uses \Toolkit\DocGuard\Analysis\Violation
 * @uses \Toolkit\DocGuard\Analysis\ViolationFactory
 */
#[CoversClass(UndeclaredDocumentInspector::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DocGuardConfig::class)]
#[UsesClass(DocGuardPathResolver::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(Heading::class)]
#[UsesClass(MarkdownFileFinder::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationFactory::class)]
final class UndeclaredDocumentInspectorTest extends TestCase
{
    public function testInspectReportsEachUndeclaredDocumentOnce(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-undeclared-' . uniqid('', true);
        mkdir($dir . '/docs', 0777, true);
        touch($dir . '/README.md');
        touch($dir . '/CONTRIBUTING.md');
        touch($dir . '/docs/guide.md');
        touch($dir . '/docs/development.md');
        $config = new DocGuardConfig(
            $dir,
            'doc-guard.yaml',
            [new DocumentConfig('README.md', [], 6), new DocumentConfig('docs/guide.md', [], 6)],
            ['*.md', 'docs/**/*.md', 'docs/*.md'],
            new ReportConfig('ai', ['path']),
        );

        $violations = (new UndeclaredDocumentInspector())->inspect($config);

        self::assertSame(
            [['CONTRIBUTING.md', 'undeclared_document'], ['docs/development.md', 'undeclared_document']],
            array_map(static fn (Violation $violation): array => [$violation->path, $violation->rule], $violations),
        );
        self::assertStringContainsString('"docs/**/*.md"', $violations[1]->message);
    }

    public function testInspectReportsNothingWithoutScanPatterns(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-undeclared-' . uniqid('', true);
        mkdir($dir);
        touch($dir . '/NOTES.md');
        $config = new DocGuardConfig($dir, 'doc-guard.yaml', [new DocumentConfig('README.md', [], 6)], [], new ReportConfig('ai', ['path']));

        self::assertSame([], (new UndeclaredDocumentInspector())->inspect($config));
    }
    public function testInspectExcludesOnlyTheNamedLocalDocuments(): void
    {
        $dir = sys_get_temp_dir() . '/docguard-local-' . uniqid('', true);
        mkdir($dir);
        touch($dir . '/README.md');
        touch($dir . '/AGENTS.md');
        touch($dir . '/NOTES.md');
        $config = new DocGuardConfig(
            $dir,
            'guard.yaml',
            [new DocumentConfig('README.md', [], 6)],
            ['*.md'],
            new ReportConfig('ai', ['path']),
            ['AGENTS.md'],
        );
        $violations = (new UndeclaredDocumentInspector())->inspect($config);
        self::assertCount(1, $violations);
        self::assertSame('NOTES.md', $violations[0]->path);
    }
}
