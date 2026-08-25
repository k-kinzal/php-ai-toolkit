<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\ExceptionHandling;

use function array_map;
use function implode;
use function is_string;

use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;

/**
 * Requires exceptions thrown inside a catch block to chain the caught one.
 *
 * Wrapping a low-level exception in a domain exception is good practice, but
 * only when the original exception travels along as $previous. Otherwise the
 * root cause and its stack trace are unrecoverable at the top-level handler.
 *
 * @implements Rule<\PhpParser\Node\Stmt\Catch_>
 */
final class RequireExceptionChainingRule implements Rule
{
    /** @readonly */
    private CatchThrowCollector $catchThrowCollector;

    /** @readonly */
    private ThrowChainEvaluator $throwChainEvaluator;

    /**
     * Creates the rule from throw collection and chain evaluation.
     */
    public function __construct(
        ?CatchThrowCollector $catchThrowCollector = null,
        ?ThrowChainEvaluator $throwChainEvaluator = null,
    ) {
        $this->catchThrowCollector = $catchThrowCollector ?? new CatchThrowCollector();
        $this->throwChainEvaluator = $throwChainEvaluator ?? new ThrowChainEvaluator();
    }

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

        $variable = $node->var;
        $caughtVariableName = $variable instanceof Variable && is_string($variable->name) ? $variable->name : null;

        $errors = [];
        foreach ($this->catchThrowCollector->collect($node->stmts) as $throw) {
            if (!$this->throwChainEvaluator->violates($throw, $caughtVariableName)) {
                continue;
            }

            if ($caughtVariableName === null) {
                $caughtTypes = implode('|', array_map(static fn (Name $name): string => $name->toString(), $node->types));
                $message = sprintf(
                    'Bind the caught %s to a variable and pass it to the exception thrown in this catch block, e.g. as the $previous constructor argument. Throwing a new exception without chaining discards the original failure and its stack trace.',
                    $caughtTypes
                );
            } else {
                $message = sprintf(
                    'Pass the caught $%s to the exception thrown in this catch block, e.g. as the $previous constructor argument. Throwing a new exception without chaining discards the original failure and its stack trace.',
                    $caughtVariableName
                );
            }

            $errors[] = RuleErrorBuilder::message($message)
                ->identifier('customRules.unchainedRethrow')
                ->line($throw->getStartLine())
                ->build();
        }

        return $errors;
    }
}
