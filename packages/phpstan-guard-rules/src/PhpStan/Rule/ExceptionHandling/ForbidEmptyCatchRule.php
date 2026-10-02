<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ExceptionHandling;

use function array_map;
use function implode;

use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;

/**
 * Forbids catch blocks with an empty body.
 *
 * An empty catch block silently discards the failure it captured. The
 * exception must be rethrown, wrapped, logged, or replaced by a deliberate
 * fallback so the failure stays observable.
 *
 * @implements Rule<\PhpParser\Node\Stmt\Catch_>
 */
final class ForbidEmptyCatchRule implements Rule
{
    /**
     * @return class-string<\PhpParser\Node\Stmt\Catch_>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\Catch_::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\Catch_ $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        unset($scope);

        if ($node->stmts !== []) {
            return [];
        }

        $caughtTypes = implode('|', array_map(static fn (Name $name): string => $name->toString(), $node->types));

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Handle the caught %s in this empty catch block: rethrow it, wrap it in a more specific exception with $previous, or log it and recover. An empty catch silently discards the failure.',
                    $caughtTypes
                )
            )
                ->identifier('customRules.emptyCatch')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}
