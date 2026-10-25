<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\TestClass\PropertyTestExecution;

/**
 * @covers \Toolkit\PhpStan\Rule\TestClass\PropertyTestExecution
 */
#[CoversClass(PropertyTestExecution::class)]
final class PropertyTestExecutionTest extends TestCase
{
    public function testRunsErisAcceptsChainedQuantifier(): void
    {
        $nodes = (new ParserFactory())->createForHostVersion()->parse('<?php class Properties { public function satisfiesIdentity(): void { $this->forAll(1)->when(fn($v) => true)->and(fn($v) => true)->withMaxSize(10)->then(fn($v) => true); } }');
        $method = (new NodeFinder())->findFirstInstanceOf($nodes ?? [], ClassMethod::class);
        self::assertInstanceOf(ClassMethod::class, $method);
        self::assertTrue((new PropertyTestExecution())->runsEris($method));
    }

    public function testRunsErisAcceptsLocalQuantifierAndInvoke(): void
    {
        $nodes = (new ParserFactory())->createForHostVersion()->parse('<?php class Properties { public function satisfiesIdentity(): void { $q = $this->forAll(1); $alias = $q; $alias(fn($v) => true); } }');
        $method = (new NodeFinder())->findFirstInstanceOf($nodes ?? [], ClassMethod::class);
        self::assertInstanceOf(ClassMethod::class, $method);
        self::assertTrue((new PropertyTestExecution())->runsEris($method));
    }

    public function testRunsErisRejectsUnexecutedClosure(): void
    {
        $nodes = (new ParserFactory())->createForHostVersion()->parse('<?php class Properties { public function satisfiesIdentity(): void { $unused = function() { $this->forAll(1)->then(fn($v) => true); }; } }');
        $method = (new NodeFinder())->findFirstInstanceOf($nodes ?? [], ClassMethod::class);
        self::assertInstanceOf(ClassMethod::class, $method);
        self::assertFalse((new PropertyTestExecution())->runsEris($method));
    }

    public function testRunsErisRejectsOverwrittenQuantifier(): void
    {
        $nodes = (new ParserFactory())->createForHostVersion()->parse('<?php class Properties { public function satisfiesIdentity(): void { $q = $this->forAll(1); $q = new Other(); $q->then(fn($v) => true); } }');
        $method = (new NodeFinder())->findFirstInstanceOf($nodes ?? [], ClassMethod::class);
        self::assertInstanceOf(ClassMethod::class, $method);
        self::assertFalse((new PropertyTestExecution())->runsEris($method));
    }

    public function testRunsErisRejectsDeadStatementAfterReturn(): void
    {
        $nodes = (new ParserFactory())->createForHostVersion()->parse('<?php class Properties { public function satisfiesIdentity(): void { return; $this->forAll(1)->then(fn($v) => true); } }');
        $method = (new NodeFinder())->findFirstInstanceOf($nodes ?? [], ClassMethod::class);
        self::assertInstanceOf(ClassMethod::class, $method);
        self::assertFalse((new PropertyTestExecution())->runsEris($method));
    }

    public function testRunsErisRejectsFixedExample(): void
    {
        $nodes = (new ParserFactory())->createForHostVersion()->parse('<?php class Properties { public function satisfiesIdentity(): void { self::assertSame(1, 1); } }');
        $method = (new NodeFinder())->findFirstInstanceOf($nodes ?? [], ClassMethod::class);
        self::assertInstanceOf(ClassMethod::class, $method);
        self::assertFalse((new PropertyTestExecution())->runsEris($method));
    }

    public function testRunsErisRejectsFirstClassCallable(): void
    {
        $nodes = (new ParserFactory())->createForHostVersion()->parse('<?php class Properties { public function satisfiesIdentity(): void { $this->forAll(1)->then(...); } }');
        $method = (new NodeFinder())->findFirstInstanceOf($nodes ?? [], ClassMethod::class);
        self::assertInstanceOf(ClassMethod::class, $method);
        self::assertFalse((new PropertyTestExecution())->runsEris($method));
    }

    public function testExecutesRejectsThenOnAnUnrelatedObject(): void
    {
        self::assertFalse((new PropertyTestExecution())->executes(new MethodCall(new Variable('other'), 'then'), []));
    }

    public function testIsQuantifierRejectsForAllWithoutGenerators(): void
    {
        self::assertFalse((new PropertyTestExecution())->isQuantifier(new MethodCall(new Variable('this'), 'forAll'), []));
    }
}
