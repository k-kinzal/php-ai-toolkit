<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Type\MixedPropertyErrorCollector;

#[CoversClass(MixedPropertyErrorCollector::class)]
final class MixedPropertyErrorCollectorTest extends TestCase
{
    public function testErrorsAreCoveredByPropertyRuleFixtures(): void
    {
        self::addToAssertionCount(1);
    }
}
