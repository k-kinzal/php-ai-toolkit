<?php

declare(strict_types=1);

namespace PhpStanAiRules\Rule;

use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\IdentifierRuleError;
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

        $path = $scope->getFile();

        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        $source = file_get_contents($path);
        if ($source === false) {
            return [];
        }

        try {
            $tokens = token_get_all($source, TOKEN_PARSE);
        } catch (\ParseError) {
            return [];
        }

        $errors = [];

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }

            [$tokenType, $text, $line] = $token;

            if ($tokenType !== T_COMMENT) {
                continue;
            }

            if ($this->isHandledByForbiddenCommentRule($text)) {
                continue;
            }

            $commentContent = $this->truncateComment($text);
            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Non-PHPDoc comment is prohibited: "%s". Only /** ... */ PHPDoc blocks are allowed. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
                    $commentContent
                )
            )
                ->identifier('customRules.nonDocComment')
                ->line($line)
                ->build();
        }

        return $errors;
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
