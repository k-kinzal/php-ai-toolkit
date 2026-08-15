<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\ForbidBroadCatch\BroadCatchPathMatcher;
use PhpAiToolkit\PhpStan\Rule\ForbidBroadCatchRule;
use PhpAiToolkit\PhpStan\Rule\Shared\RulePathNormalizer;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @extends RuleTestCase<ForbidBroadCatchRule>
 */
#[CoversClass(ForbidBroadCatchRule::class)]
#[UsesClass(BroadCatchPathMatcher::class)]
#[UsesClass(RulePathNormalizer::class)]
#[Medium]
final class ForbidBroadCatchRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbidBroadCatchRule(['tests/Fixture/ForbidBroadCatch/WithAllowedBroadCatch.php']);
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\Catch_::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeBroadCatchesAreReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidBroadCatch/WithBroadCatch.php'], [
            [
                'Catch a specific exception type instead of "Throwable": catch (Throwable) intercepts every failure, including programmer errors. If this catch is an intentional top-level boundary handler, add its file path to customRules.broadCatchAllowedPaths.',
                21,
            ],
            [
                'Catch a specific exception type instead of "Exception": catch (Exception) intercepts every failure, including programmer errors. If this catch is an intentional top-level boundary handler, add its file path to customRules.broadCatchAllowedPaths.',
                30,
            ],
            [
                'Catch a specific exception type instead of "LogicException": LogicException is a programmer error (LogicException family) that must be fixed at its source, not caught. If this catch is an intentional top-level boundary handler, add its file path to customRules.broadCatchAllowedPaths.',
                39,
            ],
            [
                'Catch a specific exception type instead of "InvalidArgumentException": InvalidArgumentException is a programmer error (LogicException family) that must be fixed at its source, not caught. If this catch is an intentional top-level boundary handler, add its file path to customRules.broadCatchAllowedPaths.',
                48,
            ],
            [
                'Catch a specific exception type instead of "TypeError": TypeError is an engine failure (Error family) that must be fixed at its source, not caught. If this catch is an intentional top-level boundary handler, add its file path to customRules.broadCatchAllowedPaths.',
                57,
            ],
            [
                'Catch a specific exception type instead of "DivisionByZeroError": DivisionByZeroError is an engine failure (Error family) that must be fixed at its source, not caught. If this catch is an intentional top-level boundary handler, add its file path to customRules.broadCatchAllowedPaths.',
                66,
            ],
        ]);
    }

    public function testProcessNodeSpecificCatchesAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidBroadCatch/WithSpecificCatch.php'], []);
    }

    public function testProcessNodeAllowedBoundaryPathIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidBroadCatch/WithAllowedBroadCatch.php'], []);
    }
}
