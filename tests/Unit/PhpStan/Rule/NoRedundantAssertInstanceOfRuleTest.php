<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

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
                'Redundant PHPUnit assertInstanceOf() in test class: the asserted value already has the statically-known type "Tests\Fixture\NoRedundantAssertInstanceOf\Reporter", which is guaranteed to be an instance of "Tests\Fixture\NoRedundantAssertInstanceOf\ReporterInterface". Remove this assertion or replace it with an assertion about observable behavior.',
                29,
            ],
            [
                'Redundant PHPUnit assertInstanceOf() in test class: the asserted value already has the statically-known type "Tests\Fixture\NoRedundantAssertInstanceOf\Reporter", which is guaranteed to be an instance of "Tests\Fixture\NoRedundantAssertInstanceOf\Reporter". Remove this assertion or replace it with an assertion about observable behavior.',
                36,
            ],
            [
                'Redundant PHPUnit assertInstanceOf() in test class: the asserted value already has the statically-known type "Tests\Fixture\NoRedundantAssertInstanceOf\Reporter", which is guaranteed to be an instance of "Tests\Fixture\NoRedundantAssertInstanceOf\ReporterInterface". Remove this assertion or replace it with an assertion about observable behavior.',
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
