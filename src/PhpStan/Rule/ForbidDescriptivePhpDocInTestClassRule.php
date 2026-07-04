<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbids descriptive (non-annotation) PHPDoc text in test classes.
 *
 * In restricted test namespaces, PHPDoc on classes and methods must contain
 * only @tags (e.g. @dataProvider, @extends). Descriptive prose text is forbidden
 * because test names and class names should be self-documenting.
 *
 * @implements Rule<\PhpParser\Node\Stmt\ClassLike>
 */
final class ForbidDescriptivePhpDocInTestClassRule implements Rule
{
    /** @var list<string> */
    private readonly array $restrictedTestNamespacePrefixes;

    /**
     * @param list<string> $restrictedTestNamespacePrefixes namespace prefixes for test classes
     */
    public function __construct(
        array $restrictedTestNamespacePrefixes = ['Tests\\Unit', 'Tests\\Integration'],
    ) {
        $this->restrictedTestNamespacePrefixes = array_map(
            static fn (string $prefix): string => rtrim($prefix, '\\') . '\\',
            $restrictedTestNamespacePrefixes,
        );
    }

    /**
     * @return class-string<\PhpParser\Node\Stmt\ClassLike>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\ClassLike::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassLike $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if (!$this->isInRestrictedTestNamespace($node)) {
            return [];
        }

        $className = $node->name !== null ? $node->name->toString() : '(anonymous)';
        $errors = [];

        $docComment = $node->getDocComment();
        if ($docComment !== null && $this->hasDescriptiveText($docComment->getText())) {
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
            if ($methodDoc === null) {
                continue;
            }

            if (!$this->hasDescriptiveText($methodDoc->getText())) {
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

    /**
     * Checks whether the class is in a restricted test namespace.
     */
    private function isInRestrictedTestNamespace(\PhpParser\Node\Stmt\ClassLike $node): bool
    {
        if (!isset($node->namespacedName)) {
            return false;
        }

        $fqcn = $node->namespacedName->toString();

        foreach ($this->restrictedTestNamespacePrefixes as $prefix) {
            if (str_starts_with($fqcn, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether the PHPDoc contains descriptive text before the first @tag.
     */
    private function hasDescriptiveText(string $docComment): bool
    {
        $lines = explode("\n", $docComment);

        foreach ($lines as $line) {
            $cleaned = $this->cleanDocLine($line);

            if ($cleaned === '') {
                continue;
            }

            if (str_starts_with($cleaned, '@')) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Strips PHPDoc delimiters and leading asterisks from a single line.
     */
    private function cleanDocLine(string $line): string
    {
        $line = preg_replace('#^\s*/?\*\*/?#', '', $line) ?? $line;
        $line = preg_replace('#^\s*\*/?#', '', $line) ?? $line;
        $line = preg_replace('#\s*\*/$#', '', $line) ?? $line;

        return trim($line);
    }
}
