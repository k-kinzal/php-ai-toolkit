<?php

declare(strict_types=1);

namespace Tests\Unit\Rector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;
use Tests\Rector\RectorFixtureRunner;
use Toolkit\Rector\ForbiddenCommentRector;

/**
 * @covers \Toolkit\Rector\ForbiddenCommentRector
 * @large
 */
#[CoversClass(ForbiddenCommentRector::class)]
#[Large]
final class ForbiddenCommentRectorTest extends TestCase
{
    public function testRefactorPreservesUnsupportedCasesAndIsIdempotent(): void
    {
        $result = (new RectorFixtureRunner())->run('ForbiddenCommentRector', 'ForbiddenCommentRector');
        self::assertSame(0, $result['exitCode'], $result['output']);
        self::assertSame($result['expected'], $result['actual']);
        self::assertTrue($result['idempotent'], 'A second Rector run must have no diff.');
    }

    public function testGetNodeTypesRegistersNodesAndRejectsUnrelatedInput(): void
    {
        $rule = new ForbiddenCommentRector();
        self::assertNotEmpty($rule->getNodeTypes());
        self::assertNull($rule->refactor(new \PhpParser\Node\Expr\Variable('unrelated')));
    }

    public function testWithoutSuppressionPreservesAmbiguousTagsAndProseMentions(): void
    {
        $doc = new \PhpParser\Comment\Doc('/** @phpstan-ignore return.type @return string */');
        $rule = new ForbiddenCommentRector();
        $pattern = new \Toolkit\PhpStan\Rule\Shared\ForbiddenCommentPattern();
        self::assertSame($doc, $rule->withoutSuppression($doc, $pattern));
        $prose = new \PhpParser\Comment\Doc('/** Explains @phpstan-ignore return.type usage. */');
        self::assertSame($prose, $rule->withoutSuppression($prose, $pattern));
    }
}
