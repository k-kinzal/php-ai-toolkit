<?php

declare(strict_types=1);

namespace Tests\PhpStan\ForbidClassLikeNameSuffix;

class UserService
{
}

interface PaymentGateway
{
}

trait RecordsEvents
{
}

enum Status
{
    case Active;
}
