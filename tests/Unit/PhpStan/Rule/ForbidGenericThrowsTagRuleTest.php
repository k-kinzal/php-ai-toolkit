<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\ForbidGenericThrowsTagRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<ForbidGenericThrowsTagRule>
 */
#[CoversClass(ForbidGenericThrowsTagRule::class)]
#[Medium]
final class ForbidGenericThrowsTagRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbidGenericThrowsTagRule();
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\ClassMethod::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeGenericThrowsTagsAreReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidGenericThrowsTag/WithGenericThrowsTag.php'], [
            [
                'Replace "@throws \Exception" on withGenericException() with the concrete exception types the method can raise. A generic @throws tag gives callers nothing to catch selectively and defeats checked-exception analysis.',
                16,
            ],
            [
                'Replace "@throws \Throwable" on withGenericThrowable() with the concrete exception types the method can raise. A generic @throws tag gives callers nothing to catch selectively and defeats checked-exception analysis.',
                24,
            ],
            [
                'Replace "@throws \Exception" on withGenericUnionMember() with the concrete exception types the method can raise. A generic @throws tag gives callers nothing to catch selectively and defeats checked-exception analysis.',
                32,
            ],
        ]);
    }

    public function testProcessNodeConcreteThrowsTagsAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidGenericThrowsTag/WithConcreteThrowsTag.php'], []);
    }
}
