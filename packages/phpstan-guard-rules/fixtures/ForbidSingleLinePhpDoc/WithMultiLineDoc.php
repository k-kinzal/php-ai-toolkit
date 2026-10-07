<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbidSingleLinePhpDoc;

/**
 * Multi-line class doc.
 */
class WithMultiLineDoc
{
    /**
     * Multi-line constant doc.
     */
    public const STATUS = 'active';

    /**
     * Multi-line property doc.
     */
    public string $name = '';

    /**
     * Multi-line method doc.
     */
    public function doSomething(): void
    {
    }
}
