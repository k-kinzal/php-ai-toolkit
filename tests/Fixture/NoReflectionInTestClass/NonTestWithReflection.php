<?php

declare(strict_types=1);

namespace App\Fixture\NoReflectionInTestClass;

use ReflectionClass;

class NonTestWithReflection
{
    /**
     * @return list<string>
     */
    public function getPublicMethods(string $className): array
    {
        $reflection = new ReflectionClass($className);

        return array_map(
            static fn (\ReflectionMethod $method): string => $method->getName(),
            $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
        );
    }
}
