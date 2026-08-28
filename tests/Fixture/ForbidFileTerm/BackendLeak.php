<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbidFileTerm;

/**
 * PostgreSQL behavior accidentally added to an abstract layer.
 */
final class BackendLeak
{
    public const DRIVER = 'MYSQL';

    public function sqliteQuery(): string
    {
        return 'generic query';
    }
}
