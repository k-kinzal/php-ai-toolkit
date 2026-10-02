<?php

declare(strict_types=1);

namespace Tests\Fixture\NoNonPublicMethod;

final class WithPrivateConstructor
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }
}
