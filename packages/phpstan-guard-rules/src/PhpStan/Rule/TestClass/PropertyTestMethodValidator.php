<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\TestClass;

use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Validates declarations of executable property test methods.
 */
final class PropertyTestMethodValidator
{
    /** @readonly */
    private bool $supportsTestAttribute;

    /**
     * Uses the installed PHPUnit discovery API unless a compatibility mode is supplied.
     */
    public function __construct(?bool $supportsTestAttribute = null)
    {
        $this->supportsTestAttribute = $supportsTestAttribute ?? class_exists('PHPUnit\\Framework\\Attributes\\Test');
    }

    /**
     * Returns naming, visibility, and PHPUnit discovery errors for a declared method.
     *
     * @return list<IdentifierRuleError>
     */
    public function errors(ClassMethod $method, string $className): array
    {
        $name = $method->name->toString();
        if (preg_match('/^satisfies[A-Z][a-zA-Z0-9]*$/D', $name) !== 1) {
            return [RuleErrorBuilder::message(sprintf('Rename %s::%s() to satisfies<PropertyName> (for example satisfiesRoundTrip). Property test classes may declare only property test methods; move helpers, providers, and lifecycle methods out of the class.', $className, $name))
                ->identifier('customRules.propertyTestMethodName')->line($method->getStartLine())->build()];
        }

        $errors = [];
        if (!$method->isPublic() || $method->isStatic() || $method->isAbstract() || $method->params !== []) {
            $errors[] = RuleErrorBuilder::message(sprintf('Declare %s::%s() as a public, non-static, concrete method with no parameters. Generate inputs inside the property test.', $className, $name))
                ->identifier('customRules.propertyTestMethodSignature')->line($method->getStartLine())->build();
        }
        if (!$this->hasTestMetadata($method)) {
            $errors[] = RuleErrorBuilder::message(sprintf('Mark %s::%s() with #[PHPUnit\\Framework\\Attributes\\Test] (PHPUnit 10+) or @test (PHPUnit 9) so PHPUnit executes this property test.', $className, $name))
                ->identifier('customRules.propertyTestDiscovery')->line($method->getStartLine())->build();
        }

        return $errors;
    }

    /**
     * Recognizes PHPUnit's test attribute and legacy test annotation.
     */
    public function hasTestMetadata(ClassMethod $method): bool
    {
        foreach ($method->attrGroups as $group) {
            foreach ($group->attrs as $attribute) {
                if ($this->supportsTestAttribute && $attribute->name->toString() === 'PHPUnit\\Framework\\Attributes\\Test') {
                    return true;
                }
            }
        }

        $doc = $method->getDocComment();

        return !$this->supportsTestAttribute && $doc !== null && preg_match('/@test(?:\s|\*\/)/', $doc->getText()) === 1;
    }
}
