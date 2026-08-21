<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\PhpDoc;

use function array_merge;

use PhpAiToolkit\PhpStan\Rule\Shared\LineOrderedErrors;
use PHPStan\Rules\IdentifierRuleError;

use function sprintf;

/**
 * Collects missing-example errors across the declarations of one class-like.
 */
final class PublicApiExampleErrorCollector
{
    /** @readonly */
    private PublicApiVisibilityDetector $visibilityDetector;

    /** @readonly */
    private RunnableExampleDetector $exampleDetector;

    /** @readonly */
    private MissingExampleErrorBuilder $errorBuilder;

    /** @readonly */
    private LineOrderedErrors $order;

    /**
     * Creates the collector from visibility detection, example detection, and error building.
     */
    public function __construct(
        ?PublicApiVisibilityDetector $visibilityDetector = null,
        ?RunnableExampleDetector $exampleDetector = null,
        ?MissingExampleErrorBuilder $errorBuilder = null,
        ?LineOrderedErrors $order = null,
    ) {
        $this->visibilityDetector = $visibilityDetector ?? new PublicApiVisibilityDetector();
        $this->exampleDetector = $exampleDetector ?? new RunnableExampleDetector();
        $this->errorBuilder = $errorBuilder ?? new MissingExampleErrorBuilder();
        $this->order = $order ?? new LineOrderedErrors();
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function errors(\PhpParser\Node\Stmt\ClassLike $node, string $kindLabel, string $className): array
    {
        return $this->order->sorted(array_merge(
            $this->classErrors($node, $kindLabel, $className),
            $this->methodErrors($node, $className),
            $this->propertyErrors($node, $className),
            $this->constantErrors($node, $className),
            $this->enumCaseErrors($node, $className),
        ));
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function classErrors(\PhpParser\Node\Stmt\ClassLike $node, string $kindLabel, string $className): array
    {
        return $this->errorsFor($node, 'customRules.requireExampleOnClass', sprintf('%s %s', $kindLabel, $className));
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function methodErrors(\PhpParser\Node\Stmt\ClassLike $node, string $className): array
    {
        $errors = [];
        foreach ($node->getMethods() as $method) {
            $subject = sprintf('method %s::%s()', $className, $method->name->toString());
            $errors = array_merge($errors, $this->errorsFor($method, 'customRules.requireExampleOnMethod', $subject));
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function propertyErrors(\PhpParser\Node\Stmt\ClassLike $node, string $className): array
    {
        $errors = [];
        foreach ($node->getProperties() as $property) {
            foreach ($property->props as $declared) {
                $subject = sprintf('property %s::$%s', $className, $declared->name->toString());
                $errors = array_merge($errors, $this->errorsFor($property, 'customRules.requireExampleOnProperty', $subject));
            }
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function constantErrors(\PhpParser\Node\Stmt\ClassLike $node, string $className): array
    {
        $errors = [];
        foreach ($node->getConstants() as $constant) {
            foreach ($constant->consts as $declared) {
                $subject = sprintf('constant %s::%s', $className, $declared->name->toString());
                $errors = array_merge($errors, $this->errorsFor($constant, 'customRules.requireExampleOnConstant', $subject));
            }
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function enumCaseErrors(\PhpParser\Node\Stmt\ClassLike $node, string $className): array
    {
        $errors = [];
        foreach ($node->stmts as $statement) {
            if (!$statement instanceof \PhpParser\Node\Stmt\EnumCase) {
                continue;
            }

            $subject = sprintf('enum case %s::%s', $className, $statement->name->toString());
            $errors = array_merge($errors, $this->errorsFor($statement, 'customRules.requireExampleOnEnumCase', $subject));
        }

        return $errors;
    }

    /**
     * Returns the error for one node, or nothing when it needs none.
     *
     * @return list<IdentifierRuleError>
     */
    public function errorsFor(\PhpParser\Node $node, string $identifier, string $subject): array
    {
        $docComment = $node->getDocComment();
        $text = $docComment === null ? null : $docComment->getText();
        if (!$this->visibilityDetector->declaresPublic($text) || $this->exampleDetector->hasRunnableExample($text)) {
            return [];
        }

        return [$this->errorBuilder->build($identifier, $subject, $node->getStartLine())];
    }
}
