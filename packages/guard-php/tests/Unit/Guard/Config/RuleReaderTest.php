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

    public function testFormatRejectsAnUnknownDocument(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('unsupported format');
        (new \Toolkit\Guard\Config\RuleReader())->format('csv', 'mode');
    }

    public function testLevelRejectsACustomSeverity(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('level');
        (new \Toolkit\Guard\Config\RuleReader())->level('strict', 'mode');
    }

    public function testRepairableLeavesPhpCheckOnly(): void
    {
        $reader = new \Toolkit\Guard\Config\RuleReader();
        self::assertFalse($reader->repairable('php', ['assert' => ['equals' => true]], ['equals' => true]));
        self::assertTrue($reader->repairable('json', [], ['equals' => true]));
    }

    public function testRepairValueCoercesNumericXmlBounds(): void
    {
        self::assertSame(1.0, (new \Toolkit\Guard\Config\RuleReader())->repairValue('xml', ['min' => 1], '1'));
        self::assertSame('1', (new \Toolkit\Guard\Config\RuleReader())->repairValue('xml', ['equals' => '1'], '1'));
    }

    public function testBoundsRejectsANonNumericLimit(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('must be a number');
        (new \Toolkit\Guard\Config\RuleReader())->bounds(['min' => '1'], 'workers');
    }

    public function testChoicesRejectsAnEmptyList(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('one_of');
        (new \Toolkit\Guard\Config\RuleReader())->choices(['one_of' => []], 'mode');
    }

    public function testTextsRejectsAnEmptyNeedle(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('contains_any');
        (new \Toolkit\Guard\Config\RuleReader())->texts(['contains_any' => ['']], 'includes');
    }

    public function testFlagsRejectsAbsentCombinedWithAnotherAssertion(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('absent');
        (new \Toolkit\Guard\Config\RuleReader())->flags(['absent' => true, 'present' => true], 'group');
    }

    /**
     * @throws JsonException
     */
    public function testRuleLeavesPhpAndJson5CheckOnly(): void
    {
        $reader = new \Toolkit\Guard\Config\RuleReader();
        $php = $reader->rule(['id' => 'risky', 'file' => '.php-cs-fixer.dist.php', 'format' => 'php', 'select' => '/riskyAllowed', 'assert' => ['equals' => true]]);
        $json5 = $reader->rule(['id' => 'msi', 'file' => 'infection.json5', 'format' => 'json5', 'select' => '/minMsi', 'assert' => ['equals' => 80]]);
        self::assertFalse($php->repairable);
        self::assertFalse($json5->repairable);
    }
}
