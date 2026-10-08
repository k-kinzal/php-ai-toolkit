<?php

declare(strict_types=1);

namespace Tests\PhpStan\ForbidFileTerm;

final class BackendNeutral
{
    public function query(): string
    {
        return 'generic query';
    }
}
