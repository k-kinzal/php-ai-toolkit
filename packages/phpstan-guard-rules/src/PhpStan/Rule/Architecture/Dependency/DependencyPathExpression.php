<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Architecture\Dependency;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * Evaluates definite path expressions without executing project code.
 */
final class DependencyPathExpression
{
    /**
     * Creates the evaluator with PHPStan-aware function name resolution.
     */
    public function __construct(private DependencyCallResolver $calls)
    {
    }

    /**
     * Resolves magic file constants even when PHPStan generalizes them to string.
     */
    public function resolve(Node\Expr $expression, Scope $scope): ?string
    {
        $file = $scope->isInTrait() ? $scope->getTraitReflection()->getFileName() : $scope->getFile();
        if ($expression instanceof Node\Scalar\MagicConst\Dir) {
            return $file !== null ? dirname($file) : null;
        }
        if ($expression instanceof Node\Scalar\MagicConst\File) {
            return $file;
        }
        if ($expression instanceof Node\Scalar\String_) {
            return $expression->value;
        }
        if ($expression instanceof Node\Expr\BinaryOp\Concat) {
            $left = $this->resolve($expression->left, $scope);
            $right = $this->resolve($expression->right, $scope);

            return $left !== null && $right !== null ? $left . $right : null;
        }
        if ($expression instanceof Node\Expr\FuncCall) {
            $call = $this->calls->resolve($expression, $scope);
            if ($call !== null && strtolower($this->calls->name($call)) === 'dirname') {
                return $this->directory($expression, $scope);
            }
        }
        $type = $scope->getType($expression);
        $strings = $type->getConstantStrings();

        return $type->isConstantScalarValue()->yes() && count($strings) === 1 ? $strings[0]->getValue() : null;
    }

    /**
     * Evaluates built-in dirname with a known path and a positive constant level.
     */
    public function directory(Node\Expr\FuncCall $call, Scope $scope): ?string
    {
        if ($call->isFirstClassCallable()) {
            return null;
        }
        foreach ($call->getArgs() as $argument) {
            if ($argument->unpack) {
                return null;
            }
        }
        $argument = $this->calls->argument($call, 0, 'path');
        $levels = $this->calls->argument($call, 1, 'levels');
        if ($argument === null) {
            return null;
        }
        $path = $this->resolve($argument, $scope);
        if ($path === null) {
            return null;
        }
        if ($levels === null) {
            return dirname($path);
        }
        $type = $scope->getType($levels);
        $integers = $type->getConstantScalarValues();
        if (!$type->isConstantScalarValue()->yes() || count($integers) !== 1 || !is_int($integers[0]) || $integers[0] < 1) {
            return null;
        }

        return dirname($path, $integers[0]);
    }
}
