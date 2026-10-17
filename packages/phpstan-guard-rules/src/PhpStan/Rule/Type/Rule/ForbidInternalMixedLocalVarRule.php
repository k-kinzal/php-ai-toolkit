<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Type\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\EnumCase;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

use function sprintf;

use Toolkit\PhpStan\Rule\Type\MixedLocalPhpDocErrorCollector;

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
     * @return class-string<Stmt>
     */
    public function getNodeType(): string
    {
        return Stmt::class;
    }

    /**
     * @param Stmt $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof ClassLike
            || $node instanceof ClassMethod
            || $node instanceof Function_
            || $node instanceof Property
            || $node instanceof ClassConst
            || $node instanceof EnumCase) {
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
