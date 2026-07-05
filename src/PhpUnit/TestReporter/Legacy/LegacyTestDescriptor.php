<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter\Legacy;

/**
 * Normalized PHPUnit 9 test identity and source location.
 *
 * @property-read string $id fully qualified test identifier
 * @property-read string $name display name
 * @property-read string $file source file path
 * @property-read int $line source line
 */
final class LegacyTestDescriptor
{
    /**
     * Creates a legacy PHPUnit test descriptor.
     *
     * @param string $id fully qualified test identifier
     * @param string $name display name
     * @param string $file source file path
     * @param int $line source line
     */
    public function __construct(
        /** @readonly */
        private string $id,
        /** @readonly */
        private string $name,
        /** @readonly */
        private string $file,
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
        return get_object_vars($this)[$name] ?? null;
    }
}
