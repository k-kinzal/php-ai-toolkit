<?php

declare(strict_types=1);

namespace PhpStanAiRules\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbids single-line PHPDoc comments on public API elements.
 *
 * All PHPDoc comments on classes, interfaces, traits, enums,
 * public methods, public properties, and public constants
 * must use the multi-line format.
 *
 * @implements Rule<\PhpParser\Node\Stmt\ClassLike>
 */
final class ForbidSingleLinePhpDocRule implements Rule
{
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
        if ($this->isAnonymousClass($node, $scope)) {
            return [];
        }

        $errors = [];

        $classDoc = $node->getDocComment();
        if ($classDoc !== null && $this->isSingleLine($classDoc->getText())) {
            $errors[] = $this->buildError($classDoc->getText(), $classDoc->getStartLine());
        }

        foreach ($node->getMethods() as $method) {
            if (!$method->isPublic()) {
                continue;
            }

            $doc = $method->getDocComment();
            if ($doc !== null && $this->isSingleLine($doc->getText())) {
                $errors[] = $this->buildError($doc->getText(), $doc->getStartLine());
            }
        }

        foreach ($node->getProperties() as $property) {
            if (!$property->isPublic()) {
                continue;
            }

            $doc = $property->getDocComment();
            if ($doc !== null && $this->isSingleLine($doc->getText())) {
                $errors[] = $this->buildError($doc->getText(), $doc->getStartLine());
            }
        }

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof \PhpParser\Node\Stmt\ClassConst) {
                continue;
            }

            if (!$stmt->isPublic()) {
                continue;
            }

            $doc = $stmt->getDocComment();
            if ($doc !== null && $this->isSingleLine($doc->getText())) {
                $errors[] = $this->buildError($doc->getText(), $doc->getStartLine());
            }
        }

        return $errors;
    }

    /**
     * Checks whether the class-like node represents an anonymous class.
     */
    private function isAnonymousClass(\PhpParser\Node\Stmt\ClassLike $node, Scope $scope): bool
    {
        if (!$node instanceof \PhpParser\Node\Stmt\Class_) {
            return false;
        }

        if ($node->name === null) {
            return true;
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection !== null && $classReflection->isAnonymous()) {
            return true;
        }

        return str_starts_with($node->name->toString(), 'AnonymousClass');
    }

    /**
     * Checks whether a PHPDoc comment is written on a single line.
     */
    private function isSingleLine(string $text): bool
    {
        return strpos($text, "\n") === false;
    }

    /**
     * Builds a rule error for a single-line PHPDoc comment.
     */
    private function buildError(string $text, int $line): IdentifierRuleError
    {
        $truncated = $this->truncateComment($text);

        return RuleErrorBuilder::message(
            sprintf(
                'Single-line PHPDoc is prohibited: "%s". Rewrite as a multi-line PHPDoc block: open with /** on its own line, write the description on the next line prefixed with " * ", and close with */ on its own line.',
                $truncated
            )
        )
            ->identifier('customRules.singleLinePhpDoc')
            ->line($line)
            ->build();
    }

    /**
     * Truncates a comment to a maximum display length.
     */
    private function truncateComment(string $text): string
    {
        $trimmed = trim($text);
        if (mb_strlen($trimmed) > 80) {
            return mb_substr($trimmed, 0, 80) . '...';
        }

        return $trimmed;
    }
}
