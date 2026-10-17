<?php

declare(strict_types=1);

namespace Tests\Unit\Rector;

use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;
use Tests\Rector\RectorFixtureRunner;
use Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser;
use Toolkit\Rector\RequireListForArrayLiteralRector;

/**
 * @covers \Toolkit\Rector\RequireListForArrayLiteralRector
 * @large
 */
#[CoversClass(RequireListForArrayLiteralRector::class)]
#[Large]
final class RequireListForArrayLiteralRectorTest extends TestCase
{
    public function testRefactorPreservesUnsupportedCasesAndIsIdempotent(): void
    {
        $result = (new RectorFixtureRunner())->run('RequireListForArrayLiteralRector', 'RequireListForArrayLiteralRector');
        self::assertSame(0, $result['exitCode'], $result['output']);
        self::assertSame($result['expected'], $result['actual']);
        self::assertTrue($result['idempotent'], 'A second Rector run must have no diff.');
    }

    public function testGetNodeTypesRegistersNodesAndRejectsUnrelatedInput(): void
    {
        $rule = new RequireListForArrayLiteralRector();
        self::assertNotEmpty($rule->getNodeTypes());
        self::assertNull($rule->refactor(new Variable('unrelated')));
    }

    public function testNarrowChangesOuterBranchesButPreservesNestedArrays(): void
    {
        $parsed = (new RulePhpDocParser())->parse('/** @return array<int, array<int, string>>|null */');
        $value = $parsed->getReturnTagValues()[0];
        (new RequireListForArrayLiteralRector())->narrow($value->type);
        self::assertSame('(list<array<int, string>> | null)', (string) $value->type);
    }
}
