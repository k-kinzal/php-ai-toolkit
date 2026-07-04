<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use ParseError;
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
        $braceDepth = 0;
        $pendingCatch = false;
        $catchBodyBraceDepths = [];

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                $this->updateCatchBraceState($token, $braceDepth, $pendingCatch, $catchBodyBraceDepths);
                continue;
            }

            [$tokenType, $text, $line] = $token;

            if ($tokenType === T_CATCH) {
                $pendingCatch = true;

                continue;
            }

            if ($tokenType !== T_COMMENT) {
                continue;
            }

            if ($this->isHandledByForbiddenCommentRule($text)) {
                continue;
            }

            if ($this->isAllowedCatchLineComment($text, $catchBodyBraceDepths)) {
                continue;
            }

            $errors[] = $this->buildNonDocCommentError($text, $line);
        }

        return $errors;
    }

    /**
     * @param list<int> $catchBodyBraceDepths
     */
    private function updateCatchBraceState(
        string $token,
        int &$braceDepth,
        bool &$pendingCatch,
        array &$catchBodyBraceDepths,
    ): void {
        if ($token === '{') {
            ++$braceDepth;

            if ($pendingCatch) {
                $catchBodyBraceDepths[] = $braceDepth;
                $pendingCatch = false;
            }
        }

        if ($token !== '}') {
            return;
        }

        $lastCatchBodyDepth = $catchBodyBraceDepths[count($catchBodyBraceDepths) - 1] ?? null;
        if ($lastCatchBodyDepth === $braceDepth) {
            array_pop($catchBodyBraceDepths);
        }

        if ($braceDepth > 0) {
            --$braceDepth;
        }
    }

    private function buildNonDocCommentError(string $text, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Non-PHPDoc comment is prohibited: "%s". Only /** ... */ PHPDoc blocks are allowed, except // comments inside catch blocks. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
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
     * @param list<int> $catchBodyBraceDepths
     */
    private function isAllowedCatchLineComment(string $text, array $catchBodyBraceDepths): bool
    {
        return $catchBodyBraceDepths !== [] && str_starts_with($text, '//');
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
