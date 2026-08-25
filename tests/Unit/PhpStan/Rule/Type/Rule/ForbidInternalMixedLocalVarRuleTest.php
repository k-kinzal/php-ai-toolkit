<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Type\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser;
use PhpAiToolkit\PhpStan\Rule\Type\MixedLocalPhpDocErrorCollector;
use PhpAiToolkit\PhpStan\Rule\Type\Rule\ForbidInternalMixedLocalVarRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @extends RuleTestCase<ForbidInternalMixedLocalVarRule>
 */
#[CoversClass(ForbidInternalMixedLocalVarRule::class)]
#[UsesClass(MixedLocalPhpDocErrorCollector::class)]
#[UsesClass(RulePhpDocParser::class)]
#[Medium]
final class ForbidInternalMixedLocalVarRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbidInternalMixedLocalVarRule();
    }

    public function testGetNodeTypeReturnsStatementNode(): void
    {
        self::assertSame(\PhpParser\Node\Stmt::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeReportsLocalVarMixed(): void
    {
        $guidance = ': this declaration is internal or scope-restricted, so it must state a deterministic PHPStan type. Validate arbitrary input at an unrestricted public boundary, then pass the narrowed type inward.';
        $this->analyse([__DIR__ . '/../../../../../Fixture/ForbidInternalMixedType/ForbiddenMixedTypes.php'], [
            ['Replace concrete mixed type "mixed" in local @var $local of Tests\Fixture\ForbidInternalMixedType\RestrictedTypes::transform()' . $guidance, 25],
            ['Replace concrete mixed type "mixed" in local @var $item of file scope' . $guidance, 60],
        ]);
    }
}
