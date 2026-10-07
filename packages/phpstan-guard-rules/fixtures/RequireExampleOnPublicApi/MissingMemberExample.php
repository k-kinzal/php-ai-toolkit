<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireExampleOnPublicApi;

/**
 * Holds members that declare themselves public API.
 */
final class MissingMemberExample
{
    /**
     * A public constant without an example.
     *
     * @visibility public
     */
    public const VERSION = '1.0';

    /**
     * A public property without an example.
     *
     * @visibility public
     */
    public string $name = '';

    /**
     * A public method without an example.
     *
     * @visibility public
     */
    public function run(): string
    {
        return $this->name;
    }
}
