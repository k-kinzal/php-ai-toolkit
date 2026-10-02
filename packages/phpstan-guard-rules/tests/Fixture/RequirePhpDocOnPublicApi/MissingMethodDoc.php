<?php

declare(strict_types=1);

namespace Tests\Fixture\RequirePhpDocOnPublicApi;

/**
 * Class with undocumented public methods.
 */
class MissingMethodDoc
{
    public function undocumented(): void
    {
    }

    public function __toString(): string
    {
        return '';
    }

    /**
     * This method is documented.
     */
    public function documented(): void
    {
    }
}
