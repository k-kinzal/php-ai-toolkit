<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

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
#[CoversClass(\Guard\Config\RuleReader::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Config\Schema::class)]
#[UsesClass(\Guard\Structure\Document\Selection::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(\Guard\Structure\Document\Constraint::class)]
#[UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
final class RuleReaderTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testRuleRejectsRepairOutsideAllowedValues(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('repair must satisfy');
        (new \Guard\Config\RuleReader())->rule(['id' => 'mode', 'file' => 'x.json', 'select' => '/mode', 'assert' => ['one_of' => ['A', 'B']], 'repair' => 'C']);
    }

    /**
     * @throws JsonException
     */
    public function testReadRejectsDuplicateIds(): void
    {
        $entry = ['id' => 'mode', 'file' => 'x.json', 'select' => '/mode', 'assert' => ['equals' => 'A']];
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
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
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        (new \Guard\Config\RuleReader())->assertions(['min' => 3, 'max' => 1], 'workers');
    }

    public function testFormatRejectsAnUnknownDocument(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('unsupported format');
        (new \Guard\Config\RuleReader())->format('csv', 'mode');
    }

    public function testLevelRejectsACustomSeverity(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
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
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('must be a number');
        (new \Guard\Config\RuleReader())->bounds(['min' => '1'], 'workers');
    }

    public function testChoicesRejectsAnEmptyList(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('one_of');
        (new \Guard\Config\RuleReader())->choices(['one_of' => []], 'mode');
    }

    public function testTextsRejectsAnEmptyNeedle(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('contains_any');
        (new \Guard\Config\RuleReader())->texts(['contains_any' => ['']], 'includes');
    }

    public function testFlagsRejectsAbsentCombinedWithAnotherAssertion(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
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
    /**
     * @throws JsonException
     */
    public function testRuleRejectsAWhitespaceOnlyMessage(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('workers.message must describe the problem and how to fix it.');
        (new \Guard\Config\RuleReader())->rule(['id' => 'workers', 'file' => 'app.json', 'select' => '/workers', 'assert' => ['equals' => 2], 'message' => '  ']);
    }
}
