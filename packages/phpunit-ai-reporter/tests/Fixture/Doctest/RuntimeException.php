<?php

declare(strict_types=1);

namespace Tests\Fixture\Doctest;

use Exception;

/**
 * An exception whose short name collides with a global class it is unrelated to.
 *
 * A docblock that documents "throws RuntimeException" names the global class,
 * because that is what the name resolves to. This one only spells the same last
 * segment, so a matcher that fell back to comparing short names would accept it
 * for that documentation and let a wrong exception pass.
 */
final class RuntimeException extends Exception
{
}
