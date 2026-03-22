<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbidSingleLinePhpDoc;

/** Single-line class doc. */
class WithSingleLineDoc
{
    /** Single-line constant doc. */
    public const STATUS = 'active';

    /** Single-line property doc. */
    public string $name = '';

    /** Single-line method doc. */
    public function doSomething(): void
    {
    }
}
