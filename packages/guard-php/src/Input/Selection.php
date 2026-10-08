<?php

declare(strict_types=1);

namespace Guard\Input;

/**
 * Declares filesystem inputs independently of formats and policies.
 *
 * @property-read string $mode
 * @property-read list<string> $paths
 * @property-read list<string> $exclude
 * @property-read string $suffix
 * @property-read bool $confined
 * @property-read ?string $forbiddenPath
 * @property-read string $forbiddenMessage
 * @property-read string $rootError
 */
final class Selection
{
    /**
     * Creates the immutable value.
     * @param string $mode
     * @param list<string> $paths
     * @param list<string> $exclude
     * @param string $suffix
     * @param bool $confined
     * @param ?string $forbiddenPath
     * @param string $forbiddenMessage
     * @param string $rootError
     * @throws \Guard\Diagnostic\PolicyException when the selection mode is unsupported
     */
    public function __construct(
        /** @readonly */
        private string $mode,
        /** @readonly */
        private array $paths,
        /** @readonly */
        private array $exclude = [],
        /** @readonly */
        private string $suffix = '',
        /** @readonly */
        private bool $confined = false,
        /** @readonly */
        private string $rootError = 'Selected root "{path}" is not a directory. Select an existing directory.',
        /** @readonly */
        private ?string $forbiddenPath = null,
        /** @readonly */
        private string $forbiddenMessage = '',
    ) {
        if (!in_array($mode, ['files', 'patterns', 'descendants', 'directories'], true)) {
            throw new \Guard\Diagnostic\PolicyException('Selection mode "' . $mode . '" is unsupported. Use files, patterns, descendants or directories.');
        }
    }

    /**
     * Returns a declared property.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'mode' => $this->mode,
            'paths' => $this->paths,
            'exclude' => $this->exclude,
            'suffix' => $this->suffix,
            'confined' => $this->confined,
            'rootError' => $this->rootError,
            'forbiddenPath' => $this->forbiddenPath,
            'forbiddenMessage' => $this->forbiddenMessage,
            default => null,
        };
    }
}
