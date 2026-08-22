<?php

declare(strict_types=1);

namespace Tests\Fixture\Doctest;

use Override;
use PhpAiToolkit\Doctest\Configuration\Configuration;
use PhpAiToolkit\Doctest\TestCase\Legacy\LegacyDoctestRunner;

/**
 * Binds the PHPUnit 9 doctest runner to the fixture project.
 */
final class LegacyFixtureDoctestSuite extends LegacyDoctestRunner
{
    /**
     * Returns the configuration selecting the fixture project sources.
     */
    #[Override]
    public static function configure(): Configuration
    {
        return new Configuration(
            directories: [__DIR__ . '/project/src'],
            excludePatterns: ['*/Nested/*'],
        );
    }
}
