<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Mutation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Mutation\MutationInspector;

/**
 * @covers \Toolkit\PhpStan\Rule\Mutation\MutationInspector
 */
#[CoversClass(MutationInspector::class)]
final class MutationInspectorTest extends TestCase
{
    public function testViolationsReportsAnUndeclaredParameterWrite(): void
    {
        $declaration = [
            'key' => 'function:run', 'symbol' => 'run()', 'line' => 2, 'file' => 'run.php',
            'parameters' => [['name' => 'value', 'variadic' => false]], 'mutable' => [],
            'this' => false, 'global' => false, 'problems' => [], 'prototypes' => [], 'static' => false,
        ];
        $operation = ['kind' => 'mutation', 'caller' => 'function:run', 'line' => 5, 'file' => 'run.php', 'root' => 'var:value'];

        self::assertSame('customRules.mutationUndeclared', (new MutationInspector())->violations([$declaration], [$operation])[0]['identifier']);
    }

    public function testContractProblemsNamesInvalidSyntax(): void
    {
        $declaration = [
            'key' => 'function:run', 'symbol' => 'run()', 'line' => 2, 'file' => 'run.php',
            'parameters' => [], 'mutable' => [], 'this' => false, 'global' => false,
            'problems' => ['Fix syntax.'], 'prototypes' => [], 'static' => false,
        ];

        self::assertStringContainsString('Fix syntax.', (new MutationInspector())->contractProblems([$declaration])[0]['message']);
    }

    public function testOverrideProblemsRejectsWidening(): void
    {
        $parent = [
            'key' => 'method:base::run', 'symbol' => 'Base::run()', 'line' => 2, 'file' => 'base.php',
            'parameters' => [], 'mutable' => [], 'this' => false, 'global' => false,
            'problems' => [], 'prototypes' => [], 'static' => false,
        ];
        $child = $parent + [];
        $child['key'] = 'method:child::run';
        $child['symbol'] = 'Child::run()';
        $child['this'] = true;
        $child['prototypes'] = ['method:base::run'];
        $index = [$parent['key'] => $parent, $child['key'] => $child];

        self::assertCount(1, (new MutationInspector())->overrideProblems([$parent, $child], $index, [
            $parent['key'] => [], $child['key'] => ['this' => true],
        ]));
    }

    public function testActualEffectsMapsDirectRoots(): void
    {
        $declaration = [
            'key' => 'function:run', 'symbol' => 'run()', 'line' => 2, 'file' => 'run.php',
            'parameters' => [], 'mutable' => [], 'this' => false, 'global' => false,
            'problems' => [], 'prototypes' => [], 'static' => false,
        ];
        $operation = ['kind' => 'mutation', 'caller' => 'function:run', 'line' => 5, 'file' => 'run.php', 'root' => 'global'];

        self::assertArrayHasKey('global', (new MutationInspector())->actualEffects(['function:run' => $declaration], [$operation], [])['function:run']);
    }

    public function testPermissionSitesPlacesEffectsAtTheCall(): void
    {
        $call = [
            'kind' => 'call', 'caller' => 'function:run', 'line' => 7, 'file' => 'run.php',
            'callees' => ['function:change'], 'receiver' => 'local', 'arguments' => [],
        ];

        self::assertSame(7, (new MutationInspector())->permissionSites(['global' => true], $call)['global']['line']);
    }

    public function testMappedCallEffectsKeepsGlobalStateGlobal(): void
    {
        $declaration = [
            'key' => 'function:run', 'symbol' => 'run()', 'line' => 2, 'file' => 'run.php',
            'parameters' => [], 'mutable' => [], 'this' => false, 'global' => false,
            'problems' => [], 'prototypes' => [], 'static' => false,
        ];
        $call = [
            'kind' => 'call', 'caller' => 'function:run', 'line' => 7, 'file' => 'run.php',
            'callees' => ['function:change'], 'receiver' => 'local', 'arguments' => [],
        ];

        self::assertSame(['global'], (new MutationInspector())->mappedCallEffects('global', $call, $declaration, $declaration, []));
    }

    public function testRootEffectsFollowsParameterAliases(): void
    {
        $declaration = [
            'key' => 'function:run', 'symbol' => 'run()', 'line' => 2, 'file' => 'run.php',
            'parameters' => [['name' => 'value', 'variadic' => false]], 'mutable' => [],
            'this' => false, 'global' => false, 'problems' => [], 'prototypes' => [], 'static' => false,
        ];

        self::assertSame(['arg:0'], (new MutationInspector())->rootEffects('var:alias', $declaration, ['alias' => ['var:value']], []));
    }

    public function testOwnPermissionsMapsNamesToPositions(): void
    {
        $declaration = [
            'key' => 'function:run', 'symbol' => 'run()', 'line' => 2, 'file' => 'run.php',
            'parameters' => [['name' => 'value', 'variadic' => false]], 'mutable' => ['value'],
            'this' => false, 'global' => true, 'problems' => [], 'prototypes' => [], 'static' => false,
        ];

        self::assertSame(['arg:0' => true, 'global' => true], (new MutationInspector())->ownPermissions($declaration));
    }

    public function testPermissionsInheritsPrototypeEffects(): void
    {
        $parent = [
            'key' => 'method:base::run', 'symbol' => 'Base::run()', 'line' => 2, 'file' => 'base.php',
            'parameters' => [], 'mutable' => [], 'this' => true, 'global' => false,
            'problems' => [], 'prototypes' => [], 'static' => false,
        ];
        $child = $parent + [];
        $child['key'] = 'method:child::run';
        $child['this'] = false;
        $child['prototypes'] = ['method:base::run'];
        $index = [$parent['key'] => $parent, $child['key'] => $child];
        $cache = [];

        self::assertSame(['this' => true], (new MutationInspector())->permissions($child['key'], $index, $cache, []));
    }

    public function testUndeclaredProvidesTheRequiredAnnotation(): void
    {
        $declaration = [
            'key' => 'method:box::run', 'symbol' => 'Box::run()', 'line' => 2, 'file' => 'box.php',
            'parameters' => [], 'mutable' => [], 'this' => false, 'global' => false,
            'problems' => [], 'prototypes' => [], 'static' => false,
        ];

        self::assertStringContainsString('@mutation $this', (new MutationInspector())->undeclared(
            $declaration,
            'this',
            ['line' => 5, 'file' => 'box.php'],
        )['message']);
    }

    public function testEffectLabelNamesTheParameter(): void
    {
        $declaration = [
            'key' => 'function:run', 'symbol' => 'run()', 'line' => 2, 'file' => 'run.php',
            'parameters' => [['name' => 'value', 'variadic' => false]], 'mutable' => [],
            'this' => false, 'global' => false, 'problems' => [], 'prototypes' => [], 'static' => false,
        ];

        self::assertSame('$value', (new MutationInspector())->effectLabel($declaration, 'arg:0'));
    }
}
