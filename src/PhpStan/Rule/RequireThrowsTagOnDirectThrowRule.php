<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\RequireThrowsTagOnDirectThrow\ThrowsDeclarationInspector;
use PhpAiToolkit\PhpStan\Rule\RequireThrowsTagOnDirectThrow\ThrowSiteCollector;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;

/**
 * Requires a @throws tag for every exception thrown directly in a method body
 * and not caught within the same method.
 *
 * Unlike PHPStan's checked-exception analysis this also applies to unchecked
 * exceptions: declaring the throw at its origin keeps @throws documentation
 * accurate and keeps catch analysis sound when "exceptions.implicitThrows"
 * is disabled. Exceptions raised by called methods do not need to be
 * propagated manually; only direct throw statements are checked.
 *
 * @implements Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
final class RequireThrowsTagOnDirectThrowRule implements Rule
{
    /** @readonly */
    private ThrowSiteCollector $throwSiteCollector;

    /** @readonly */
    private ThrowsDeclarationInspector $throwsDeclarationInspector;

    /**
     * Creates the rule from throw-site collection and declaration inspection.
     */
    public function __construct(
        ?ThrowSiteCollector $throwSiteCollector = null,
        ?ThrowsDeclarationInspector $throwsDeclarationInspector = null,
    ) {
        $this->throwSiteCollector = $throwSiteCollector ?? new ThrowSiteCollector();
        $this->throwsDeclarationInspector = $throwsDeclarationInspector ?? new ThrowsDeclarationInspector();
    }

    /**
     * @return class-string<\PhpParser\Node\Stmt\ClassMethod>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt\ClassMethod::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassMethod $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if ($node->stmts === null || $node->stmts === []) {
            return [];
        }

        $sites = $this->throwSiteCollector->collect($node->stmts);
        if ($sites === []) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return [];
        }

        $methodName = $node->name->toString();
        if (!$classReflection->hasNativeMethod($methodName)) {
            return [];
        }
        $declaredThrowType = $classReflection->getNativeMethod($methodName)->getThrowType();

        $errors = [];
        foreach ($sites as $site) {
            foreach ($this->throwsDeclarationInspector->uncoveredClassNames($site, $scope, $declaredThrowType) as $thrownClassName) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'Declare "@throws \%s" in the PHPDoc of %s() or catch the exception inside the method. The exception thrown here escapes %s() without being declared.',
                        $thrownClassName,
                        $methodName,
                        $methodName
                    )
                )
                    ->identifier('customRules.missingThrowsTag')
                    ->line($site->line)
                    ->build();
            }
        }

        return $errors;
    }
}
