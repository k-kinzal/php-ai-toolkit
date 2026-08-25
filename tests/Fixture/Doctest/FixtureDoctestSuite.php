<?php

declare(strict_types=1);

namespace Tests\Fixture\Doctest;

use Override;
use Toolkit\Doctest\Configuration\Configuration;
use Toolkit\Doctest\TestCase\DoctestRunner;

/**
 * Binds the doctest runner to the fixture project.
 */
final class FixtureDoctestSuite extends DoctestRunner
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
