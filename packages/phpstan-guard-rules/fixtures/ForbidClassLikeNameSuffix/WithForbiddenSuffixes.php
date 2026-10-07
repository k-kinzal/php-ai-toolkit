<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbidClassLikeNameSuffix;

class UserHelper
{
}

interface PaymentManager
{
}

trait RequestData
{
}

enum StatusHelper
{
    case Active;
}

final class AnonymousClassFactory
{
    public function create(): object
    {
        return new class () {
        };
    }
}
