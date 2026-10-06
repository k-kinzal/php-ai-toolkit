<?php

declare(strict_types=1);

namespace Tests\Unit\Policy;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Rule
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
final class RuleTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testPreservesRepairScalarTypes(): void
    {
        $rule = new \Guard\Policy\Rule('switch', 'a.json', 'json', '/enabled', 'required', ['equals' => false], false, true);
        self::assertFalse($rule->repair);
        self::assertSame(['equals' => false], $rule->assertions);
        self::assertTrue($rule->repairable);
    }

}
