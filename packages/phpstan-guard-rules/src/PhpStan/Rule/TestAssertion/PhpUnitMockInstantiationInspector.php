<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\TestAssertion;

use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPUnit\Framework\MockObject\Generator\Generator as MockGenerator;
use PHPUnit\Framework\MockObject\MockBuilder;

/**
 * Detects direct instantiation of PHPUnit mock infrastructure.
 */
final class PhpUnitMockInstantiationInspector
{
    /** @readonly */
    private PhpUnitMockApiErrorBuilder $errorBuilder;

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
    public function errors(New_ $node, Scope $scope): array
    {
        if (!$node->class instanceof Name) {
            return [];
        }

        $resolvedName = $scope->resolveName($node->class);
        if (!in_array($resolvedName, [MockBuilder::class, MockGenerator::class], true)) {
            return [];
        }

        return [$this->errorBuilder->prohibitedInstantiation($resolvedName, $node->getStartLine())];
    }
}
