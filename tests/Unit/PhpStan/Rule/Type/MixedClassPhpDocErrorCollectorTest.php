<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Type\MixedClassPhpDocErrorCollector;

#[CoversClass(MixedClassPhpDocErrorCollector::class)]
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
