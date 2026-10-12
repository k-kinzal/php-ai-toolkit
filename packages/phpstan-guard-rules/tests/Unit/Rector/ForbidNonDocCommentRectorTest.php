<?php

declare(strict_types=1);

namespace Tests\Unit\Rector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;
use Tests\Rector\RectorFixtureRunner;
use Toolkit\Rector\ForbidNonDocCommentRector;

/**
 * @covers \Toolkit\Rector\ForbidNonDocCommentRector
 * @large
 */
#[CoversClass(ForbidNonDocCommentRector::class)]
#[Large]
final class ForbidNonDocCommentRectorTest extends TestCase
{
    public function testRefactorPreservesUnsupportedCasesAndIsIdempotent(): void
    {
        $result = (new RectorFixtureRunner())->run('ForbidNonDocCommentRector', 'ForbidNonDocCommentRector');
        self::assertSame(0, $result['exitCode'], $result['output']);
        self::assertSame($result['expected'], $result['actual']);
        self::assertTrue($result['idempotent'], 'A second Rector run must have no diff.');
    }

    public function testGetNodeTypesRegistersNodesAndRejectsUnrelatedInput(): void
    {
        $rule = new ForbidNonDocCommentRector();
        self::assertNotEmpty($rule->getNodeTypes());
        self::assertNull($rule->refactor(new \PhpParser\Node\Expr\Variable('unrelated')));
    }

    public function testForbiddenOffsetsDistinguishesCommentsOnTheSameLine(): void
    {
        $source = '<?php $a = [/* forbidden */ 1]; /* also forbidden */';
        $offsets = (new ForbidNonDocCommentRector())->forbiddenOffsets($source);
        self::assertCount(2, $offsets);
        self::assertArrayHasKey(12, $offsets);
        self::assertArrayHasKey(32, $offsets);
    }
}
