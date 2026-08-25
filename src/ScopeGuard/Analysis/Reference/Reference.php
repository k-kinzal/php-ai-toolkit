<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Analysis\Reference;

/**
 * One place where source code names a declaration.
 *
 * @property-read string $className
 * @property-read ?string $memberName
 * @property-read string $kind
 * @property-read string $namespace
 * @property-read string $path
 * @property-read int $line
 *
 * @visibility parent
 */
final class Reference
{
    /**
     * @param string $kind how the name is written, such as instantiation or parameter type
     * @param string $namespace the namespace the reference is written in
     */
    public function __construct(
        /** @readonly */
        private string $className,
        /** @readonly */
        private ?string $memberName,
        /** @readonly */
        private string $kind,
        /** @readonly */
        private string $namespace,
        /** @readonly */
        private string $path,
        /** @readonly */
        private int $line,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'className' => $this->className,
            'memberName' => $this->memberName,
            'kind' => $this->kind,
            'namespace' => $this->namespace,
            'path' => $this->path,
            'line' => $this->line,
            default => null,
        };
    }
}
