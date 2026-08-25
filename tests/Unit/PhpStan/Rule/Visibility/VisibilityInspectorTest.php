<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Visibility;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Visibility\Scope\NamespaceLineage;
use Toolkit\PhpStan\Rule\Visibility\Scope\ScopeProblemReader;
use Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityScope;
use Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityScopeResolver;
use Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityTagParser;
use Toolkit\PhpStan\Rule\Visibility\VisibilityDeclarationIndex;
use Toolkit\PhpStan\Rule\Visibility\VisibilityInspector;

/**
 * @covers \Toolkit\PhpStan\Rule\Visibility\VisibilityInspector
 * @uses \Toolkit\PhpStan\Rule\Visibility\Scope\NamespaceLineage
 * @uses \Toolkit\PhpStan\Rule\Visibility\Scope\ScopeProblemReader
 * @uses \Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityScope
 * @uses \Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityScopeResolver
 * @uses \Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityTagParser
 * @uses \Toolkit\PhpStan\Rule\Visibility\VisibilityDeclarationIndex
 */
#[CoversClass(VisibilityInspector::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(ScopeProblemReader::class)]
#[UsesClass(VisibilityScope::class)]
#[UsesClass(VisibilityScopeResolver::class)]
#[UsesClass(VisibilityTagParser::class)]
#[UsesClass(VisibilityDeclarationIndex::class)]
final class VisibilityInspectorTest extends TestCase
{
    public function testViolationsReportInvalidTagAndOutOfScopeReference(): void
    {
        $index = new VisibilityDeclarationIndex();
        $index->add('/Order.php', [
            'class' => ['className' => 'App\Domain\Order', 'memberName' => null, 'symbol' => 'App\Domain\Order', 'kind' => 'class', 'namespace' => 'App\Domain', 'docComment' => '/** @visibility namespace */', 'line' => 3],
            'parents' => [],
            'members' => [['className' => 'App\Domain\Order', 'memberName' => 'broken', 'symbol' => 'App\Domain\Order::broken()', 'kind' => 'method', 'namespace' => 'App\Domain', 'docComment' => '/** @visibility parrent */', 'line' => 8]],
        ]);
        $violations = (new VisibilityInspector([]))->violations($index, [[
            'className' => 'App\Domain\Order',
            'memberName' => null,
            'kind' => 'instantiation',
            'namespace' => 'App\Http',
            'line' => 11,
            'file' => '/Controller.php',
        ]]);

        self::assertCount(2, $violations);
        self::assertSame(VisibilityInspector::INVALID_SCOPE_IDENTIFIER, $violations[0]['identifier']);
        self::assertSame(VisibilityInspector::OUT_OF_SCOPE_IDENTIFIER, $violations[1]['identifier']);
    }

    public function testViolationsSkipExemptNamespaceReferences(): void
    {
        self::assertTrue((new VisibilityInspector(['Tests']))->isExempt('Tests\Unit\OrderTest'));
    }

    public function testDeclarationViolationsAcceptUntaggedDeclaration(): void
    {
        self::assertSame([], (new VisibilityInspector())->declarationViolations([
            'className' => 'App\Order',
            'memberName' => null,
            'symbol' => 'App\Order',
            'kind' => 'class',
            'namespace' => 'App',
            'docComment' => null,
            'line' => 3,
            'file' => '/Order.php',
        ]));
    }

    public function testReferenceViolationIgnoresDeclarationOutsideAnalysis(): void
    {
        self::assertNull((new VisibilityInspector())->referenceViolation(new VisibilityDeclarationIndex(), [
            'className' => 'Vendor\Client',
            'memberName' => null,
            'kind' => 'instantiation',
            'namespace' => 'App',
            'line' => 3,
            'file' => '/App.php',
        ]));
    }

    public function testIsExemptRejectsUnconfiguredNamespace(): void
    {
        self::assertFalse((new VisibilityInspector(['Tests']))->isExempt('App'));
    }

    public function testScopeOfResolvesDeclarationComment(): void
    {
        self::assertFalse((new VisibilityInspector())->scopeOf([
            'namespace' => 'App\Domain',
            'docComment' => '/** @visibility namespace */',
        ])->permits('App\Http'));
    }

    public function testOutOfScopeNamesWideningScope(): void
    {
        $violation = (new VisibilityInspector())->outOfScope(
            ['symbol' => 'App\Domain\Order', 'kind' => 'class', 'namespace' => 'App\Domain', 'docComment' => '/** @visibility namespace */'],
            ['kind' => 'instantiation', 'namespace' => 'App\Http', 'line' => 4, 'file' => '/Http.php'],
        );

        self::assertStringContainsString('@visibility App', $violation['message']);
    }

    public function testInvalidScopeBuildsDeclarationError(): void
    {
        $violation = (new VisibilityInspector())->invalidScope(
            ['file' => '/Order.php', 'line' => 3, 'symbol' => 'App\Order', 'kind' => 'class'],
            'parrent',
            'it names nothing',
        );

        self::assertSame(VisibilityInspector::INVALID_SCOPE_IDENTIFIER, $violation['identifier']);
    }

    public function testContradictoryScopesNamesBothChoices(): void
    {
        $violation = (new VisibilityInspector())->contradictoryScopes([
            'file' => '/Order.php',
            'line' => 3,
            'symbol' => 'App\Order',
            'kind' => 'class',
        ]);

        self::assertStringContainsString('Remove either', $violation['message']);
    }

    public function testDescribeNamespaceNamesGlobalNamespace(): void
    {
        self::assertSame('the global namespace', (new VisibilityInspector())->describeNamespace(''));
    }

    public function testWideningForReturnsSharedAncestor(): void
    {
        self::assertSame('App', (new VisibilityInspector())->wideningFor('App\Domain', 'App\Http'));
    }
}
