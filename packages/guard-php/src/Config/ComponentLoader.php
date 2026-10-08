<?php

declare(strict_types=1);

namespace Guard\Config;

use Guard\Diagnostic\PolicyException;
use Guard\Policy\Policy;
use Guard\Structure\Structurer;
use ReflectionClass;
use ReflectionParameter;
use RuntimeException;
use TypeError;

/**
 * Constructs configured policies and structurers using their named constructor arguments.
 */
final class ComponentLoader
{
    /**
     * Loads the same functional interfaces used by built-in components.
     * @param array<string, mixed> $options
     * @throws PolicyException when the class or its constructor arguments are invalid
     */
    public function create(string $class, array $options): Policy|Structurer
    {
        if (!class_exists($class)) {
            throw new PolicyException('Class "' . $class . '" was not found. Install it or add it to Composer autoload and run composer dump-autoload.');
        }
        if (!is_a($class, Policy::class, true) && !is_a($class, Structurer::class, true)) {
            throw new PolicyException('Class "' . $class . '" must implement ' . Policy::class . ' or ' . Structurer::class . '. Register a policy or structurer directly.');
        }
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new PolicyException('Class "' . $class . '" needs a concrete implementation with a public constructor. Configure an instantiable policy or structurer.');
        }
        if ($reflection->getConstructor() === null && $options !== []) {
            throw new PolicyException('Class "' . $class . '" has no constructor. Remove its options or declare named constructor parameters.');
        }
        $constructor = $reflection->getConstructor();
        if ($constructor !== null && !$constructor->isVariadic()) {
            $names = array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $constructor->getParameters());
            $unknown = array_diff(array_keys($options), $names);
            if ($unknown !== []) {
                throw new PolicyException('Class "' . $class . '": unknown options ' . implode(', ', $unknown) . '. Check its named constructor arguments in guard.yaml.extensions.');
            }
        }
        try {
            return new $class(...$options);
        } catch (TypeError|RuntimeException $exception) {
            throw new PolicyException('Class "' . $class . '": ' . $exception->getMessage() . ' Check its named constructor arguments in guard.yaml.extensions.', 0, $exception);
        }
    }
}
