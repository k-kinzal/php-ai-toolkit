<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Guard\Config\Configuration;
use Guard\Config\RuleReader;
use Guard\Config\Schema;
use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Input\DirectoryListing;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\FileSet;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Selection;
use Guard\Input\StructuredFile;
use Guard\Policy\Context;
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use Guard\Policy\PolicyBinding;
use Guard\Policy\Rule;
use Guard\Structure\Document\Constraint;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\RuleReader
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Schema
 * @uses \Guard\Structure\Document\Selection
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Structure\Document\Constraint
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(RuleReader::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(Schema::class)]
#[UsesClass(\Guard\Structure\Document\Selection::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(Constraint::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Rule::class)]
#[UsesClass(Finding::class)]
final class RuleReaderTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testRuleRejectsRepairOutsideAllowedValues(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('repair must satisfy');
        (new RuleReader())->rule(['id' => 'mode', 'file' => 'x.json', 'select' => '/mode', 'assert' => ['one_of' => ['A', 'B']], 'repair' => 'C']);
    }

    /**
     * @throws JsonException
     */
    public function testReadRejectsDuplicateIds(): void
    {
        $entry = ['id' => 'mode', 'file' => 'x.json', 'select' => '/mode', 'assert' => ['equals' => 'A']];
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Duplicate');
        (new RuleReader())->read([$entry, $entry]);
    }

    /**
     * @throws JsonException
     */
    public function testMakesExactRulesRepairable(): void
    {
        $rule = (new RuleReader())->rule(['id' => 'mode', 'file' => 'x.json', 'select' => '/mode', 'level' => 'recommended', 'assert' => ['equals' => 'A']]);
        self::assertSame('recommended', $rule->level);
        self::assertSame('A', $rule->repair);
        self::assertTrue($rule->repairable);
    }

    public function testAssertionsRejectsContradictoryBounds(): void
    {
        $this->expectException(PolicyException::class);
        (new RuleReader())->assertions(['min' => 3, 'max' => 1], 'workers');
    }

    public function testFormatRejectsAnUnknownDocument(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('unsupported format');
        (new RuleReader())->format('csv', 'mode');
    }

    public function testLevelRejectsACustomSeverity(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('level');
        (new RuleReader())->level('strict', 'mode');
    }

    public function testRepairableAllowsPhpWithAConfiguredValue(): void
    {
        $reader = new RuleReader();
        self::assertTrue($reader->repairable('php', ['assert' => ['equals' => true]], ['equals' => true]));
        self::assertTrue($reader->repairable('json', [], ['equals' => true]));
    }

    public function testRepairValueCoercesNumericXmlBounds(): void
    {
        self::assertSame(1.0, (new RuleReader())->repairValue('xml', ['min' => 1], '1'));
        self::assertSame('1', (new RuleReader())->repairValue('xml', ['equals' => '1'], '1'));
    }

    public function testBoundsRejectsANonNumericLimit(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('must be a number');
        (new RuleReader())->bounds(['min' => '1'], 'workers');
    }

    public function testChoicesRejectsAnEmptyList(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('one_of');
        (new RuleReader())->choices(['one_of' => []], 'mode');
    }

    public function testTextsRejectsAnEmptyNeedle(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('contains_any');
        (new RuleReader())->texts(['contains_any' => ['']], 'includes');
    }

    public function testFlagsRejectsAbsentCombinedWithAnotherAssertion(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('absent');
        (new RuleReader())->flags(['absent' => true, 'present' => true], 'group');
    }

    /**
     * @throws JsonException
     */
    public function testRuleMakesPhpAndJson5RepairsAvailable(): void
    {
        $reader = new RuleReader();
        $php = $reader->rule(['id' => 'risky', 'file' => '.php-cs-fixer.dist.php', 'format' => 'php', 'select' => '/riskyAllowed', 'assert' => ['equals' => true]]);
        $json5 = $reader->rule(['id' => 'msi', 'file' => 'infection.json5', 'format' => 'json5', 'select' => '/minMsi', 'assert' => ['equals' => 80]]);
        self::assertTrue($php->repairable);
        self::assertTrue($json5->repairable);
    }
    /**
     * @throws JsonException
     */
    public function testRuleRejectsAWhitespaceOnlyMessage(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('workers.message must describe the problem and how to fix it.');
        (new RuleReader())->rule(['id' => 'workers', 'file' => 'app.json', 'select' => '/workers', 'assert' => ['equals' => 2], 'message' => '  ']);
    }
}
