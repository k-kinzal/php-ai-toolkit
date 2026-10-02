<?php

declare(strict_types=1);

namespace Tests\Fixture\RequirePhpDocOnPublicApi;

/**
 * Class with undocumented public properties.
 */
class MissingPropertyDoc
{
    public string $undocumented = '';

    /**
     * Documented property.
     */
    public string $documented = '';
}
