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
     * Returns the fixture project root.
     */
    #[Override]
    public static function doctestRoot(): string
    {
        return __DIR__ . '/project';
    }

    /**
     * Leaves the nested directory of the fixture project unscanned.
     *
     * @return list<string>
     */
    #[Override]
    public static function doctestExcludes(): array
    {
        return ['src/Nested/*'];
    }
}
