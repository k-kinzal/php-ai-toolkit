<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis;

use PhpAiToolkit\ScopeGuard\Analysis\AnalysisResult;
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
use PhpAiToolkit\ScopeGuard\Analysis\Scope\ExemptNamespaces;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\NamespaceLineage;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\ScopeProblemReader;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScope;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScopeResolver;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityTagParser;
use PhpAiToolkit\ScopeGuard\Analysis\ScopeChecker;
use PhpAiToolkit\ScopeGuard\Analysis\ScopeGuardAnalyzer;
use PhpAiToolkit\ScopeGuard\Analysis\ScopeViolationBuilder;
use PhpAiToolkit\ScopeGuard\Analysis\Violation;
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
