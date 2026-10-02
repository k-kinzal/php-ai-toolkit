<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Policy;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Policy\Rule
 */
#[CoversClass(\Toolkit\Guard\Policy\Rule::class)]
final class RuleTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testPreservesRepairScalarTypes(): void
    {
        $rule = new \Toolkit\Guard\Policy\Rule('switch', 'a.json', 'json', '/enabled', 'required', ['equals' => false], false, true);
        self::assertFalse($rule->repair);
        self::assertSame(['equals' => false], $rule->assertions);
        self::assertTrue($rule->repairable);
    }

}
