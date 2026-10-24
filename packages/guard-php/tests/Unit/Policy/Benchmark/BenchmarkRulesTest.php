<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Benchmark;

use Guard\Policy\Benchmark\BenchmarkLayout;
use Guard\Policy\Benchmark\BenchmarkRules;
use Guard\Policy\Rule;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Benchmark\BenchmarkRules
 * @uses \Guard\Policy\Benchmark\BenchmarkLayout
 * @uses \Guard\Policy\Rule
 */
#[CoversClass(BenchmarkRules::class)]
#[UsesClass(BenchmarkLayout::class)]
#[UsesClass(Rule::class)]
final class BenchmarkRulesTest extends TestCase
{
    public function testCommandHandlesWordsAndAcronyms(): void
    {
        self::assertSame('bench:parser', (new BenchmarkRules())->command('ParserBench.php'));
        self::assertSame('bench:xml-parser', (new BenchmarkRules())->command('XMLParserBench.php'));
    }

    /**
     * @throws JsonException
     */
    public function testEqualMakesTheRequiredValueRepairable(): void
    {
        $rule = (new BenchmarkRules())->equal('autoload', '/autoload-dev/psr-4/Bench\\', 'bench/');
        self::assertSame('required', $rule->level);
        self::assertSame(['equals' => 'bench/'], $rule->assertions);
        self::assertSame('bench/', $rule->repair);
    }

    /**
     * @throws JsonException
     */
    public function testRulesRepairMappingAndDefaultCommandForOneFile(): void
    {
        $rules = (new BenchmarkRules())->rules(['Bench.php']);
        self::assertCount(2, $rules);
        self::assertSame('/autoload-dev/psr-4/Bench\\', $rules[0]->select);
        self::assertSame('bench/', $rules[0]->repair);
        self::assertSame('/scripts/bench', $rules[1]->select);
        self::assertSame('phpbench run bench --bootstrap=vendor/autoload.php', $rules[1]->repair);
        self::assertTrue($rules[1]->repairable);
    }

    /**
     * @throws JsonException
     */
    public function testRulesCreateEveryIndividualCommandAndSkipInvalidNames(): void
    {
        $rules = (new BenchmarkRules())->rules(['HttpClientBench.php', 'XMLBench.php', 'data.json', 'Bench.php']);
        self::assertCount(4, $rules);
        self::assertSame('phpbench.script.bench:http-client', $rules[2]->id);
        self::assertSame('/scripts/bench:http-client', $rules[2]->select);
        self::assertSame('phpbench run bench/HttpClientBench.php --bootstrap=vendor/autoload.php', $rules[2]->repair);
        self::assertSame('/scripts/bench:xml', $rules[3]->select);
        self::assertSame('phpbench run bench/XMLBench.php --bootstrap=vendor/autoload.php', $rules[3]->repair);
    }

    /**
     * @throws JsonException
     */
    public function testRulesDoNotProposeConflictingCommands(): void
    {
        self::assertCount(2, (new BenchmarkRules())->rules(['XMLBench.php', 'XmlBench.php']));
    }
}
