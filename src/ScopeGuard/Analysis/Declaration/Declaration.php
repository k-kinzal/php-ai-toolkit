<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Analysis\Declaration;

use Toolkit\ScopeGuard\Analysis\Scope\VisibilityScope;

/**
 * One declaration that carries a visibility scope.
 *
 * @property-read string $symbol
 * @property-read string $kind
 * @property-read string $namespace
 * @property-read VisibilityScope $scope
 * @property-read string $path
 * @property-read int $line
 *
 * @visibility parent
 */
final class Declaration
{
    /**
     * @param string $symbol display name, such as App\Domain\Order or App\Domain\Order::place()
     * @param string $kind declaration keyword, such as class, method, or enum case
     */
    public function __construct(
        /** @readonly */
        private string $symbol,
        /** @readonly */
        private string $kind,
        /** @readonly */
        private string $namespace,
        /** @readonly */
        private VisibilityScope $scope,
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
            'symbol' => $this->symbol,
            'kind' => $this->kind,
            'namespace' => $this->namespace,
            'scope' => $this->scope,
            'path' => $this->path,
            'line' => $this->line,
            default => null,
        };
    }
}
