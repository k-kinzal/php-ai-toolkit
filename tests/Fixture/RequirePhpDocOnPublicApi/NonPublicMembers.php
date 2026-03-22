<?php

declare(strict_types=1);

namespace Tests\Fixture\RequirePhpDocOnPublicApi;

/**
 * Class with only non-public members.
 */
class NonPublicMembers
{
    private const SECRET = 'hidden';

    protected const INHERITED = 'base';

    private string $secret = '';

    protected string $inherited = '';

    private function helper(): void
    {
    }

    protected function hook(): void
    {
    }
}
