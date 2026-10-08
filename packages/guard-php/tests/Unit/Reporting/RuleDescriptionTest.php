<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

/**
 * @covers \Guard\Reporting\RuleDescription
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Guard\Reporting\RuleDescription::class)]

final class RuleDescriptionTest extends \PHPUnit\Framework\TestCase
{
    public function testToArrayPreservesTypedConstraintsAndRepairCapability(): void
    {
        $rule = new \Guard\Reporting\RuleDescription('limit', 'a.json', 'required', true, 'Bound work.', ['max' => 2]);
        self::assertSame(['id' => 'limit', 'target' => 'a.json', 'level' => 'required', 'fixable' => true, 'message' => 'Bound work.', 'details' => ['max' => 2]], $rule->toArray());
    }

}
