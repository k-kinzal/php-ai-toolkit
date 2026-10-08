<?php

declare(strict_types=1);

namespace Tests\PhpStan\NoNonPublicMethod;

use Override;

abstract class ProtectedBase
{
    protected function templateStep(): string
    {
        return 'base';
    }
}

class OverrideProtectedMethod extends ProtectedBase
{
    #[Override]
    protected function templateStep(): string
    {
        return 'override';
    }
}
