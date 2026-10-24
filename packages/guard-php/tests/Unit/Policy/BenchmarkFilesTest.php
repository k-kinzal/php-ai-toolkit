<?php

declare(strict_types=1);

namespace Tests\Unit\Policy;

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
use Guard\Policy\Benchmark\BenchmarkLayout;
use Guard\Policy\Benchmark\BenchmarkRules;
use Guard\Policy\BenchmarkFiles;
use Guard\Policy\Context;
use Guard\Policy\Plan;
use Guard\Structure\Php\PhpBenchReference;
use Guard\Structure\Php\Tokens;
use JsonException;
use Nette\Neon\Exception;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\BenchmarkFiles
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Policy\Benchmark\BenchmarkLayout
 * @uses \Guard\Policy\Benchmark\BenchmarkRules
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Structure\Php\PhpBenchReference
 * @uses \Guard\Structure\Php\Tokens
 */
#[CoversClass(BenchmarkFiles::class)]
#[UsesClass(Finding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(Entry::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(BenchmarkLayout::class)]
#[UsesClass(BenchmarkRules::class)]
#[UsesClass(Context::class)]
#[UsesClass(Plan::class)]
#[UsesClass(PhpBenchReference::class)]
#[UsesClass(Tokens::class)]
final class BenchmarkFilesTest extends TestCase
{
    public function testInputsSelectOnlyDirectPhpFilesAndDirectoryMetadata(): void
    {
        $inputs = (new BenchmarkFiles())->inputs(new Context('/project', '/project/guard.yaml', false));
        self::assertSame(['bench/*.php'], $inputs['php']->selection->paths);
        self::assertSame('php.tokens', $inputs['php']->structure);
        self::assertSame(['bench'], $inputs['directory']->selection->paths);
    }

    public function testDirectoryRejectsMissingRootsAndLimitsTraversal(): void
    {
        $selection = (new BenchmarkFiles())->directory()->selection;
        self::assertSame('directories', $selection->mode);
        self::assertSame(['bench/*/*'], $selection->exclude);
        self::assertStringContainsString('Create bench/Bench.php', $selection->rootError);
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function testEvaluateReportsCustomBenchmarksWithoutProposingCodeChanges(): void
    {
        $record = new FileRecord('/project/bench/Bench.php', 'bench/Bench.php', new Entry(false, true, false, '/project/bench/Bench.php'));
        $tokens = new Tokens(array_values(PhpToken::tokenize('<?php class Bench {}')));
        $inputs = new InputSet([
            'directory' => new FileSet([], ['bench' => new DirectoryListing('bench', ['Bench.php'], [])], null),
            'php' => new FileSet(['bench/Bench.php' => new StructuredFile($record, true, $tokens, null)], [], null),
        ]);
        $plan = (new BenchmarkFiles())->evaluate($inputs, new Context('/project', '/project/guard.yaml', true));
        self::assertCount(1, $plan->findings);
        self::assertSame('phpbench.namespace', $plan->findings[0]->rule);
        self::assertSame('bench/Bench.php', $plan->findings[0]->path);
        self::assertSame([], $plan->changes);
        self::assertFalse($plan->findings[0]->fixable);
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function testEvaluateRejectsUnreadableFiles(): void
    {
        $record = new FileRecord('/project/bench/Bench.php', 'bench/Bench.php', new Entry(false, true, false, '/project/bench/Bench.php'));
        $inputs = new InputSet([
            'directory' => new FileSet([], [], null),
            'php' => new FileSet(['bench/Bench.php' => new StructuredFile($record, false, null, null)], [], null),
        ]);
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Cannot inspect PHPBench references in bench/Bench.php');
        (new BenchmarkFiles())->evaluate($inputs, new Context('/project', '/project/guard.yaml', false));
    }
}
