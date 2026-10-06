<?php

declare(strict_types=1);

namespace Guard\Config\Reader;

use Guard\Collect\Scope;
use Guard\Config\Schema;
use Guard\Policy\PolicyException;

/**
 * Reads the collector boundary independently of extension-specific file expectations.
 */
final class ScopeConfigReader
{
    /**
     * @throws PolicyException when the scope or its paths are malformed
     */
    public function read(mixed $value): Scope
    {
        $data = (new Schema())->mapping($value, ['include', 'exclude'], 'collect');
        $data += ['include' => ['**'], 'exclude' => []];
        return new Scope($this->paths($data['include'], 'collect.include'), $this->paths($data['exclude'], 'collect.exclude'));
    }

    /**
     * Requires a project-relative boundary, without ambiguous parent traversal.
     * @return list<string>
     * @throws PolicyException when a scope path escapes the project
     */
    public function paths(mixed $value, string $context): array
    {
        $paths = (new Schema())->strings($value, $context);
        foreach ($paths as $path) {
            if (str_starts_with($path, '/') || str_contains($path, '\\') || preg_match('/^[A-Za-z]:/', $path) === 1 || in_array('..', explode('/', $path), true)) {
                throw new PolicyException($context . ': "' . $path . '" must be relative to guard.yaml, use forward slashes, and contain no parent traversal.');
            }
        }
        return $paths;
    }
}
