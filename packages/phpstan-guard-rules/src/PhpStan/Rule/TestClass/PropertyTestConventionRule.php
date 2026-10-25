<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\TestClass;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Toolkit\PhpStan\Support\TestClassScope;

/**
 * Enforces the directory, class, and method conventions for property tests.
 *
 * @implements Rule<InClassNode>
 */
final class PropertyTestConventionRule implements Rule
{
    /**
     * Creates the rule with the shared test scope detector.
     */
    public function __construct(private TestClassScope $testClassScope)
    {
    }

    /**
     * @return class-string<InClassNode>
     */
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getOriginalNode();
        if (!$class instanceof Class_ || !$this->testClassScope->isPropertyTestClass($scope)) {
            return [];
        }

        $className = $node->getClassReflection()->getName();
        if ($class->isAbstract()) {
            return [RuleErrorBuilder::message(sprintf('Declare %s as a concrete property test class extending PHPUnit\\Framework\\TestCase. Move traits, interfaces, enums, and abstract helpers out of tests/Property and Tests\\Property.', $className))
                ->identifier('customRules.propertyTestDeclaration')->line($class->getStartLine())->build()];
        }
        $errors = $this->classErrors($class, $className, $scope->getFile());
        $validator = new PropertyTestMethodValidator();
        foreach ($class->getMethods() as $method) {
            $methodErrors = $validator->errors($method, $className);
            $errors = array_merge($errors, $methodErrors);
            if ($node->getClassReflection()->hasTraitUse('Eris\\TestTrait') && $methodErrors === [] && !(new PropertyTestExecution())->runsEris($method)) {
                $errors[] = RuleErrorBuilder::message(sprintf('Execute an Eris property in %s::%s(): pass generators to $this->forAll(...) and run the resulting quantifier with ->then($assertion). A fixed example or an unexecuted callback does not run generated cases.', $className, $method->name->toString()))
                    ->identifier('customRules.propertyTestExecution')->line($method->getStartLine())->build();
            }
        }

        return $errors;
    }
    /**
     * Validates the class location, name, parent, and presence of declared tests.
     * @return list<IdentifierRuleError>
     */
    public function classErrors(Class_ $class, string $className, string $file): array
    {
        $name = $class->name !== null ? $class->name->toString() : '';
        $errors = [];
        if (!str_contains('/' . str_replace('\\', '/', $file), '/tests/Property/')) {
            $errors[] = RuleErrorBuilder::message(sprintf('Move property test class %s to tests/Property/%s.php. Property tests must be stored under tests/Property.', $className, $name))
                ->identifier('customRules.propertyTestLocation')->line($class->getStartLine())->build();
        }
        if (preg_match('/^[A-Z][a-zA-Z0-9]*Properties$/D', $name) !== 1) {
            $errors[] = RuleErrorBuilder::message(sprintf('Rename property test class %s to <Name>Properties (for example CollectionProperties) and use the same PHP filename.', $className))
                ->identifier('customRules.propertyTestClassName')->line($class->getStartLine())->build();
        }
        if ($class->extends === null || $class->extends->toString() !== 'PHPUnit\\Framework\\TestCase') {
            $errors[] = RuleErrorBuilder::message(sprintf('Make property test class %s extend PHPUnit\\Framework\\TestCase directly. Move shared test behavior to collaborators.', $className))
                ->identifier('customRules.propertyTestExtends')->line($class->getStartLine())->build();
        }
        if ($class->getMethods() === []) {
            $errors[] = RuleErrorBuilder::message(sprintf('Add at least one executable satisfies<PropertyName>() test to %s, with PHPUnit test metadata. Empty property test classes do not exercise any property.', $className))
                ->identifier('customRules.propertyTestEmpty')->line($class->getStartLine())->build();
        }
        return $errors;
    }
}
