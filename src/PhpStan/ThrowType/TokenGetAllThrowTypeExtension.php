<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\ThrowType;

use function is_int;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Declares the ParseError thrown by token_get_all() with the TOKEN_PARSE flag.
 *
 * PHPStan has no throw metadata for token_get_all(), so under
 * "exceptions.implicitThrows: false" a catch of ParseError around it is
 * falsely reported as a dead catch. This extension restores that metadata.
 */
final class TokenGetAllThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{
    /**
     * Supports the token_get_all() function only.
     */
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'token_get_all';
    }

    /**
     * Returns ParseError when the TOKEN_PARSE flag may be set, or null otherwise.
     */
    public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
    {
        $args = $functionCall->getArgs();
        if (!isset($args[1])) {
            return null;
        }

        $flagValues = $scope->getType($args[1]->value)->getConstantScalarValues();
        if ($flagValues !== []) {
            $mayParse = false;
            foreach ($flagValues as $flagValue) {
                if (!is_int($flagValue) || ($flagValue & TOKEN_PARSE) === TOKEN_PARSE) {
                    $mayParse = true;
                }
            }
            if (!$mayParse) {
                return null;
            }
        }

        return new ObjectType('ParseError');
    }
}
