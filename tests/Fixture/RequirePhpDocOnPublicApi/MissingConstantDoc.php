<?php

declare(strict_types=1);

namespace Tests\Fixture\RequirePhpDocOnPublicApi;

/**
 * Class with undocumented public constants.
 */
class MissingConstantDoc
{
    public const UNDOCUMENTED = 'value';

    /**
     * Documented constant.
     */
    public const DOCUMENTED = 'value';
}
