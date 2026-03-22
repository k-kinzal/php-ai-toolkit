<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbiddenMagicMethodCall;

class Base
{
    public function __construct()
    {
    }
}

class ParentCall extends Base
{
    public function __construct()
    {
        parent::__construct();
    }
}
