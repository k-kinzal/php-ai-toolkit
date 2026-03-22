<?php

declare(strict_types=1);

namespace Tests\Fixture\RequirePhpDocOnPublicApi;

enum EnumWithoutDoc: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
