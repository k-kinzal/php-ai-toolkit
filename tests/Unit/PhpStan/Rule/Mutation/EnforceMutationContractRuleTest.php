<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Mutation;

use Override;
use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\Mutation\MutationContract;
use Toolkit\Mutation\MutationContractReader;
use Toolkit\PhpStan\Rule\Mutation\CallableId;
use Toolkit\PhpStan\Rule\Mutation\EnforceMutationContractRule;
use Toolkit\PhpStan\Rule\Mutation\MutationDeclarationCollector;
use Toolkit\PhpStan\Rule\Mutation\MutationInspector;
use Toolkit\PhpStan\Rule\Mutation\MutationOperationCollector;
use Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser;

/**
 * @extends RuleTestCase<EnforceMutationContractRule>
 */
#[CoversClass(EnforceMutationContractRule::class)]
#[UsesClass(MutationInspector::class)]
#[UsesClass(MutationDeclarationCollector::class)]
#[UsesClass(MutationOperationCollector::class)]
#[UsesClass(CallableId::class)]
#[UsesClass(MutationContract::class)]
#[UsesClass(MutationContractReader::class)]
#[UsesClass(RulePhpDocParser::class)]
#[Medium]
final class EnforceMutationContractRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new EnforceMutationContractRule();
    }

    /**
     * @return list<Collector<\PhpParser\Node, mixed>>
     */
    #[Override]
    protected function getCollectors(): array
    {
        return [new MutationDeclarationCollector(), new MutationOperationCollector(self::createReflectionProvider())];
    }

    public function testGetNodeTypeReturnsCollectedDataNode(): void
    {
        self::assertSame(\PHPStan\Node\CollectedDataNode::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeEnforcesDirectTransferredAndInheritedEffects(): void
    {
        $this->analyse([__DIR__ . '/../../../../Fixture/MutationContract/project/src/MutationCases.php'], [
            [
                'Tests\Fixture\MutationContract\MutationCases::directArgument() mutates $box without declaring that effect. Add +mut immediately after $box in its @param tag.',
                36,
            ],
            [
                'Tests\Fixture\MutationContract\MutationCases::directThis() mutates $this without declaring that effect. Add "@mutation $this" to the method PHPDoc.',
                41,
            ],
            [
                'Tests\Fixture\MutationContract\MutationCases::directGlobal() mutates global state without declaring that effect. Add "@mutation global" to the callable PHPDoc.',
                46,
            ],
            [
                'Tests\Fixture\MutationContract\MutationCases::transfersArgument() mutates $box without declaring that effect. Add +mut immediately after $box in its @param tag.',
                52,
            ],
            [
                'Tests\Fixture\MutationContract\MutationCases::transfersThis() mutates $this without declaring that effect. Add "@mutation $this" to the method PHPDoc.',
                57,
            ],
            [
                'Tests\Fixture\MutationContract\MutationCases::transfersGlobal() mutates global state without declaring that effect. Add "@mutation global" to the callable PHPDoc.',
                68,
            ],
            [
                'Tests\Fixture\MutationContract\MutationCases::aliasesArgument() mutates $box without declaring that effect. Add +mut immediately after $box in its @param tag.',
                75,
            ],
            [
                'Tests\Fixture\MutationContract\passes_box() mutates $box without declaring that effect. Add +mut immediately after $box in its @param tag.',
                94,
            ],
            [
                'Invalid mutation contract on Tests\Fixture\MutationContract\malformed(): Place +mut immediately after $box in the @param tag.',
                98,
            ],
            [
                'Mutation contract on Tests\Fixture\MutationContract\WideningImplementation::work() widens inherited effect $box. Remove that effect, or declare it on Tests\Fixture\MutationContract\ReadOnlyContract::work() so every caller sees the same permission.',
                111,
            ],
            [
                'Tests\Fixture\MutationContract\passes_box_transitively() mutates $box without declaring that effect. Add +mut immediately after $box in its @param tag.',
                144,
            ],
            [
                'Tests\Fixture\MutationContract\reassigns_box() mutates $box without declaring that effect. Add +mut immediately after $box in its @param tag.',
                150,
            ],
            [
                'Tests\Fixture\MutationContract\changes_static_cache() mutates global state without declaring that effect. Add "@mutation global" to the callable PHPDoc.',
                156,
            ],
        ]);
    }
}
