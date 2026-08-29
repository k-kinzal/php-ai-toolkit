<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Mutation;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Mutation\CallableId;
use Toolkit\PhpStan\Rule\Mutation\MutationOperationCollector;

/**
 * @covers \Toolkit\PhpStan\Rule\Mutation\MutationOperationCollector
 */
#[CoversClass(MutationOperationCollector::class)]
#[UsesClass(CallableId::class)]
final class MutationOperationCollectorTest extends TestCase
{
    public function testGetNodeTypeReturnsAnyParserNode(): void
    {
        self::assertSame(\PhpParser\Node::class, (new MutationOperationCollector(self::createStub(ReflectionProvider::class)))->getNodeType());
    }

    public function testProcessNodeSkipsNodesOutsideCallables(): void
    {
        $collector = new MutationOperationCollector(self::createStub(ReflectionProvider::class));

        self::assertNull($collector->processNode(new \PhpParser\Node\Scalar\LNumber(1), self::createStub(Scope::class)));
    }

    public function testRecordsCollectsAssignmentAndAlias(): void
    {
        $collector = new MutationOperationCollector(self::createStub(ReflectionProvider::class));
        $assign = new \PhpParser\Node\Expr\Assign(
            new \PhpParser\Node\Expr\Variable('alias'),
            new \PhpParser\Node\Expr\Variable('input'),
        );

        self::assertCount(2, $collector->records($assign, self::createStub(Scope::class), 'function:run', 8));
    }

    public function testMutationsCollectsEveryTarget(): void
    {
        $collector = new MutationOperationCollector(self::createStub(ReflectionProvider::class));
        $records = $collector->mutations([
            new \PhpParser\Node\Expr\Variable('left'),
            new \PhpParser\Node\Expr\Variable('right'),
        ], 'function:run', 9);

        self::assertSame(['var:left', 'var:right'], [$records[0]['root'], $records[1]['root']]);
    }

    public function testAssignmentReturnsNullForAnotherNodeKind(): void
    {
        $collector = new MutationOperationCollector(self::createStub(ReflectionProvider::class));

        self::assertNull($collector->assignment(new \PhpParser\Node\Scalar\LNumber(1), 'function:run', 9));
    }

    public function testPersistentAliasesTreatsImportedVariableAsGlobal(): void
    {
        $collector = new MutationOperationCollector(self::createStub(ReflectionProvider::class));
        $global = new \PhpParser\Node\Stmt\Global_([
            new \PhpParser\Node\Expr\Variable('cache'),
        ]);

        self::assertSame('global', $collector->persistentAliases($global, 'function:run', 9)[0]['root'] ?? null);
    }

    public function testCallSkipsDynamicFunctionNames(): void
    {
        $collector = new MutationOperationCollector(self::createStub(ReflectionProvider::class));
        $call = new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Expr\Variable('callable'));

        self::assertNull($collector->call($call, self::createStub(Scope::class), 'function:run', 10));
    }

    public function testMethodCalleesReturnsNoneForNoClasses(): void
    {
        $collector = new MutationOperationCollector(self::createStub(ReflectionProvider::class));

        self::assertSame([], $collector->methodCallees([], 'run', self::createStub(Scope::class)));
    }

    public function testRootPreservesExternalOrigins(): void
    {
        $collector = new MutationOperationCollector(self::createStub(ReflectionProvider::class));
        $property = new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('input'), 'value');

        self::assertSame('var:input', $collector->root($property));
        self::assertSame('this', $collector->root(new \PhpParser\Node\Expr\Variable('this')));
        self::assertSame('global', $collector->root(new \PhpParser\Node\Expr\Variable('GLOBALS')));
    }
}
