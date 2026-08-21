<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Execution;

use function class_exists;
use function function_exists;
use function interface_exists;
use function is_file;

use PhpAiToolkit\Doctest\Analysis\Target;
use PhpAiToolkit\Doctest\DoctestException;

use function realpath;
use function sprintf;
use function trait_exists;

/**
 * Makes the code an example documents available before the example runs.
 *
 * A declaration an autoloader can already resolve is never included by hand,
 * so a project whose sources are autoloaded never risks redeclaring them; only
 * files that define nothing loadable, such as plain function files, are read.
 */
final class SourceLoader
{
    /** @var array<string, true> */
    private array $loaded = [];

    /**
     * Loads the bootstrap file, once per run, and the file the target lives in.
     *
     * @throws DoctestException when a configured file does not exist
     */
    public function load(Target $target, ?string $bootstrap): void
    {
        if ($bootstrap !== null) {
            $this->loadFile($bootstrap);
        }

        if (!$this->isDefined($target)) {
            $this->loadFile($target->path);
        }
    }

    /**
     * Includes one file unless it has already been included.
     *
     * @throws DoctestException when the file does not exist
     */
    public function loadFile(string $path): void
    {
        $resolved = is_file($path) ? realpath($path) : false;
        if ($resolved === false) {
            throw new DoctestException(sprintf('Could not load file for doctest execution: %s', $path));
        }

        if (isset($this->loaded[$resolved])) {
            return;
        }

        $this->loaded[$resolved] = true;

        require_once $resolved;
    }

    /**
     * Reports whether the symbol the target documents can already be resolved.
     */
    public function isDefined(Target $target): bool
    {
        if ($target->kind === Target::FILE) {
            return false;
        }

        $prefix = $target->namespace === '' ? '' : $target->namespace . '\\';
        if ($target->kind === Target::FUNCTION) {
            return function_exists($prefix . $target->name);
        }

        $name = $prefix . ($target->className ?? $target->name);

        return class_exists($name) || interface_exists($name) || trait_exists($name);
    }
}
