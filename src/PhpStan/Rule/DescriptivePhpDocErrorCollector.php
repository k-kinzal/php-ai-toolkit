<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Collects descriptive PHPDoc errors in restricted test classes.
 */
final class DescriptivePhpDocErrorCollector
{
    /** @readonly */
    private DescriptivePhpDocTextDetector $textDetector;

    /**
     * Creates a collector from namespace matching and PHPDoc text detection.
     */
    public function __construct(
        /** @readonly */
        private RestrictedTestNamespaceMatcher $namespaceMatcher,
        ?DescriptivePhpDocTextDetector $textDetector = null,
    ) {
        $this->textDetector = $textDetector ?? new DescriptivePhpDocTextDetector();
    }

    /**
     * Returns descriptive PHPDoc errors for the class-like node.
     *
     * @return list<IdentifierRuleError>
     */
    public function errors(\PhpParser\Node\Stmt\ClassLike $node): array
    {
        if (!$this->namespaceMatcher->matches($node)) {
            return [];
        }

        $className = $node->name !== null ? $node->name->toString() : '(anonymous)';
        $errors = [];

        $docComment = $node->getDocComment();
        if ($docComment !== null && $this->textDetector->has($docComment->getText())) {
            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Test class %s has descriptive PHPDoc text. Remove the description. Annotation-only PHPDoc (e.g., @extends) is allowed.',
                    $className
                )
            )
                ->identifier('customRules.testClassDescriptivePhpDoc')
                ->line($node->getStartLine())
                ->build();
        }

        foreach ($node->getMethods() as $method) {
            $methodDoc = $method->getDocComment();
            if ($methodDoc === null || !$this->textDetector->has($methodDoc->getText())) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Method %s::%s() has descriptive PHPDoc text. Remove the description. Annotation-only PHPDoc (e.g., @dataProvider) is allowed.',
                    $className,
                    $method->name->toString()
                )
            )
                ->identifier('customRules.testClassDescriptivePhpDoc')
                ->line($method->getStartLine())
                ->build();
        }

        return $errors;
    }
}
