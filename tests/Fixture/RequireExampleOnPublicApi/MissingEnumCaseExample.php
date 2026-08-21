<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireExampleOnPublicApi;

/**
 * Holds an enum case that declares itself public API.
 */
enum MissingEnumCaseExample: string
{
    /**
     * The only case.
     *
     * @visibility public
     */
    case Only = 'only';
}
