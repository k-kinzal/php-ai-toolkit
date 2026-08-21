<?php

declare(strict_types=1);

namespace Tests\Fixture\Doctest;

use Override;
use PhpAiToolkit\PhpUnit\Doctest\Legacy\LegacyDoctestTestCase;

/**
 * Binds the PHPUnit 9 doctest test case to the fixture project.
 */
final class LegacyFixtureDoctestTestCase extends LegacyDoctestTestCase
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
