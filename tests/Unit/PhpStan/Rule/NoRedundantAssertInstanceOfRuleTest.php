<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\NoRedundantAssertInstanceOfRule;
use PhpAiToolkit\PhpStan\Support\TestClassScope;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<NoRedundantAssertInstanceOfRule>
 */
#[CoversClass(NoRedundantAssertInstanceOfRule::class)]
#[Medium]
final class NoRedundantAssertInstanceOfRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new NoRedundantAssertInstanceOfRule(new TestClassScope());
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Expr::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeRedundantAssertInstanceOfIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoRedundantAssertInstanceOf/RedundantAssertInstanceOf.php'], [
            [
                'Remove redundant assertInstanceOf(): "Tests\Fixture\NoRedundantAssertInstanceOf\Reporter" is already an instance of "Tests\Fixture\NoRedundantAssertInstanceOf\ReporterInterface". Assert observable behavior instead.',
                29,
            ],
            [
                'Remove redundant assertInstanceOf(): "Tests\Fixture\NoRedundantAssertInstanceOf\Reporter" is already an instance of "Tests\Fixture\NoRedundantAssertInstanceOf\Reporter". Assert observable behavior instead.',
                36,
            ],
            [
                'Remove redundant assertInstanceOf(): "Tests\Fixture\NoRedundantAssertInstanceOf\Reporter" is already an instance of "Tests\Fixture\NoRedundantAssertInstanceOf\ReporterInterface". Assert observable behavior instead.',
                43,
            ],
        ]);
    }

    public function testProcessNodeUsefulAssertInstanceOfIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoRedundantAssertInstanceOf/AllowedAssertInstanceOf.php'], []);
    }

    public function testProcessNodeOutsideTestNamespaceIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/NoRedundantAssertInstanceOf/NonTestClass.php'], []);
    }
}
