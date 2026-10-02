<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\TestAssertion;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use Toolkit\PhpStan\Support\TestClassScope;

/**
 * Forbids expecting Throwable or the LogicException and Error families in test cases.
 *
 * A test that expects one of those types asserts that broken code fails, which
 * is true of any code that is broken and therefore proves nothing about the
 * class under test. Such a test case is invalid and belongs deleted.
 *
 * @implements Rule<\PhpParser\Node\Expr>
 */
final class NoBrokenCodeExpectationRule implements Rule
{
    /** @readonly */
    private ExpectExceptionCallInspector $inspector;

    /**
     * @param TestClassScope $testClassScope test class scope detector
     */
    public function __construct(
        /** @readonly */
        private TestClassScope $testClassScope,
        ?ExpectExceptionCallInspector $inspector = null,
    ) {
        $this->inspector = $inspector ?? new ExpectExceptionCallInspector();
    }

    /**
     * @return class-string<\PhpParser\Node\Expr>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Expr::class;
    }

    /**
     * @param \PhpParser\Node\Expr $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if (!$this->testClassScope->isTestClass($scope)) {
            return [];
        }

        return $this->inspector->errors($node, $scope);
    }
}
