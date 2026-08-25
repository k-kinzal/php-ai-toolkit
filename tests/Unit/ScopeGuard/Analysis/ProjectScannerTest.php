<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis;

use PhpAiToolkit\ScopeGuard\Analysis\Declaration\ClassLikeKind;
use PhpAiToolkit\ScopeGuard\Analysis\Declaration\Declaration;
use PhpAiToolkit\ScopeGuard\Analysis\Declaration\DeclarationCollector;
use PhpAiToolkit\ScopeGuard\Analysis\Declaration\DeclarationIndex;
use PhpAiToolkit\ScopeGuard\Analysis\Parse\FileNamespaces;
use PhpAiToolkit\ScopeGuard\Analysis\Parse\NodeWalker;
use PhpAiToolkit\ScopeGuard\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\ScopeGuard\Analysis\Parse\SourceFileParser;
use PhpAiToolkit\ScopeGuard\Analysis\ProjectScan;
use PhpAiToolkit\ScopeGuard\Analysis\ProjectScanner;
use PhpAiToolkit\ScopeGuard\Analysis\Reference\Reference;
use PhpAiToolkit\ScopeGuard\Analysis\Reference\ReferenceCollector;
use PhpAiToolkit\ScopeGuard\Analysis\Reference\TypeNameReader;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\NamespaceLineage;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScope;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScopeResolver;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityTagParser;
use PhpAiToolkit\ScopeGuard\Config\ReportConfig;
use PhpAiToolkit\ScopeGuard\Config\ScopeGuardConfig;
use PhpAiToolkit\ScopeGuard\Filesystem\PhpFileFinder;
use PhpAiToolkit\ScopeGuard\Filesystem\PhpFileInclusionPolicy;
use PhpAiToolkit\ScopeGuard\Filesystem\PhpPathFileCollector;
use PhpAiToolkit\ScopeGuard\Filesystem\ScopeGuardPathResolver;
use PhpAiToolkit\ScopeGuard\ScopeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\ScopeGuard\Analysis\ProjectScanner
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Declaration\ClassLikeKind
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Declaration\Declaration
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Declaration\DeclarationCollector
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Declaration\DeclarationIndex
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Parse\FileNamespaces
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\NamespaceLineage
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Parse\NodeWalker
 * @uses \PhpAiToolkit\ScopeGuard\Filesystem\PhpFileFinder
 * @uses \PhpAiToolkit\ScopeGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Parse\PhpParserBridge
 * @uses \PhpAiToolkit\ScopeGuard\Filesystem\PhpPathFileCollector
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\ProjectScan
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Reference\Reference
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Reference\ReferenceCollector
 * @uses \PhpAiToolkit\ScopeGuard\Config\ReportConfig
 * @uses \PhpAiToolkit\ScopeGuard\Config\ScopeGuardConfig
 * @uses \PhpAiToolkit\ScopeGuard\Filesystem\ScopeGuardPathResolver
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Parse\SourceFileParser
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Reference\TypeNameReader
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScope
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScopeResolver
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityTagParser
 */
#[CoversClass(ProjectScanner::class)]
#[UsesClass(ClassLikeKind::class)]
#[UsesClass(Declaration::class)]
#[UsesClass(DeclarationCollector::class)]
#[UsesClass(DeclarationIndex::class)]
#[UsesClass(FileNamespaces::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(NodeWalker::class)]
#[UsesClass(PhpFileFinder::class)]
#[UsesClass(PhpFileInclusionPolicy::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PhpPathFileCollector::class)]
#[UsesClass(ProjectScan::class)]
#[UsesClass(Reference::class)]
#[UsesClass(ReferenceCollector::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ScopeGuardConfig::class)]
#[UsesClass(ScopeGuardPathResolver::class)]
#[UsesClass(SourceFileParser::class)]
#[UsesClass(TypeNameReader::class)]
#[UsesClass(VisibilityScope::class)]
#[UsesClass(VisibilityScopeResolver::class)]
#[UsesClass(VisibilityTagParser::class)]
#[Medium]
final class ProjectScannerTest extends TestCase
{
    /**
     * @dataProvider providerFixtureConfig
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerFixtureConfig')]
    public function testScanCountsEveryAnalyzedFile(ScopeGuardConfig $config): void
    {
        self::assertSame(16, (new ProjectScanner())->scan($config)->fileCount);
    }

    /**
     * @dataProvider providerFixtureConfig
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerFixtureConfig')]
    public function testScanIndexesDeclarationsByFullyQualifiedName(ScopeGuardConfig $config): void
    {
        $scan = (new ProjectScanner())->scan($config);

        self::assertSame(
            'Tests\\Fixture\\ScopeGuard\\Package\\NamespaceScoped',
            $scan->index->classDeclaration('Tests\\Fixture\\ScopeGuard\\Package\\NamespaceScoped')?->symbol
        );
    }

    /**
     * @dataProvider providerFixtureConfig
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerFixtureConfig')]
    public function testScanResolvesImportedNamesInReferences(ScopeGuardConfig $config): void
    {
        $names = array_map(static fn (Reference $reference): string => $reference->className, (new ProjectScanner())->scan($config)->references);

        self::assertContains('Tests\\Fixture\\ScopeGuard\\Package\\NamespaceScoped', $names);
    }

    /**
     * @return array<string, array{ScopeGuardConfig}>
     */
    public static function providerFixtureConfig(): array
    {
        return ['the ScopeGuard fixture project' => [new ScopeGuardConfig(
            __DIR__ . '/../../../Fixture/ScopeGuard/project',
            ['src'],
            [],
            [],
            new ReportConfig('ai', ['path', 'line']),
        )]];
    }
}
