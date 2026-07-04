<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use ParseError;
use PhpAiToolkit\PhpStan\Support\NonDocCommentContext;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<FileNode>
 */
final class ForbidNonDocCommentRule implements Rule
{
    private const PHPSTAN_IGNORE_PATTERN = '/@phpstan-ignore(?:-line|-next-line)?\b/';
    private const INFECTION_IGNORE_ALL_PATTERN = '/@infection-ignore-all\b/';

    /**
     * @return class-string<FileNode>
     */
    public function getNodeType(): string
    {
        return FileNode::class;
    }

    /**
     * @param FileNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        unset($node);

        $tokens = $this->parseTokens($scope->getFile());
        if ($tokens === null) {
            return [];
        }

        return $this->processTokens($tokens);
    }

    /**
     * @return array<int, array{int, string, int}|string>|null
     */
    private function parseTokens(string $path): ?array
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $source = file_get_contents($path);
        if ($source === false) {
            return null;
        }

        try {
            return token_get_all($source, TOKEN_PARSE);
        } catch (ParseError) {
            return null;
        }
    }

    /**
     * @param array<int, array{int, string, int}|string> $tokens
     * @return list<IdentifierRuleError>
     */
    private function processTokens(array $tokens): array
    {
        $errors = [];
        $context = new NonDocCommentContext();

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                $context->registerStringToken($token);
                continue;
            }

            [$tokenType, $text, $line] = $token;

            if ($tokenType !== T_COMMENT) {
                $context->registerToken($tokenType, $text);
                continue;
            }

            if ($this->isHandledByForbiddenCommentRule($text)) {
                continue;
            }

            if (str_starts_with($text, '//') && $context->allowsLineComment()) {
                continue;
            }

            $errors[] = $this->buildNonDocCommentError($text, $line);
        }

        return $errors;
    }

    private function buildNonDocCommentError(string $text, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Non-PHPDoc comment is prohibited: "%s". Only /** ... */ PHPDoc blocks are allowed, except // comments inside catch blocks or array literals. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
                $this->truncateComment($text)
            )
        )
            ->identifier('customRules.nonDocComment')
            ->line($line)
            ->build();
    }

    /**
     * Checks whether the comment matches patterns already handled by ForbiddenCommentRule.
     */
    private function isHandledByForbiddenCommentRule(string $text): bool
    {
        if (preg_match(self::PHPSTAN_IGNORE_PATTERN, $text) === 1) {
            return true;
        }

        if (preg_match(self::INFECTION_IGNORE_ALL_PATTERN, $text) === 1) {
            return true;
        }

        return false;
    }


    /**
     * Truncates the comment text for display in the error message.
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
