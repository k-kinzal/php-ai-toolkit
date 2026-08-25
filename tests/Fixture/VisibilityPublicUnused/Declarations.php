<?php

declare(strict_types=1);

namespace Tests\Fixture\VisibilityPublicUnused;

/**
 * Public entry point.
 *
 * @visibility public
 */
final class PublicEntryPoint
{
    /**
     * Public constant.
     *
     * @visibility public
     */
    public const CODE = 1;

    /**
     * Public state.
     *
     * @visibility public
     */
    public string $state = '';

    /**
     * Public operation.
     *
     * @visibility public
     */
    public function run(): void
    {
    }

    /**
     * Internal operation.
     */
    public function internal(): void
    {
    }
}
