<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Config;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Config\RuleReader
 * @uses \Toolkit\Guard\Config\Schema
 * @uses \Toolkit\Guard\Document\Selection
 * @uses \Toolkit\Guard\Policy\Constraint
 * @uses \Toolkit\Guard\Policy\PolicyException
 * @uses \Toolkit\Guard\Policy\Rule
 */
#[CoversClass(\Toolkit\Guard\Config\RuleReader::class)]
#[UsesClass(\Toolkit\Guard\Config\Schema::class)]
#[UsesClass(\Toolkit\Guard\Document\Selection::class)]
#[UsesClass(\Toolkit\Guard\Policy\Constraint::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
#[UsesClass(\Toolkit\Guard\Policy\Rule::class)]
final class RuleReaderTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testRuleRejectsRepairOutsideAllowedValues(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('repair must satisfy');
        (new \Toolkit\Guard\Config\RuleReader())->rule(['id' => 'mode', 'file' => 'x.json', 'select' => '/mode', 'assert' => ['one_of' => ['A', 'B']], 'repair' => 'C']);
    }

    /**
     * @throws JsonException
     */
    public function testReadRejectsDuplicateIds(): void
    {
        $entry = ['id' => 'mode', 'file' => 'x.json', 'select' => '/mode', 'assert' => ['equals' => 'A']];
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('Duplicate');
        (new \Toolkit\Guard\Config\RuleReader())->read([$entry, $entry]);
    }

    /**
     * @throws JsonException
     */
    public function testMakesExactRulesRepairable(): void
    {
        $rule = (new \Toolkit\Guard\Config\RuleReader())->rule(['id' => 'mode', 'file' => 'x.json', 'select' => '/mode', 'level' => 'recommended', 'assert' => ['equals' => 'A']]);
        self::assertSame('recommended', $rule->level);
        self::assertSame('A', $rule->repair);
        self::assertTrue($rule->repairable);
    }

    public function testAssertionsRejectsContradictoryBounds(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        (new \Toolkit\Guard\Config\RuleReader())->assertions(['min' => 3, 'max' => 1], 'workers');
    }
}
