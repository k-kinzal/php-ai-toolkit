<?php

declare(strict_types=1);

namespace Tests\Unit\Rector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;
use Tests\Rector\RectorFixtureRunner;
use Toolkit\Rector\ForbiddenMagicMethodCallRector;

/**
 * @covers \Toolkit\Rector\ForbiddenMagicMethodCallRector
 * @large
 */
#[CoversClass(ForbiddenMagicMethodCallRector::class)]
#[Large]
final class ForbiddenMagicMethodCallRectorTest extends TestCase
{
    public function testRefactorPreservesUnsupportedCasesAndIsIdempotent(): void
    {
        $result = (new RectorFixtureRunner())->run('ForbiddenMagicMethodCallRector', 'ForbiddenMagicMethodCallRector');
        self::assertSame(0, $result['exitCode'], $result['output']);
        self::assertSame($result['expected'], $result['actual']);
        self::assertTrue($result['idempotent'], 'A second Rector run must have no diff.');
    }

    public function testGetNodeTypesRegistersNodesAndRejectsUnrelatedInput(): void
    {
        $rule = new ForbiddenMagicMethodCallRector();
        self::assertNotEmpty($rule->getNodeTypes());
        self::assertNull($rule->refactor(new \PhpParser\Node\Expr\Variable('unrelated')));
    }
}
