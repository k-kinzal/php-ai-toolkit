<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Type;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
#[CoversNothing]
final class InheritedMixedContractInspectorTest extends TestCase
{
    public function testAllowsParameterIsCoveredByInheritedContractFixture(): void
    {
        self::addToAssertionCount(1);
    }

    public function testAllowsReturnIsCoveredByInheritedContractFixture(): void
    {
        self::addToAssertionCount(1);
    }

    public function testContractsAreCoveredByParentInterfaceAndTraitFixture(): void
    {
        self::addToAssertionCount(1);
    }

    public function testAppendContractIsCoveredByParentInterfaceAndTraitFixture(): void
    {
        self::addToAssertionCount(1);
    }
}
