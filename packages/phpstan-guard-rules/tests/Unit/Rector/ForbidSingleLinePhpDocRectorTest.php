<?php

declare(strict_types=1);

namespace Tests\Unit\Rector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;
use Tests\Rector\RectorFixtureRunner;
use Toolkit\Rector\ForbidSingleLinePhpDocRector;

/**
 * @covers \Toolkit\Rector\ForbidSingleLinePhpDocRector
 * @large
 */
#[CoversClass(ForbidSingleLinePhpDocRector::class)]
#[Large]
final class ForbidSingleLinePhpDocRectorTest extends TestCase
{
    public function testRefactorPreservesUnsupportedCasesAndIsIdempotent(): void
    {
        $result = (new RectorFixtureRunner())->run('ForbidSingleLinePhpDocRector', 'ForbidSingleLinePhpDocRector');
        self::assertSame(0, $result['exitCode'], $result['output']);
        self::assertSame($result['expected'], $result['actual']);
        self::assertTrue($result['idempotent'], 'A second Rector run must have no diff.');
    }

    public function testGetNodeTypesRegistersNodesAndRejectsUnrelatedInput(): void
    {
        $rule = new ForbidSingleLinePhpDocRector();
        self::assertNotEmpty($rule->getNodeTypes());
        self::assertNull($rule->refactor(new \PhpParser\Node\Expr\Variable('unrelated')));
    }

    public function testExpandRetainsAllTextAndLeavesMultilineCommentsAlone(): void
    {
        $node = new \PhpParser\Node\Stmt\ClassMethod('run');
        $node->setDocComment(new \PhpParser\Comment\Doc('/** @return array{a: int, b: string} */'));
        $rule = new ForbidSingleLinePhpDocRector();
        self::assertTrue($rule->expand($node));
        self::assertSame("/**\n * @return array{a: int, b: string}\n */", $node->getDocComment()?->getText());
        self::assertFalse($rule->expand($node));
    }

    public function testRefactorSafeSetLeavesRiskyViolationsUnchanged(): void
    {
        $result = (new RectorFixtureRunner())->run('', 'SafeSet', 'safe');
        self::assertSame(0, $result['exitCode'], $result['output']);
        self::assertSame($result['expected'], $result['actual']);
        self::assertTrue($result['idempotent']);
    }
}
