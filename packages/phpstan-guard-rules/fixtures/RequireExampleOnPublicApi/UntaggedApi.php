<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireExampleOnPublicApi;

/**
 * Reachable everywhere but never declared public API, so no example is required.
 */
final class UntaggedApi
{
    /**
     * Returns a label.
     */
    public function label(): string
    {
        return 'untagged';
    }
}
