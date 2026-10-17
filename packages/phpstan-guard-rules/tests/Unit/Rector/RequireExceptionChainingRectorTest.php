<?php

declare(strict_types=1);

namespace Tests\Unit\Rector;

use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;
use Tests\Rector\RectorFixtureRunner;
use Toolkit\Rector\RequireExceptionChainingRector;

/**
 * @covers \Toolkit\Rector\RequireExceptionChainingRector
 * @large
 */
#[CoversClass(RequireExceptionChainingRector::class)]
#[Large]
final class RequireExceptionChainingRectorTest extends TestCase
{
    public function testRefactorPreservesUnsupportedCasesAndIsIdempotent(): void
    {
        $result = (new RectorFixtureRunner())->run('RequireExceptionChainingRector', 'RequireExceptionChainingRector');
        self::assertSame(0, $result['exitCode'], $result['output']);
        self::assertSame($result['expected'], $result['actual']);
        self::assertTrue($result['idempotent'], 'A second Rector run must have no diff.');
    }

    public function testGetNodeTypesRegistersNodesAndRejectsUnrelatedInput(): void
    {
        $rule = new RequireExceptionChainingRector();
        self::assertNotEmpty($rule->getNodeTypes());
        self::assertNull($rule->refactor(new Variable('unrelated')));
    }

    public function testAcceptsPreviousRejectsDynamicExceptionClasses(): void
    {
        $node = new New_(new Variable('exceptionClass'));
        self::assertFalse((new RequireExceptionChainingRector())->acceptsPrevious($node));
    }

    public function testRefactorRiskySetComposesChangesWithoutEnablingSafeRules(): void
    {
        $result = (new RectorFixtureRunner())->run('', 'RiskySet', 'risky');
        self::assertSame(0, $result['exitCode'], $result['output']);
        self::assertSame($result['expected'], $result['actual']);
        self::assertTrue($result['idempotent']);
    }
}
