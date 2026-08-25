<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\Type\Rule;

use PhpAiToolkit\PhpStan\Rule\Type\MixedLocalPhpDocErrorCollector;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

use function sprintf;

/**
 * Applies the internal mixed policy to local @var declarations.
 *
 * @implements Rule<\PhpParser\Node\Stmt>
 */
final class ForbidInternalMixedLocalVarRule implements Rule
{
    /** @readonly */
    private MixedLocalPhpDocErrorCollector $collector;

    /**
     * Creates the rule from local PHPDoc inspection.
     */
    public function __construct(?MixedLocalPhpDocErrorCollector $collector = null)
    {
        $this->collector = $collector ?? new MixedLocalPhpDocErrorCollector();
    }

    /**
     * @return class-string<\PhpParser\Node\Stmt>
     */
    public function getNodeType(): string
    {
        return \PhpParser\Node\Stmt::class;
    }

    /**
     * @param \PhpParser\Node\Stmt $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(\PhpParser\Node $node, Scope $scope): array
    {
        if ($node instanceof \PhpParser\Node\Stmt\ClassLike
            || $node instanceof \PhpParser\Node\Stmt\ClassMethod
            || $node instanceof \PhpParser\Node\Stmt\Function_
            || $node instanceof \PhpParser\Node\Stmt\Property
            || $node instanceof \PhpParser\Node\Stmt\ClassConst
            || $node instanceof \PhpParser\Node\Stmt\EnumCase) {
            return [];
        }

        $functionName = $scope->getFunctionName();
        $class = $scope->getClassReflection();
        if ($class !== null && $functionName !== null) {
            $symbol = sprintf('%s::%s()', $class->getDisplayName(), $functionName);
        } elseif ($functionName !== null) {
            $symbol = sprintf('%s()', $functionName);
        } else {
            $symbol = 'file scope';
        }

        return $this->collector->errors($node, $symbol);
    }
}
