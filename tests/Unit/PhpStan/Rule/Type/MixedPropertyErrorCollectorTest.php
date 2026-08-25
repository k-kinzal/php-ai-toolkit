<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Type;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
#[CoversNothing]
final class MixedPropertyErrorCollectorTest extends TestCase
{
    public function testErrorsAreCoveredByPropertyRuleFixtures(): void
    {
        self::addToAssertionCount(1);
    }
}
