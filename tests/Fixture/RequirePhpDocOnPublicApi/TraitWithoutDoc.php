<?php

declare(strict_types=1);

namespace Tests\Fixture\RequirePhpDocOnPublicApi;

trait TraitWithoutDoc
{
    public string $name = '';

    public function doSomething(): void
    {
    }
}
