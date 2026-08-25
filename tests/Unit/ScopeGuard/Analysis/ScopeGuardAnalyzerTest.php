<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Analysis\AnalysisResult;
use Toolkit\ScopeGuard\Analysis\Declaration\ClassLikeKind;
use Toolkit\ScopeGuard\Analysis\Declaration\Declaration;
use Toolkit\ScopeGuard\Analysis\Declaration\DeclarationCollector;
use Toolkit\ScopeGuard\Analysis\Declaration\DeclarationIndex;
use Toolkit\ScopeGuard\Analysis\Parse\FileNamespaces;
use Toolkit\ScopeGuard\Analysis\Parse\NodeWalker;
use Toolkit\ScopeGuard\Analysis\Parse\PhpParserBridge;
use Toolkit\ScopeGuard\Analysis\Parse\SourceFileParser;
use Toolkit\ScopeGuard\Analysis\ProjectScan;
use Toolkit\ScopeGuard\Analysis\ProjectScanner;
use Toolkit\ScopeGuard\Analysis\Reference\Reference;
use Toolkit\ScopeGuard\Analysis\Reference\ReferenceCollector;
use Toolkit\ScopeGuard\Analysis\Reference\TypeNameReader;
use Toolkit\ScopeGuard\Analysis\Scope\ExemptNamespaces;
use Toolkit\ScopeGuard\Analysis\Scope\NamespaceLineage;
use Toolkit\ScopeGuard\Analysis\Scope\ScopeProblemReader;
use Toolkit\ScopeGuard\Analysis\Scope\VisibilityScope;
use Toolkit\ScopeGuard\Analysis\Scope\VisibilityScopeResolver;
use Toolkit\ScopeGuard\Analysis\Scope\VisibilityTagParser;
use Toolkit\ScopeGuard\Analysis\ScopeChecker;
use Toolkit\ScopeGuard\Analysis\ScopeGuardAnalyzer;
use Toolkit\ScopeGuard\Analysis\ScopeViolationBuilder;
use Toolkit\ScopeGuard\Analysis\Violation;
use Toolkit\ScopeGuard\Config\ReportConfig;
use Toolkit\ScopeGuard\Config\ScopeGuardConfig;
use Toolkit\ScopeGuard\Filesystem\PhpFileFinder;
use Toolkit\ScopeGuard\Filesystem\PhpFileInclusionPolicy;
use Toolkit\ScopeGuard\Filesystem\PhpPathFileCollector;
use Toolkit\ScopeGuard\Filesystem\ScopeGuardPathResolver;
use Toolkit\ScopeGuard\ScopeGuardException;

/**
 * @covers \Toolkit\ScopeGuard\Analysis\ScopeGuardAnalyzer
 * @uses \Toolkit\ScopeGuard\Analysis\AnalysisResult
 * @uses \Toolkit\ScopeGuard\Analysis\Declaration\ClassLikeKind
 * @uses \Toolkit\ScopeGuard\Analysis\Declaration\Declaration
 * @uses \Toolkit\ScopeGuard\Analysis\Declaration\DeclarationCollector
 * @uses \Toolkit\ScopeGuard\Analysis\Declaration\DeclarationIndex
 * @uses \Toolkit\ScopeGuard\Analysis\Scope\ExemptNamespaces
 * @uses \Toolkit\ScopeGuard\Analysis\Parse\FileNamespaces
 * @uses \Toolkit\ScopeGuard\Analysis\Scope\NamespaceLineage
 * @uses \Toolkit\ScopeGuard\Analysis\Parse\NodeWalker
 * @uses \Toolkit\ScopeGuard\Filesystem\PhpFileFinder
 * @uses \Toolkit\ScopeGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \Toolkit\ScopeGuard\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\ScopeGuard\Filesystem\PhpPathFileCollector
 * @uses \Toolkit\ScopeGuard\Analysis\ProjectScan
 * @uses \Toolkit\ScopeGuard\Analysis\ProjectScanner
 * @uses \Toolkit\ScopeGuard\Analysis\Reference\Reference
 * @uses \Toolkit\ScopeGuard\Analysis\Reference\ReferenceCollector
 * @uses \Toolkit\ScopeGuard\Config\ReportConfig
 * @uses \Toolkit\ScopeGuard\Analysis\ScopeChecker
 * @uses \Toolkit\ScopeGuard\Config\ScopeGuardConfig
 * @uses \Toolkit\ScopeGuard\Filesystem\ScopeGuardPathResolver
 * @uses \Toolkit\ScopeGuard\Analysis\Scope\ScopeProblemReader
 * @uses \Toolkit\ScopeGuard\Analysis\ScopeViolationBuilder
 * @uses \Toolkit\ScopeGuard\Analysis\Parse\SourceFileParser
 * @uses \Toolkit\ScopeGuard\Analysis\Reference\TypeNameReader
 * @uses \Toolkit\ScopeGuard\Analysis\Scope\VisibilityScope
 * @uses \Toolkit\ScopeGuard\Analysis\Scope\VisibilityScopeResolver
 * @uses \Toolkit\ScopeGuard\Analysis\Scope\VisibilityTagParser
 * @uses \Toolkit\ScopeGuard\Analysis\Violation
 */
#[CoversClass(ScopeGuardAnalyzer::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(ClassLikeKind::class)]
#[UsesClass(Declaration::class)]
#[UsesClass(DeclarationCollector::class)]
#[UsesClass(DeclarationIndex::class)]
#[UsesClass(ExemptNamespaces::class)]
#[UsesClass(FileNamespaces::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(NodeWalker::class)]
#[UsesClass(PhpFileFinder::class)]
#[UsesClass(PhpFileInclusionPolicy::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PhpPathFileCollector::class)]
#[UsesClass(ProjectScan::class)]
#[UsesClass(ProjectScanner::class)]
#[UsesClass(Reference::class)]
#[UsesClass(ReferenceCollector::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ScopeChecker::class)]
#[UsesClass(ScopeGuardConfig::class)]
#[UsesClass(ScopeGuardPathResolver::class)]
#[UsesClass(ScopeProblemReader::class)]
#[UsesClass(ScopeViolationBuilder::class)]
#[UsesClass(SourceFileParser::class)]
#[UsesClass(TypeNameReader::class)]
#[UsesClass(VisibilityScope::class)]
#[UsesClass(VisibilityScopeResolver::class)]
#[UsesClass(VisibilityTagParser::class)]
#[UsesClass(Violation::class)]
#[Medium]
final class ScopeGuardAnalyzerTest extends TestCase
{
    /**
     * @dataProvider providerFixtureConfig
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerFixtureConfig')]
    public function testAnalyzeReportsEveryViolationOfTheFixtureProject(ScopeGuardConfig $config): void
    {
        self::assertSame(20, (new ScopeGuardAnalyzer())->analyze($config)->violationCount());
    }

    /**
     * @dataProvider providerFixtureConfig
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerFixtureConfig')]
    public function testAnalyzeCountsScopedDeclarations(ScopeGuardConfig $config): void
    {
        self::assertSame(15, (new ScopeGuardAnalyzer())->analyze($config)->scopedDeclarationCount);
    }

    /**
     * @dataProvider providerExemptFixtureConfig
     *
     * @throws ScopeGuardException
     */
    #[DataProvider('providerExemptFixtureConfig')]
    public function testAnalyzeSkipsReferencesFromExemptNamespaces(ScopeGuardConfig $config): void
    {
        self::assertSame(5, (new ScopeGuardAnalyzer())->analyze($config)->violationCount());
    }

    public function testScopedDeclarationCountCountsOnlyTaggedDeclarations(): void
    {
        $scan = new ProjectScan(new DeclarationIndex(), [], 0);

        self::assertSame(0, (new ScopeGuardAnalyzer())->scopedDeclarationCount($scan));
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

    /**
     * @return array<string, array{ScopeGuardConfig}>
     */
    public static function providerExemptFixtureConfig(): array
    {
        return ['the fixture project with an exempt namespace' => [new ScopeGuardConfig(
            __DIR__ . '/../../../Fixture/ScopeGuard/project',
            ['src'],
            [],
            ['Tests\\Fixture\\ScopeGuard\\Outside'],
            new ReportConfig('ai', ['path', 'line']),
        )]];
    }
}
