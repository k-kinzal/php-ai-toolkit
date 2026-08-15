<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\RequireThrowsTagOnDirectThrow;

use PhpAiToolkit\PhpStan\Rule\RequireThrowsTagOnDirectThrow\ThrowSiteVisitor;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeTraverser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ThrowSiteVisitor::class)]
#[UsesClass(\PhpAiToolkit\PhpStan\Rule\RequireThrowsTagOnDirectThrow\ThrowSite::class)]
final class ThrowSiteVisitorTest extends TestCase
{
    public function testEnterNodeSkipsNestedFunctionScopes(): void
    {
        self::assertSame(NodeTraverser::DONT_TRAVERSE_CHILDREN, (new ThrowSiteVisitor())->enterNode(new Closure()));
    }

    public function testEnterNodeRecordsThrowGuardedByEnclosingTry(): void
    {
        $visitor = new ThrowSiteVisitor();
        $tryNode = new TryCatch([], [new Catch_([new Name('RuntimeException')], new Variable('exception'), [])], null);

        $visitor->enterNode($tryNode);
        $visitor->enterNode(new Throw_(new New_(new Name('LogicException'))));

        $sites = $visitor->sites();
        self::assertCount(1, $sites);
        self::assertSame('LogicException', $sites[0]->thrownNames[0]->toString());
        self::assertSame('RuntimeException', $sites[0]->guardNames[0]->toString());
    }

    public function testLeaveNodeRemovesTryProtection(): void
    {
        $visitor = new ThrowSiteVisitor();
        $tryNode = new TryCatch([], [new Catch_([new Name('RuntimeException')], new Variable('exception'), [])], null);

        $visitor->enterNode($tryNode);
        $visitor->leaveNode($tryNode);
        $visitor->enterNode(new Throw_(new New_(new Name('LogicException'))));

        $sites = $visitor->sites();
        self::assertCount(1, $sites);
        self::assertSame([], $sites[0]->guardNames);
    }

    public function testRecordThrowResolvesRethrownCatchTypes(): void
    {
        $visitor = new ThrowSiteVisitor();
        $catchNode = new Catch_([new Name('RuntimeException')], new Variable('exception'), []);
        $tryNode = new TryCatch([], [$catchNode], null);

        $visitor->enterNode($tryNode);
        $visitor->enterNode($catchNode);
        $visitor->recordThrow(new Throw_(new Variable('exception')));

        $sites = $visitor->sites();
        self::assertCount(1, $sites);
        self::assertSame('RuntimeException', $sites[0]->thrownNames[0]->toString());
        self::assertSame([], $sites[0]->guardNames);
    }

    public function testSitesReturnsRecordedThrowsInOrder(): void
    {
        $visitor = new ThrowSiteVisitor();

        $visitor->recordThrow(new Throw_(new New_(new Name('RuntimeException'))));
        $visitor->recordThrow(new Throw_(new New_(new Name('DomainException'))));

        $sites = $visitor->sites();
        self::assertCount(2, $sites);
        self::assertSame('RuntimeException', $sites[0]->thrownNames[0]->toString());
        self::assertSame('DomainException', $sites[1]->thrownNames[0]->toString());
    }
}
