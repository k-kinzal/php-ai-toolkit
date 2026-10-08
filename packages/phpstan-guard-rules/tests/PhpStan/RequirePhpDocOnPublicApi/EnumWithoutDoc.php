<?php

declare(strict_types=1);

namespace Tests\PhpStan\RequirePhpDocOnPublicApi;

enum EnumWithoutDoc: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
