<?php

declare(strict_types=1);

namespace Guard\Extension;

use Guard\Policy\PolicyException;
use ReflectionClass;
use RuntimeException;

/**
 * Loads explicitly configured, Composer-autoloadable extensions before collection.
 */
final class ExtensionLoader
{
    /**
     * Adds configured extensions after the registry's existing registrations.
     * @param array<string, array<string, mixed>> $extensions
     * @throws PolicyException when an extension cannot be constructed or registered
     */
    public function register(array $extensions, Registry $registry): void
    {
        foreach ($extensions as $class => $options) {
            try {
                $this->create($class, $options)->register($registry);
            } catch (RuntimeException $exception) {
                throw new PolicyException('Extension "' . $class . '": ' . $exception->getMessage(), 0, $exception);
            }
        }
    }

    /**
     * Uses the configuration factory, or a no-argument constructor for an optionless extension.
     * @param array<string, mixed> $options
     * @throws PolicyException when the class or options do not satisfy the extension contract
     */
    public function create(string $class, array $options): Extension
    {
        if (!class_exists($class)) {
            throw new PolicyException('Class "' . $class . '" was not found. Install it or add it to Composer autoload and run composer dump-autoload.');
        }
        if (!is_a($class, Extension::class, true)) {
            throw new PolicyException('Class "' . $class . '" must implement ' . Extension::class . '.');
        }
        $reflection = new ReflectionClass($class);
        if ($reflection->isAbstract()) {
            throw new PolicyException('Class "' . $class . '" is abstract. Configure a concrete extension class.');
        }
        if (is_a($class, ConfigurableExtension::class, true)) {
            return $class::fromOptions($options);
        }
        if ($options !== []) {
            throw new PolicyException('Options require ' . ConfigurableExtension::class . '. Implement fromOptions() or remove the options.');
        }
        $constructor = $reflection->getConstructor();
        if (!$reflection->isInstantiable() || ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0)) {
            throw new PolicyException('Class "' . $class . '" needs a public no-argument constructor or a ' . ConfigurableExtension::class . ' factory.');
        }
        return new $class();
    }
}
