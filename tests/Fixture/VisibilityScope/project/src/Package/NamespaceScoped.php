<?php

declare(strict_types=1);

namespace Tests\Fixture\VisibilityScope\Package;

/**
 * @visibility namespace
 */
final class NamespaceScoped
{
    public const LIMIT = 3;

    public static int $shared = 0;

    public int $counter = 0;

    public function run(): int
    {
        return self::LIMIT;
    }

    public static function make(): self
    {
        return new self();
    }
}
