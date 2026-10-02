<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ExceptionHandling;

use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Determines which thrown exceptions are neither caught nor declared.
 */
final class ThrowsDeclarationInspector
{
    /**
     * Returns thrown class names not covered by enclosing catches or the declared throw type.
     *
     * @return list<string>
     */
    public function uncoveredClassNames(ThrowSite $site, Scope $scope, ?Type $declaredThrowType): array
    {
        $uncovered = [];
        foreach ($site->thrownNames as $thrownName) {
            $thrownClassName = $scope->resolveName($thrownName);
            $thrownType = new ObjectType($thrownClassName);

            $caught = false;
            foreach ($site->guardNames as $guardName) {
                if ((new ObjectType($scope->resolveName($guardName)))->isSuperTypeOf($thrownType)->yes()) {
                    $caught = true;
                    break;
                }
            }
            if ($caught) {
                continue;
            }

            if ($declaredThrowType !== null && $declaredThrowType->isSuperTypeOf($thrownType)->yes()) {
                continue;
            }

            $uncovered[] = $thrownClassName;
        }

        return $uncovered;
    }
}
