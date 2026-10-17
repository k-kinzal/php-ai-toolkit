<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Mutation;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Stmt\Global_;
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
        self::assertSame(Node::class, (new MutationOperationCollector(self::createStub(ReflectionProvider::class)))->getNodeType());
    }

    public function testProcessNodeSkipsNodesOutsideCallables(): void
    {
        $collector = new MutationOperationCollector(self::createStub(ReflectionProvider::class));

        self::assertNull($collector->processNode(new LNumber(1), self::createStub(Scope::class)));
    }

    public function testRecordsCollectsAssignmentAndAlias(): void
    {
        $collector = new MutationOperationCollector(self::createStub(ReflectionProvider::class));
        $assign = new Assign(
            new Variable('alias'),
            new Variable('input'),
        );

        self::assertCount(2, $collector->records($assign, self::createStub(Scope::class), 'function:run', 8));
    }

    public function testMutationsCollectsEveryTarget(): void
    {
        $collector = new MutationOperationCollector(self::createStub(ReflectionProvider::class));
        $records = $collector->mutations([
            new Variable('left'),
            new Variable('right'),
        ], 'function:run', 9);

        self::assertSame(['var:left', 'var:right'], [$records[0]['root'], $records[1]['root']]);
    }

    public function testAssignmentReturnsNullForAnotherNodeKind(): void
    {
        $collector = new MutationOperationCollector(self::createStub(ReflectionProvider::class));

        self::assertNull($collector->assignment(new LNumber(1), 'function:run', 9));
    }

    public function testPersistentAliasesTreatsImportedVariableAsGlobal(): void
    {
        $collector = new MutationOperationCollector(self::createStub(ReflectionProvider::class));
        $global = new Global_([
            new Variable('cache'),
        ]);

        self::assertSame('global', $collector->persistentAliases($global, 'function:run', 9)[0]['root'] ?? null);
    }

    public function testCallSkipsDynamicFunctionNames(): void
    {
        $collector = new MutationOperationCollector(self::createStub(ReflectionProvider::class));
        $call = new FuncCall(new Variable('callable'));

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
        $property = new PropertyFetch(new Variable('input'), 'value');

        self::assertSame('var:input', $collector->root($property));
        self::assertSame('this', $collector->root(new Variable('this')));
        self::assertSame('global', $collector->root(new Variable('GLOBALS')));
    }
}
