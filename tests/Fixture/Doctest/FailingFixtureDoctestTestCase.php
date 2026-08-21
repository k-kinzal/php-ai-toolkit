<?php

declare(strict_types=1);

namespace Tests\Fixture\Doctest;

use Override;
use PhpAiToolkit\PhpUnit\Doctest\DoctestTestCase;

/**
 * Binds the doctest test case to the fixture project whose example is wrong.
 */
final class FailingFixtureDoctestTestCase extends DoctestTestCase
{
    /**
     * Returns the failing fixture project configuration.
     */
    #[Override]
    public static function doctestConfigPath(): string
    {
        return __DIR__ . '/failing/doctest.yaml';
    }
}
