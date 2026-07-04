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
final class ForbiddenCommentRule implements Rule
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

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }

            [$tokenType, $text, $line] = $token;

            if (!in_array($tokenType, [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            if (preg_match(self::PHPSTAN_IGNORE_PATTERN, $text) === 1) {
                $errors[] = $this->buildPhpstanIgnoreError($text, $line);
            }

            if (preg_match(self::INFECTION_IGNORE_ALL_PATTERN, $text) === 1) {
                $errors[] = $this->buildInfectionIgnoreAllError($text, $line);
            }
        }

        return $errors;
    }

    private function buildPhpstanIgnoreError(string $text, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'phpstan-ignore comments are prohibited: "%s". Remove this comment and re-run PHPStan to reveal the actual error it was suppressing, then fix the root cause. If the error is a false positive, ask a human operator to add an ignoreErrors entry in phpstan.neon with the error\'s identifier.',
                $this->truncateComment($text)
            )
        )
            ->identifier('customRules.phpstanIgnoreComment')
            ->line($this->reportedLineForIgnoreComment($text, $line))
            ->build();
    }

    private function buildInfectionIgnoreAllError(string $text, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'infection-ignore-all comments are prohibited: "%s". Remove this comment and run mutation testing to identify surviving mutants, then strengthen assertions or add test cases to kill them. If the code is genuinely untestable, restructure it to improve testability.',
                $this->truncateComment($text)
            )
        )
            ->identifier('customRules.infectionIgnoreAllComment')
            ->line($line)
            ->build();
    }

    private function reportedLineForIgnoreComment(string $text, int $line): int
    {
        if (str_contains($text, '@phpstan-ignore-line')) {
            return $line + 1;
        }

        return $line;
    }

    private function truncateComment(string $text): string
    {
        $trimmed = trim($text);
        if (mb_strlen($trimmed) > 80) {
            return mb_substr($trimmed, 0, 80) . '...';
        }

        return $trimmed;
    }
}
