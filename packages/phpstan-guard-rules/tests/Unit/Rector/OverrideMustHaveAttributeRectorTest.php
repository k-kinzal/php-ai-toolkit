<?php

declare(strict_types=1);

namespace Tests\Unit\Rector;

use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;
use Tests\Rector\RectorFixtureRunner;
use Toolkit\Rector\OverrideMustHaveAttributeRector;

/**
 * @covers \Toolkit\Rector\OverrideMustHaveAttributeRector
 * @large
 */
#[CoversClass(OverrideMustHaveAttributeRector::class)]
#[Large]
final class OverrideMustHaveAttributeRectorTest extends TestCase
{
    public function testRefactorPreservesUnsupportedCasesAndIsIdempotent(): void
    {
        $result = (new RectorFixtureRunner())->run('OverrideMustHaveAttributeRector', 'OverrideMustHaveAttributeRector');
        self::assertSame(0, $result['exitCode'], $result['output']);
        self::assertSame($result['expected'], $result['actual']);
        self::assertTrue($result['idempotent'], 'A second Rector run must have no diff.');
    }

    public function testGetNodeTypesRegistersNodesAndRejectsUnrelatedInput(): void
    {
        $rule = new OverrideMustHaveAttributeRector();
        self::assertNotEmpty($rule->getNodeTypes());
        self::assertNull($rule->refactor(new Variable('unrelated')));
    }
}
