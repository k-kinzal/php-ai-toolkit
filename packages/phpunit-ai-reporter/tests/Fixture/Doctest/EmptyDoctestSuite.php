<?php

declare(strict_types=1);

namespace Tests\Fixture\Doctest;

use Override;
use Toolkit\Doctest\Configuration\Configuration;
use Toolkit\Doctest\TestCase\DoctestRunner;

/**
 * Binds the doctest runner to an empty source set.
 */
final class EmptyDoctestSuite extends DoctestRunner
{
    /**
     * Returns a configuration that intentionally discovers no examples.
     */
    #[Override]
    public static function configure(): Configuration
    {
        return new Configuration(directories: []);
    }
}
