<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Visibility;

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
        self::assertSame(\PhpParser\Node::class, (new VisibilityReferenceCollector())->getNodeType());
    }

    public function testProcessNodeReturnsNullWithoutReference(): void
    {
        self::assertNull((new VisibilityReferenceCollector())->processNode(new \PhpParser\Node\Stmt\Nop(), self::createStub(Scope::class)));
    }

    public function testFromNodeRecordsResolvedStaticMemberReference(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('App\Domain\Order');
        $scope->method('getNamespace')->willReturn('App\Http');
        $reference = (new VisibilityReferenceCollector())->fromNode(
            new \PhpParser\Node\Expr\StaticCall(new \PhpParser\Node\Name('Order'), 'open'),
            $scope,
        );

        self::assertSame('App\Domain\Order', $reference[0]['className']);
        self::assertSame('open', $reference[0]['memberName']);
        self::assertSame('App\Http', $reference[0]['namespace']);
    }

    public function testFromNodeSkipsRelativeClassNames(): void
    {
        self::assertSame([], (new VisibilityReferenceCollector())->fromNode(
            new \PhpParser\Node\Expr\New_(new \PhpParser\Node\Name('self')),
            self::createStub(Scope::class),
        ));
    }

    public function testFromExpressionReadsInstantiation(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('App\Order');

        self::assertSame('__construct', (new VisibilityReferenceCollector())->fromExpression(
            new \PhpParser\Node\Expr\New_(new \PhpParser\Node\Name('Order')),
            $scope,
        )[0]['memberName']);
    }

    public function testFromSupertypesReadsImplementedInterface(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('App\Contract');
        $class = new \PhpParser\Node\Stmt\Class_('Order', ['implements' => [new \PhpParser\Node\Name('Contract')]]);

        self::assertCount(1, (new VisibilityReferenceCollector())->fromSupertypes($class, $scope));
    }

    public function testFromNamesReadsEveryName(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('App\One', 'App\Two');

        self::assertCount(2, (new VisibilityReferenceCollector())->fromNames([
            new \PhpParser\Node\Name('One'),
            new \PhpParser\Node\Name('Two'),
        ], 'inheritance', $scope));
    }

    public function testFromTypeReadsUnionMembers(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('App\One', 'App\Two');
        $type = new \PhpParser\Node\UnionType([new \PhpParser\Node\Name('One'), new \PhpParser\Node\Name('Two')]);

        self::assertCount(2, (new VisibilityReferenceCollector())->fromType($type, 'return type', $scope));
    }

    public function testReferenceSkipsComputedClassPosition(): void
    {
        self::assertSame([], (new VisibilityReferenceCollector())->reference(
            new \PhpParser\Node\Expr\Variable('class'),
            null,
            'instantiation',
            self::createStub(Scope::class),
        ));
    }

    public function testMemberNameRejectsComputedName(): void
    {
        self::assertNull((new VisibilityReferenceCollector())->memberName(new \PhpParser\Node\Expr\Variable('method')));
    }
}
