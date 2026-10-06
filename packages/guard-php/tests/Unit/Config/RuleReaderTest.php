<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\RuleReader
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Schema
 * @uses \Guard\Document\Selection
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\Constraint
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Config\RuleReader::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Config\Schema::class)]
#[UsesClass(\Guard\Document\Selection::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(\Guard\Policy\Constraint::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class RuleReaderTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testRuleRejectsRepairOutsideAllowedValues(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('repair must satisfy');
        (new \Guard\Config\RuleReader())->rule(['id' => 'mode', 'file' => 'x.json', 'select' => '/mode', 'assert' => ['one_of' => ['A', 'B']], 'repair' => 'C']);
    }

    /**
     * @throws JsonException
     */
    public function testReadRejectsDuplicateIds(): void
    {
        $entry = ['id' => 'mode', 'file' => 'x.json', 'select' => '/mode', 'assert' => ['equals' => 'A']];
        $this->expectException(\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('Duplicate');
        (new \Guard\Config\RuleReader())->read([$entry, $entry]);
    }

    /**
     * @throws JsonException
     */
    public function testMakesExactRulesRepairable(): void
    {
        $rule = (new \Guard\Config\RuleReader())->rule(['id' => 'mode', 'file' => 'x.json', 'select' => '/mode', 'level' => 'recommended', 'assert' => ['equals' => 'A']]);
        self::assertSame('recommended', $rule->level);
        self::assertSame('A', $rule->repair);
        self::assertTrue($rule->repairable);
    }

    public function testAssertionsRejectsContradictoryBounds(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        (new \Guard\Config\RuleReader())->assertions(['min' => 3, 'max' => 1], 'workers');
    }

    public function testFormatRejectsAnUnknownDocument(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('unsupported format');
        (new \Guard\Config\RuleReader())->format('csv', 'mode');
    }

    public function testLevelRejectsACustomSeverity(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('level');
        (new \Guard\Config\RuleReader())->level('strict', 'mode');
    }

    public function testRepairableLeavesPhpCheckOnly(): void
    {
        $reader = new \Guard\Config\RuleReader();
        self::assertFalse($reader->repairable('php', ['assert' => ['equals' => true]], ['equals' => true]));
        self::assertTrue($reader->repairable('json', [], ['equals' => true]));
    }

    public function testRepairValueCoercesNumericXmlBounds(): void
    {
        self::assertSame(1.0, (new \Guard\Config\RuleReader())->repairValue('xml', ['min' => 1], '1'));
        self::assertSame('1', (new \Guard\Config\RuleReader())->repairValue('xml', ['equals' => '1'], '1'));
    }

    public function testBoundsRejectsANonNumericLimit(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('must be a number');
        (new \Guard\Config\RuleReader())->bounds(['min' => '1'], 'workers');
    }

    public function testChoicesRejectsAnEmptyList(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('one_of');
        (new \Guard\Config\RuleReader())->choices(['one_of' => []], 'mode');
    }

    public function testTextsRejectsAnEmptyNeedle(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('contains_any');
        (new \Guard\Config\RuleReader())->texts(['contains_any' => ['']], 'includes');
    }

    public function testFlagsRejectsAbsentCombinedWithAnotherAssertion(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('absent');
        (new \Guard\Config\RuleReader())->flags(['absent' => true, 'present' => true], 'group');
    }

    /**
     * @throws JsonException
     */
    public function testRuleLeavesPhpAndJson5CheckOnly(): void
    {
        $reader = new \Guard\Config\RuleReader();
        $php = $reader->rule(['id' => 'risky', 'file' => '.php-cs-fixer.dist.php', 'format' => 'php', 'select' => '/riskyAllowed', 'assert' => ['equals' => true]]);
        $json5 = $reader->rule(['id' => 'msi', 'file' => 'infection.json5', 'format' => 'json5', 'select' => '/minMsi', 'assert' => ['equals' => 80]]);
        self::assertFalse($php->repairable);
        self::assertFalse($json5->repairable);
    }
}
