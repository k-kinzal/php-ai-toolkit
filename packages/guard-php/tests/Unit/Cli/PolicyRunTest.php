<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use Guard\Cli\PolicyRun;
use Guard\Execution\FileChange;
use Guard\Execution\Plan;
use Guard\Extension\Registry;
use Guard\Policy\PolicyException;
use Guard\Reporting\Finding;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\Support\CallbackPolicy;

/**
 * @covers \Guard\Cli\PolicyRun
 * @uses \Guard\Collect\Filesystem\Discovery
 * @uses \Guard\Collect\Filesystem\Snapshot
 * @uses \Guard\Collect\Filesystem\WalkQueue
 * @uses \Guard\Config\Reader\ExtensionConfigReader
 * @uses \Guard\Config\RuleReader
 * @uses \Guard\Extension\ExtensionLoader
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\FieldConstraints
 * @uses \Guard\Collect\Collector
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\ConfigurationLoader
 * @uses \Guard\Config\DocumentMerger
 * @uses \Guard\Config\ImportResolver
 * @uses \Guard\Config\Schema
 * @uses \Guard\Document\DataDocument
 * @uses \Guard\Execution\AtomicWriter
 * @uses \Guard\Execution\ChangeSet
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Pipeline
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\Registry
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 * @uses \Guard\Reporting\Reporter
 */
#[CoversClass(PolicyRun::class)]
#[UsesClass(\Guard\Collect\Filesystem\Discovery::class)]
#[UsesClass(\Guard\Collect\Filesystem\Snapshot::class)]
#[UsesClass(\Guard\Collect\Filesystem\WalkQueue::class)]
#[UsesClass(\Guard\Config\Reader\ExtensionConfigReader::class)]
#[UsesClass(\Guard\Config\RuleReader::class)]
#[UsesClass(\Guard\Extension\ExtensionLoader::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(\Guard\Policy\FieldConstraints::class)]
#[UsesClass(\Guard\Collect\Collector::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Config\ConfigurationLoader::class)]
#[UsesClass(\Guard\Config\DocumentMerger::class)]
#[UsesClass(\Guard\Config\ImportResolver::class)]
#[UsesClass(\Guard\Config\Schema::class)]
#[UsesClass(\Guard\Document\DataDocument::class)]
#[UsesClass(\Guard\Execution\AtomicWriter::class)]
#[UsesClass(\Guard\Execution\ChangeSet::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(\Guard\Execution\Pipeline::class)]
#[UsesClass(Plan::class)]
#[UsesClass(Registry::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
#[UsesClass(\Guard\Reporting\Reporter::class)]
final class PolicyRunTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testRunReportsAPolicyWithoutFindingsAsPassed(): void
    {
        $root = sys_get_temp_dir() . '/guard-policy-run-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\n");
        $output = new BufferedOutput();

        self::assertSame(0, (new PolicyRun(new Registry()))->run($root . '/guard.yaml', 'json', false, false, $output));
        self::assertStringContainsString('"success": true', $output->fetch());
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testRunWritesTheRepairsOfARepairingRun(): void
    {
        $root = sys_get_temp_dir() . '/guard-policy-run-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\n");
        file_put_contents($root . '/a.txt', 'old');
        $registry = new Registry();
        $registry->addPolicy('rewrite', new CallbackPolicy([], static fn (): Plan => new Plan([], [new FileChange($root . '/a.txt', 'old', 'new')])));
        $output = new BufferedOutput();

        self::assertSame(0, (new PolicyRun($registry))->run($root . '/guard.yaml', 'text', true, false, $output));
        self::assertSame('new', file_get_contents($root . '/a.txt'));
        self::assertStringContainsString('changed: ' . $root . '/a.txt', $output->fetch());
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testRunWritesNothingOnADryRun(): void
    {
        $root = sys_get_temp_dir() . '/guard-policy-run-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\n");
        file_put_contents($root . '/a.txt', 'old');
        $registry = new Registry();
        $registry->addPolicy('rewrite', new CallbackPolicy([], static fn (): Plan => new Plan([], [new FileChange($root . '/a.txt', 'old', 'new')])));
        $output = new BufferedOutput();

        self::assertSame(0, (new PolicyRun($registry))->run($root . '/guard.yaml', 'text', true, true, $output));
        self::assertSame('old', file_get_contents($root . '/a.txt'));
        self::assertStringContainsString('would change: ' . $root . '/a.txt', $output->fetch());
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testRunWritesNothingWhileARequiredViolationBlocksTheRepairs(): void
    {
        $root = sys_get_temp_dir() . '/guard-policy-run-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\n");
        file_put_contents($root . '/a.txt', 'old');
        $finding = new Finding('a.txt', 'example.blocked', 'required', 'Fix a.txt by hand.');
        $registry = new Registry();
        $registry->addPolicy('rewrite', new CallbackPolicy([], static fn (): Plan => new Plan([$finding], [new FileChange($root . '/a.txt', 'old', 'new')], [$finding])));
        $output = new BufferedOutput();

        self::assertSame(1, (new PolicyRun($registry))->run($root . '/guard.yaml', 'text', true, false, $output));
        self::assertSame('old', file_get_contents($root . '/a.txt'));
        self::assertStringContainsString('blocked: ' . $root . '/a.txt', $output->fetch());
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testRunWritesTheReportRaw(): void
    {
        $root = sys_get_temp_dir() . '/guard-policy-run-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\n");
        $finding = new Finding('a.php', 'example.tag', 'recommended', 'Start the file with <?php.');
        $registry = new Registry();
        $registry->addPolicy('tag', new CallbackPolicy([], static fn (): Plan => new Plan([$finding], [])));
        $output = new BufferedOutput();

        self::assertSame(0, (new PolicyRun($registry))->run($root . '/guard.yaml', 'text', false, false, $output));
        self::assertStringContainsString('Start the file with <?php.', $output->fetch());
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testRunRejectsAMissingPolicyFile(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Guard configuration not found: /no/such/guard.yaml. Run guard init first.');

        (new PolicyRun(new Registry()))->run('/no/such/guard.yaml', 'text', false, false, new BufferedOutput());
    }
}
