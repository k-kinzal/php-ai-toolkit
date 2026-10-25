<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Visibility;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\Visibility\TypeNameReader;
use Toolkit\PhpStan\Rule\Visibility\VisibilityReferenceCollector;

/**
 * @covers \Toolkit\PhpStan\Rule\Visibility\VisibilityReferenceCollector
 * @uses \Toolkit\PhpStan\Rule\Visibility\TypeNameReader
 * @medium
 */
#[CoversClass(VisibilityReferenceCollector::class)]
#[UsesClass(TypeNameReader::class)]
#[Medium]
final class VisibilityReferenceCollectorTest extends PHPStanTestCase
{
    public function testGetNodeTypeReturnsBaseNode(): void
    {
        self::assertSame(Node::class, (new VisibilityReferenceCollector())->getNodeType());
    }

    public function testProcessNodeReturnsNullWithoutReference(): void
    {
        self::assertNull((new VisibilityReferenceCollector())->processNode(new Nop(), self::createStub(Scope::class)));
    }

    public function testFromNodeRecordsResolvedStaticMemberReference(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('App\Domain\Order');
        $scope->method('getNamespace')->willReturn('App\Http');
        $reference = (new VisibilityReferenceCollector())->fromNode(
            new StaticCall(new Name('Order'), 'open'),
            $scope,
        );

        self::assertSame('App\Domain\Order', $reference[0]['className']);
        self::assertSame('open', $reference[0]['memberName']);
        self::assertSame('App\Http', $reference[0]['namespace']);
    }

    public function testFromNodeSkipsRelativeClassNames(): void
    {
        self::assertSame([], (new VisibilityReferenceCollector())->fromNode(
            new New_(new Name('self')),
            self::createStub(Scope::class),
        ));
    }

    public function testFromExpressionReadsInstantiation(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('App\Order');

        self::assertSame('__construct', (new VisibilityReferenceCollector())->fromExpression(
            new New_(new Name('Order')),
            $scope,
        )[0]['memberName']);
    }

    public function testFromSupertypesReadsImplementedInterface(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('App\Contract');
        $class = new Class_('Order', ['implements' => [new Name('Contract')]]);

        self::assertCount(1, (new VisibilityReferenceCollector())->fromSupertypes($class, $scope));
    }

    public function testFromNamesReadsEveryName(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('App\One', 'App\Two');

        self::assertCount(2, (new VisibilityReferenceCollector())->fromNames([
            new Name('One'),
            new Name('Two'),
        ], 'inheritance', $scope));
    }

    public function testFromTypeReadsUnionMembers(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('App\One', 'App\Two');
        $type = new UnionType([new Name('One'), new Name('Two')]);

        self::assertCount(2, (new VisibilityReferenceCollector())->fromType($type, 'return type', $scope));
    }

    public function testReferenceSkipsComputedClassPosition(): void
    {
        self::assertSame([], (new VisibilityReferenceCollector())->reference(
            new Variable('class'),
            null,
            'instantiation',
            self::createStub(Scope::class),
        ));
    }

    public function testMemberNameRejectsComputedName(): void
    {
        self::assertNull((new VisibilityReferenceCollector())->memberName(new Variable('method')));
    }

    public function testInstanceReferenceResolvesNullablePropertyReceiver(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(TypeCombinator::union(new ObjectType('App\Domain\Order'), new NullType()));
        $scope->method('getNamespace')->willReturn('App\Http');
        $references = (new VisibilityReferenceCollector())->fromExpression(new NullsafePropertyFetch(new Variable('order'), 'value', ['startLine' => 12]), $scope);
        self::assertSame([['className' => 'App\Domain\Order', 'memberName' => 'value', 'kind' => 'instance property access', 'namespace' => 'App\Http', 'line' => 12]], $references);
    }

    public function testInstanceReferenceSkipsAmbiguousReceiverTypes(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(TypeCombinator::union(new ObjectType('App\One'), new ObjectType('App\Two')));
        self::assertSame([], (new VisibilityReferenceCollector())->fromExpression(new MethodCall(new Variable('object'), 'run'), $scope));
    }

    public function testInstanceReferenceSkipsUnknownReceiversAndDynamicMembers(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new MixedType());
        self::assertSame([], (new VisibilityReferenceCollector())->fromExpression(new MethodCall(new Variable('object'), new Variable('method')), $scope));
        self::assertSame([], (new VisibilityReferenceCollector())->fromExpression(new MethodCall(new Variable('object'), 'run'), $scope));
    }
}
