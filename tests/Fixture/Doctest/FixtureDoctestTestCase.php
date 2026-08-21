<?php

declare(strict_types=1);

namespace Tests\Fixture\Doctest;

use Override;
use PhpAiToolkit\PhpUnit\Doctest\DoctestTestCase;

/**
 * Binds the doctest test case to the fixture project.
 */
final class FixtureDoctestTestCase extends DoctestTestCase
{
    /**
     * Returns the fixture project configuration.
     */
    #[Override]
    public static function doctestConfigPath(): string
    {
        return __DIR__ . '/project/doctest.yaml';
    }
}
