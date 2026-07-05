<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPUnit\Framework\MockObject\Generator\Generator as MockGenerator;
use PHPUnit\Framework\MockObject\MockBuilder;

/**
 * Detects direct instantiation of PHPUnit mock infrastructure.
 */
final class PhpUnitMockInstantiationInspector
{
    private readonly PhpUnitMockApiErrorBuilder $errorBuilder;

    /**
     * Creates an inspector from mock API error building.
     */
    public function __construct(?PhpUnitMockApiErrorBuilder $errorBuilder = null)
    {
        $this->errorBuilder = $errorBuilder ?? new PhpUnitMockApiErrorBuilder();
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function errors(\PhpParser\Node\Expr\New_ $node, Scope $scope): array
    {
        if (!$node->class instanceof \PhpParser\Node\Name) {
            return [];
        }

        $resolvedName = $scope->resolveName($node->class);
        if (!in_array($resolvedName, [MockBuilder::class, MockGenerator::class], true)) {
            return [];
        }

        return [$this->errorBuilder->prohibitedInstantiation($resolvedName, $node->getStartLine())];
    }
}
