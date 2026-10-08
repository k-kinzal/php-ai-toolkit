<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Architecture;

use Override;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyCallResolver;
use Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyPath;
use Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyPathExpression;
use Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyRestrictions;
use Toolkit\PhpStan\Rule\Architecture\Dependency\FileDependencyResolver;
use Toolkit\PhpStan\Rule\Architecture\Dependency\SymbolDependencyResolver;
use Toolkit\PhpStan\Rule\Architecture\ForbidDependencyRule;
use Toolkit\PhpStan\Rule\Visibility\TypeNameReader;
use Toolkit\PhpStan\Rule\Visibility\VisibilityReferenceCollector;

/**
 * @extends RuleTestCase<ForbidDependencyRule>
 * @covers \Toolkit\PhpStan\Rule\Architecture\ForbidDependencyRule
 * @uses \Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyCallResolver
 * @uses \Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyPath
 * @uses \Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyPathExpression
 * @uses \Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyRestrictions
 * @uses \Toolkit\PhpStan\Rule\Architecture\Dependency\FileDependencyResolver
 * @uses \Toolkit\PhpStan\Rule\Architecture\Dependency\SymbolDependencyResolver
 * @uses \Toolkit\PhpStan\Rule\Visibility\TypeNameReader
 * @uses \Toolkit\PhpStan\Rule\Visibility\VisibilityReferenceCollector
 * @medium
 */
#[CoversClass(ForbidDependencyRule::class)]
#[UsesClass(DependencyCallResolver::class)]
#[UsesClass(DependencyPath::class)]
#[UsesClass(DependencyPathExpression::class)]
#[UsesClass(DependencyRestrictions::class)]
#[UsesClass(FileDependencyResolver::class)]
#[UsesClass(SymbolDependencyResolver::class)]
#[UsesClass(TypeNameReader::class)]
#[UsesClass(VisibilityReferenceCollector::class)]
#[Medium]
final class ForbidDependencyRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    #[Override]
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../../../rules.neon',
            __DIR__ . '/../../../../../tests/PhpStan/ForbidDependency/phpstan.neon',
        ];
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new ForbidDependencyRule(
            self::createReflectionProvider(),
            __DIR__ . '/../../../../../tests/PhpStan/ForbidDependency/Project',
            DependencyRestrictions::DEFAULTS,
            [
                'Tests\PhpStan\ForbidDependency\Project\src\Reader::load' => ['position' => 0, 'name' => 'path'],
                'Tests\PhpStan\ForbidDependency\Project\src\Reader::read' => ['position' => 0, 'name' => 'path'],
                'Tests\PhpStan\ForbidDependency\Project\src\read_config' => ['position' => 0, 'name' => 'path'],
            ],
        );
    }

    public function testProcessNodeFindsAliasedSymbolsAndTypedInstanceCalls(): void
    {
        $class = 'Tests\PhpStan\ForbidDependency\Project\examples\ExampleService';
        $message = static fn (string $symbol): string => sprintf(
            'Forbidden dependency from "tests/symbols.php" to "examples/ExampleService.php" via %s (target pattern "*/**"). Remove the reference or move the referenced code/data to an allowed path.',
            $symbol,
        );
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/ForbidDependency/Project/tests/symbols.php'], [
            [$message($class . '::__construct()'), 9],
            [$message($class . '::create()'), 10],
            [$message($class), 11],
            [$message($class), 12],
            [$message('Tests\PhpStan\ForbidDependency\Project\examples\sample()'), 13],
            [$message('Tests\PhpStan\ForbidDependency\Project\examples\SAMPLE'), 14],
            [$message($class), 16],
            [$message($class), 16],
            [$message($class . '::run()'), 18],
            [$message($class), 19],
            [$message($class), 20],
            [$message($class), 21],
            [$message($class . '::__construct()'), 22],
        ]);
    }

    public function testGetNodeTypeChecksReferencesThroughoutTheSyntaxTree(): void
    {
        self::assertSame(\PhpParser\Node::class, $this->getRule()->getNodeType());
    }

    public function testDistributedRulesEnableDependencyChecksByDefault(): void
    {
        self::assertNotEmpty(array_filter(
            self::getContainer()->getServicesByTag('phpstan.rules.rule'),
            static fn ($rule): bool => $rule instanceof ForbidDependencyRule,
        ));
    }

    public function testProcessNodeFindsInheritanceTraitsAttributesAndProperties(): void
    {
        $message = static fn (string $class): string => sprintf(
            'Forbidden dependency from "tests/Derived.php" to "examples/%s.php" via Tests\PhpStan\ForbidDependency\Project\examples\%s (target pattern "*/**"). Remove the reference or move the referenced code/data to an allowed path.',
            $class,
            $class,
        );
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/ForbidDependency/Project/tests/Derived.php'], [
            [$message('ExampleAttribute'), 10],
            [$message('ExampleContract'), 10],
            [$message('ExampleService'), 10],
            [$message('ExampleTrait'), 13],
            [$message('ExampleService'), 15],
        ]);
    }

    public function testProcessNodeFindsIncludesBuiltinsAndRegisteredFileReaders(): void
    {
        $message = static fn (string $target, string $reader, string $pattern = '*/**'): string => sprintf(
            'Forbidden dependency from "tests/reads.php" to "%s" via %s (target pattern "%s"). Remove the reference or move the referenced code/data to an allowed path.',
            $target,
            $reader,
            $pattern,
        );
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/ForbidDependency/Project/tests/reads.php'], [
            [$message('examples/ExampleService.php', 'include/require'), 9],
            [$message('examples/ExampleContract.php', 'include/require'), 10],
            [$message('examples/input.json', 'file_get_contents()'), 11],
            [$message('examples/input.json', 'file_get_contents()'), 12],
            [$message('examples/input.json', 'fopen()'), 13],
            [$message('examples/input.json', 'file()'), 14],
            [$message('examples/input.json', 'readfile()'), 15],
            [$message('examples/input.json', 'hash_file()'), 16],
            [$message('examples/input.json', 'SplFileObject::__construct()'), 17],
            [$message('examples/input.json', 'Tests\PhpStan\ForbidDependency\Project\src\Reader::load()'), 18],
            [$message('examples/input.json', 'Tests\PhpStan\ForbidDependency\Project\src\Reader::read()'), 19],
            [$message('examples/input.json', 'Tests\PhpStan\ForbidDependency\Project\src\read_config()'), 20],
            [$message('examples/input.json', 'file_get_contents()'), 21],
            [$message('example/local.php', 'include/require'), 22],
        ]);
    }

    public function testProcessNodeAllowsDocumentationUncertainPathsAndPermittedDirectories(): void
    {
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/ForbidDependency/Project/tests/allowed.php'], []);
    }

    public function testProcessNodeAllowsExamplesToUseTheirOwnFilesAndProductionCode(): void
    {
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/ForbidDependency/Project/example/local.php'], []);
    }

    public function testProcessNodeForbidsProductionDependenciesOnTestsAndExamples(): void
    {
        $message = static fn (string $target): string => sprintf(
            'Forbidden dependency from "src/dependencies.php" to "%s" via file_get_contents() (target pattern "*/**"). Remove the reference or move the referenced code/data to an allowed path.',
            $target,
        );
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/ForbidDependency/Project/src/dependencies.php'], [
            [$message('tests/input.json'), 8],
            [$message('examples/input.json'), 9],
        ]);
    }

    public function testProcessNodeForbidsExamplesFromReusingOtherRootDirectories(): void
    {
        $message = static fn (string $target): string => sprintf(
            'Forbidden dependency from "examples/dependencies.php" to "%s" via file_get_contents() (target pattern "*/**"). Remove the reference or move the referenced code/data to an allowed path.',
            $target,
        );
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/ForbidDependency/Project/examples/dependencies.php'], [
            [$message('example/local.php'), 7],
            [$message('tests/input.json'), 8],
        ]);
    }

    public function testProcessNodeAutomaticallyAppliesTheBoundaryToNewRootDirectories(): void
    {
        $message = static fn (string $target): string => sprintf(
            'Forbidden dependency from "bench/dependencies.php" to "%s" via file_get_contents() (target pattern "*/**"). Remove the reference or move the referenced code/data to an allowed path.',
            $target,
        );
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/ForbidDependency/Project/bench/dependencies.php'], [
            [$message('tests/input.json'), 7],
            [$message('examples/input.json'), 8],
        ]);
    }

    public function testProcessNodeForbidsRootFixturesAndTestSupport(): void
    {
        $message = static fn (string $target): string => sprintf(
            'Forbidden dependency from "tests/other_directories.php" to "%s" via file_get_contents() (target pattern "*/**"). Remove the reference or move the referenced code/data to an allowed path.',
            $target,
        );
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/ForbidDependency/Project/tests/other_directories.php'], [
            [$message('fixtures/input.json'), 5],
            [$message('examples-backup/input.json'), 6],
            [$message('test-support/input.json'), 7],
            [str_replace('file_get_contents()', 'include/require', $message('test-support/bootstrap.php')), 8],
        ]);
    }
}
