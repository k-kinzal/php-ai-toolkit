<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Visibility;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Visibility\TypeNameReader;
use Toolkit\PhpStan\Rule\Visibility\VisibilityReferenceCollector;

/**
 * @covers \Toolkit\PhpStan\Rule\Visibility\VisibilityReferenceCollector
 * @uses \Toolkit\PhpStan\Rule\Visibility\TypeNameReader
 */
#[CoversClass(VisibilityReferenceCollector::class)]
#[UsesClass(TypeNameReader::class)]
final class VisibilityReferenceCollectorTest extends TestCase
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
}
