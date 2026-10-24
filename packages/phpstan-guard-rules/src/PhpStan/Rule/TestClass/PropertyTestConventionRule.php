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
        $name = $class->name !== null ? $class->name->toString() : '';
        $errors = [];
        if (!str_contains('/' . str_replace('\\', '/', $scope->getFile()), '/tests/Property/')) {
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
        $validator = new PropertyTestMethodValidator();
        foreach ($class->getMethods() as $method) {
            $errors = array_merge($errors, $validator->errors($method, $className));
        }

        return $errors;
    }
}
