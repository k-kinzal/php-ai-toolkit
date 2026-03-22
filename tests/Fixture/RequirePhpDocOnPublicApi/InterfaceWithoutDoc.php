<?php

declare(strict_types=1);

namespace Tests\Fixture\RequirePhpDocOnPublicApi;

interface InterfaceWithoutDoc
{
    public const STATUS = 'active';

    public function doSomething(): void;
}
