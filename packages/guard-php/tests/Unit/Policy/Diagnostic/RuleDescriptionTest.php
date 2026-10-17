<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Diagnostic;

use Guard\Policy\Diagnostic\RuleDescription;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Diagnostic\RuleDescription
 */
#[CoversClass(RuleDescription::class)]

final class RuleDescriptionTest extends TestCase
{
    public function testToArrayPreservesTypedConstraintsAndRepairCapability(): void
    {
        $rule = new RuleDescription('limit', 'a.json', 'required', true, 'Bound work.', ['max' => 2]);
        self::assertSame(['id' => 'limit', 'target' => 'a.json', 'level' => 'required', 'fixable' => true, 'message' => 'Bound work.', 'details' => ['max' => 2]], $rule->toArray());
    }

}
