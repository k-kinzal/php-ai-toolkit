<?php

declare(strict_types=1);

namespace Tests\Fixture\RequirePhpDocOnPublicApi;

/**
 * A fully documented class.
 */
class FullyDocumented
{
    /**
     * A public constant.
     */
    public const STATUS = 'active';

    /**
     * A public property.
     */
    public string $name = '';

    /**
     * Constructor.
     *
     * @param string $value a value
     */
    public function __construct(
        public readonly string $value = '',
    ) {
    }

    /**
     * A public method.
     */
    public function doSomething(): void
    {
    }
}

$anon = new class () {
    public function noDocNeeded(): void
    {
    }
};
