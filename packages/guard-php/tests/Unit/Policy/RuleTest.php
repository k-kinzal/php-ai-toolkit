<?php

declare(strict_types=1);

namespace Tests\Unit\Policy;

use Guard\Policy\Rule;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \Guard\Policy\Rule
 */
#[CoversClass(Rule::class)]
final class RuleTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testPreservesRepairScalarTypes(): void
    {
        $rule = new Rule('switch', 'a.json', 'json', '/enabled', 'required', ['equals' => false], false, true);
        self::assertFalse($rule->repair);
        self::assertSame(['equals' => false], $rule->assertions);
        self::assertTrue($rule->repairable);
    }


    /**
     * @throws JsonException
     */
    public function testGetPreservesEmptyMappingsInsideAssertions(): void
    {
        $rule = new Rule('empty-map', 'deptrac.yaml', 'yaml', '/deptrac/skip_violations', 'required', ['equals' => (object) []], (object) [], true);
        self::assertInstanceOf(stdClass::class, $rule->assertions['equals']);
        self::assertEquals($rule->repair, $rule->assertions['equals']);
    }
}
