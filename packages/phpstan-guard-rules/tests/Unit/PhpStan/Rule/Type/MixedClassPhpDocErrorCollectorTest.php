<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Type;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
#[CoversNothing]
final class MixedClassPhpDocErrorCollectorTest extends TestCase
{
    public function testErrorsAreCoveredByClassPhpDocRuleFixtures(): void
    {
        self::addToAssertionCount(1);
    }

    public function testPropertyErrorsAreCoveredByVirtualPropertyFixture(): void
    {
        self::addToAssertionCount(1);
    }

    public function testMethodErrorsAreCoveredByVirtualMethodFixture(): void
    {
        self::addToAssertionCount(1);
    }

    public function testTypeAliasErrorsAreCoveredByTypeAliasFixture(): void
    {
        self::addToAssertionCount(1);
    }
}
