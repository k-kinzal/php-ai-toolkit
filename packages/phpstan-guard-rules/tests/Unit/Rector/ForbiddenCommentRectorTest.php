<?php

declare(strict_types=1);

namespace Tests\Unit\Rector;

use PhpParser\Comment\Doc;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;
use Tests\Rector\RectorFixtureRunner;
use Toolkit\PhpStan\Rule\Shared\ForbiddenCommentPattern;
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
        self::assertNull($rule->refactor(new Variable('unrelated')));
    }

    public function testWithoutSuppressionPreservesAmbiguousTagsAndProseMentions(): void
    {
        $doc = new Doc('/** @phpstan-ignore return.type @return string */');
        $rule = new ForbiddenCommentRector();
        $pattern = new ForbiddenCommentPattern();
        self::assertSame($doc, $rule->withoutSuppression($doc, $pattern));
        $prose = new Doc('/** Explains @phpstan-ignore return.type usage. */');
        self::assertSame($prose, $rule->withoutSuppression($prose, $pattern));
    }
}
