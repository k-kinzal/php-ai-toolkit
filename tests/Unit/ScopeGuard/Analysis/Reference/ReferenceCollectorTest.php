<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis\Reference;

use PhpAiToolkit\ScopeGuard\Analysis\Reference\Reference;
use PhpAiToolkit\ScopeGuard\Analysis\Reference\ReferenceCollector;
use PhpAiToolkit\ScopeGuard\Analysis\Reference\TypeNameReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\ScopeGuard\Analysis\Reference\ReferenceCollector
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Reference\Reference
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Reference\TypeNameReader
 */
#[CoversClass(ReferenceCollector::class)]
#[UsesClass(Reference::class)]
#[UsesClass(TypeNameReader::class)]
final class ReferenceCollectorTest extends TestCase
{
    public function testCollectReturnsOneReferencePerNamedNode(): void
    {
        $nodes = [
            new \PhpParser\Node\Expr\New_(new \PhpParser\Node\Name('App\\Order')),
            new \PhpParser\Node\Expr\Instanceof_(new \PhpParser\Node\Expr\Variable('candidate'), new \PhpParser\Node\Name('App\\Cart')),
        ];

        self::assertCount(2, (new ReferenceCollector())->collect($nodes, 'App\\Http', 'src/Http.php'));
    }

    public function testCollectReturnsNothingForNodesThatNameNoType(): void
    {
        self::assertSame([], (new ReferenceCollector())->collect([new \PhpParser\Node\Stmt\Nop()], 'App\\Http', 'src/Http.php'));
    }

    public function testFromNodeReadsAReturnType(): void
    {
        $method = new \PhpParser\Node\Stmt\ClassMethod('place', ['returnType' => new \PhpParser\Node\Name('App\\Order')]);
        $references = (new ReferenceCollector())->fromNode($method, 'App\\Http', 'src/Http.php');

        self::assertSame('return type', $references[0]->kind);
    }

    public function testFromNodeReadsAParameterType(): void
    {
        $param = new \PhpParser\Node\Param(new \PhpParser\Node\Expr\Variable('order'), null, new \PhpParser\Node\Name('App\\Order'));
        $references = (new ReferenceCollector())->fromNode($param, 'App\\Http', 'src/Http.php');

        self::assertSame('parameter type', $references[0]->kind);
    }

    public function testFromNodeReadsATraitUse(): void
    {
        $traitUse = new \PhpParser\Node\Stmt\TraitUse([new \PhpParser\Node\Name('App\\Shared')]);
        $references = (new ReferenceCollector())->fromNode($traitUse, 'App\\Http', 'src/Http.php');

        self::assertSame('trait use', $references[0]->kind);
    }

    public function testFromNodeReadsACatchType(): void
    {
        $catch = new \PhpParser\Node\Stmt\Catch_([new \PhpParser\Node\Name('App\\Failure')]);
        $references = (new ReferenceCollector())->fromNode($catch, 'App\\Http', 'src/Http.php');

        self::assertSame('catch type', $references[0]->kind);
    }

    public function testFromNodeReadsAnAttribute(): void
    {
        $attribute = new \PhpParser\Node\Attribute(new \PhpParser\Node\Name('App\\Marker'));
        $references = (new ReferenceCollector())->fromNode($attribute, 'App\\Http', 'src/Http.php');

        self::assertSame('attribute', $references[0]->kind);
    }

    public function testFromNodeReadsAPropertyType(): void
    {
        $property = new \PhpParser\Node\Stmt\Property(0, [], [], new \PhpParser\Node\Name('App\\Order'));
        $references = (new ReferenceCollector())->fromNode($property, 'App\\Http', 'src/Http.php');

        self::assertSame('property type', $references[0]->kind);
    }

    public function testFromNodeIgnoresAnUnrelatedStatement(): void
    {
        self::assertSame([], (new ReferenceCollector())->fromNode(new \PhpParser\Node\Stmt\Nop(), 'App\\Http', 'src/Http.php'));
    }

    public function testFromExpressionReadsAnInstantiationAsAConstructorCall(): void
    {
        $node = new \PhpParser\Node\Expr\New_(new \PhpParser\Node\Name('App\\Order'));
        $references = (new ReferenceCollector())->fromExpression($node, 'App\\Http', 'src/Http.php');

        self::assertSame('__construct', $references[0]->memberName);
    }

    public function testFromExpressionReadsAStaticCall(): void
    {
        $node = new \PhpParser\Node\Expr\StaticCall(new \PhpParser\Node\Name('App\\Order'), 'place');
        $references = (new ReferenceCollector())->fromExpression($node, 'App\\Http', 'src/Http.php');

        self::assertSame('place', $references[0]->memberName);
    }

    public function testFromExpressionReadsAStaticPropertyFetch(): void
    {
        $node = new \PhpParser\Node\Expr\StaticPropertyFetch(new \PhpParser\Node\Name('App\\Order'), 'total');
        $references = (new ReferenceCollector())->fromExpression($node, 'App\\Http', 'src/Http.php');

        self::assertSame('static property access', $references[0]->kind);
    }

    public function testFromExpressionReadsAConstantFetch(): void
    {
        $node = new \PhpParser\Node\Expr\ClassConstFetch(new \PhpParser\Node\Name('App\\Order'), 'STATUS');
        $references = (new ReferenceCollector())->fromExpression($node, 'App\\Http', 'src/Http.php');

        self::assertSame('STATUS', $references[0]->memberName);
    }

    public function testFromExpressionTreatsTheClassKeywordAsANameReference(): void
    {
        $node = new \PhpParser\Node\Expr\ClassConstFetch(new \PhpParser\Node\Name('App\\Order'), 'class');
        $references = (new ReferenceCollector())->fromExpression($node, 'App\\Http', 'src/Http.php');

        self::assertNull($references[0]->memberName);
    }

    public function testFromExpressionReadsAnInstanceofCheck(): void
    {
        $node = new \PhpParser\Node\Expr\Instanceof_(new \PhpParser\Node\Expr\Variable('candidate'), new \PhpParser\Node\Name('App\\Order'));
        $references = (new ReferenceCollector())->fromExpression($node, 'App\\Http', 'src/Http.php');

        self::assertSame('instanceof check', $references[0]->kind);
    }

    public function testFromExpressionIgnoresAnUnrelatedExpression(): void
    {
        self::assertSame([], (new ReferenceCollector())->fromExpression(new \PhpParser\Node\Expr\Variable('order'), 'App\\Http', 'src/Http.php'));
    }

    public function testFromSupertypesReadsTheParentClass(): void
    {
        $class = new \PhpParser\Node\Stmt\Class_('Controller', ['extends' => new \PhpParser\Node\Name('App\\Base')]);
        $references = (new ReferenceCollector())->fromSupertypes($class, 'App\\Http', 'src/Http.php');

        self::assertSame('inheritance', $references[0]->kind);
    }

    public function testFromSupertypesReadsImplementedInterfaces(): void
    {
        $class = new \PhpParser\Node\Stmt\Class_('Controller', ['implements' => [new \PhpParser\Node\Name('App\\Contract')]]);

        self::assertCount(1, (new ReferenceCollector())->fromSupertypes($class, 'App\\Http', 'src/Http.php'));
    }

    public function testFromSupertypesReadsParentInterfaces(): void
    {
        $interface = new \PhpParser\Node\Stmt\Interface_('Contract', ['extends' => [new \PhpParser\Node\Name('App\\Readable')]]);

        self::assertCount(1, (new ReferenceCollector())->fromSupertypes($interface, 'App\\Http', 'src/Http.php'));
    }

    public function testFromSupertypesReadsEnumInterfaces(): void
    {
        $enum = new \PhpParser\Node\Stmt\Enum_('Suit', ['implements' => [new \PhpParser\Node\Name('App\\Contract')]]);

        self::assertCount(1, (new ReferenceCollector())->fromSupertypes($enum, 'App\\Http', 'src/Http.php'));
    }

    public function testFromNamesReturnsOneReferencePerName(): void
    {
        $names = [new \PhpParser\Node\Name('App\\One'), new \PhpParser\Node\Name('App\\Two')];

        self::assertCount(2, (new ReferenceCollector())->fromNames($names, 'inheritance', 'App\\Http', 'src/Http.php'));
    }

    public function testFromTypeReadsEveryNameInAUnion(): void
    {
        $union = new \PhpParser\Node\UnionType([new \PhpParser\Node\Name('App\\One'), new \PhpParser\Node\Name('App\\Two')]);

        self::assertCount(2, (new ReferenceCollector())->fromType($union, 'parameter type', 'App\\Http', 'src/Http.php'));
    }

    public function testFromTypeReturnsNothingForAnAbsentType(): void
    {
        self::assertSame([], (new ReferenceCollector())->fromType(null, 'parameter type', 'App\\Http', 'src/Http.php'));
    }

    public function testReferenceRecordsTheWrittenClassName(): void
    {
        $references = (new ReferenceCollector())->reference(new \PhpParser\Node\Name('App\\Order'), null, 'inheritance', 'App\\Http', 'src/Http.php');

        self::assertSame('App\\Order', $references[0]->className);
    }

    public function testReferenceIgnoresSelfKeyword(): void
    {
        self::assertSame([], (new ReferenceCollector())->reference(new \PhpParser\Node\Name('self'), null, 'inheritance', 'App\\Http', 'src/Http.php'));
    }

    public function testReferenceIgnoresAComputedClassPosition(): void
    {
        $node = new \PhpParser\Node\Expr\Variable('className');

        self::assertSame([], (new ReferenceCollector())->reference($node, null, 'instantiation', 'App\\Http', 'src/Http.php'));
    }

    public function testMemberNameReadsAnIdentifier(): void
    {
        self::assertSame('place', (new ReferenceCollector())->memberName(new \PhpParser\Node\Identifier('place')));
    }

    public function testMemberNameIgnoresAComputedName(): void
    {
        self::assertNull((new ReferenceCollector())->memberName(new \PhpParser\Node\Expr\Variable('method')));
    }
}
