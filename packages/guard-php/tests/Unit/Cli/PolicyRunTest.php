<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use Closure;
use Guard\Cli\PolicyRun;
use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Execution\Registry;
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @covers \Guard\Cli\PolicyRun
 * @uses \Guard\Collect\Filesystem\Discovery
 * @uses \Guard\Collect\Filesystem\Snapshot
 * @uses \Guard\Collect\Filesystem\WalkQueue
 * @uses \Guard\Config\Reader\ExtensionConfigReader
 * @uses \Guard\Config\RuleReader
 * @uses \Guard\Config\ComponentLoader
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Policy\FieldConstraints
 * @uses \Guard\Collect\Collector
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\ConfigurationLoader
 * @uses \Guard\Config\DocumentMerger
 * @uses \Guard\Config\ImportResolver
 * @uses \Guard\Config\Schema
 * @uses \Guard\Structure\Document\DataDocument
 * @uses \Guard\Repair\AtomicWriter
 * @uses \Guard\Repair\ChangeSet
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Execution\Pipeline
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Execution\Registry
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Reporting\ChangeDiff
 * @uses \Guard\Policy\Diagnostic\RuleMessages
 * @uses \Guard\Reporting\Reporter
 */
#[CoversClass(PolicyRun::class)]
#[UsesClass(\Guard\Collect\Filesystem\Discovery::class)]
#[UsesClass(\Guard\Collect\Filesystem\Snapshot::class)]
#[UsesClass(\Guard\Collect\Filesystem\WalkQueue::class)]
#[UsesClass(\Guard\Config\Reader\ExtensionConfigReader::class)]
#[UsesClass(\Guard\Config\RuleReader::class)]
#[UsesClass(\Guard\Config\ComponentLoader::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(\Guard\Policy\FieldConstraints::class)]
#[UsesClass(\Guard\Collect\Collector::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Config\ConfigurationLoader::class)]
#[UsesClass(\Guard\Config\DocumentMerger::class)]
#[UsesClass(\Guard\Config\ImportResolver::class)]
#[UsesClass(\Guard\Config\Schema::class)]
#[UsesClass(\Guard\Structure\Document\DataDocument::class)]
#[UsesClass(\Guard\Repair\AtomicWriter::class)]
#[UsesClass(\Guard\Repair\ChangeSet::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(\Guard\Execution\Pipeline::class)]
#[UsesClass(Plan::class)]
#[UsesClass(Registry::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
#[UsesClass(\Guard\Reporting\Reporter::class)]
#[UsesClass(\Guard\Reporting\ChangeDiff::class)]
#[UsesClass(\Guard\Policy\Diagnostic\RuleMessages::class)]
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
        $registry->addPolicy('rewrite', new class ([], static fn (): Plan => new Plan([], [new FileChange($root . '/a.txt', 'old', 'new')])) implements \Guard\Policy\Policy {
            /** @param array<string, \Guard\Input\Input> $inputs
             * @param Closure(\Guard\Input\InputSet, \Guard\Policy\Context): Plan $callback
             */
            public function __construct(private array $inputs, private Closure $callback)
            {
            }
            public function inputs(\Guard\Policy\Context $context): array
            {
                return $this->inputs;
            }
            public function evaluate(\Guard\Input\InputSet $inputs, \Guard\Policy\Context $context): Plan
            {
                return ($this->callback)($inputs, $context);
            }
        });
        $output = new BufferedOutput();

        self::assertSame(0, (new PolicyRun($registry))->run($root . '/guard.yaml', 'text', true, false, $output));
        self::assertSame('new', file_get_contents($root . '/a.txt'));
        self::assertStringContainsString('changed: a.txt', $output->fetch());
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
        $registry->addPolicy('rewrite', new class ([], static fn (): Plan => new Plan([], [new FileChange($root . '/a.txt', 'old', 'new')])) implements \Guard\Policy\Policy {
            /** @param array<string, \Guard\Input\Input> $inputs
             * @param Closure(\Guard\Input\InputSet, \Guard\Policy\Context): Plan $callback
             */
            public function __construct(private array $inputs, private Closure $callback)
            {
            }
            public function inputs(\Guard\Policy\Context $context): array
            {
                return $this->inputs;
            }
            public function evaluate(\Guard\Input\InputSet $inputs, \Guard\Policy\Context $context): Plan
            {
                return ($this->callback)($inputs, $context);
            }
        });
        $output = new BufferedOutput();

        self::assertSame(0, (new PolicyRun($registry))->run($root . '/guard.yaml', 'text', true, true, $output));
        self::assertSame('old', file_get_contents($root . '/a.txt'));
        self::assertStringContainsString('would change: a.txt', $output->fetch());
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
        $registry->addPolicy('rewrite', new class ([], static fn (): Plan => new Plan([$finding], [new FileChange($root . '/a.txt', 'old', 'new')], [$finding])) implements \Guard\Policy\Policy {
            /** @param array<string, \Guard\Input\Input> $inputs
             * @param Closure(\Guard\Input\InputSet, \Guard\Policy\Context): Plan $callback
             */
            public function __construct(private array $inputs, private Closure $callback)
            {
            }
            public function inputs(\Guard\Policy\Context $context): array
            {
                return $this->inputs;
            }
            public function evaluate(\Guard\Input\InputSet $inputs, \Guard\Policy\Context $context): Plan
            {
                return ($this->callback)($inputs, $context);
            }
        });
        $output = new BufferedOutput();

        self::assertSame(1, (new PolicyRun($registry))->run($root . '/guard.yaml', 'text', true, false, $output));
        self::assertSame('old', file_get_contents($root . '/a.txt'));
        self::assertStringContainsString('blocked: a.txt', $output->fetch());
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
        $registry->addPolicy('tag', new class ([], static fn (): Plan => new Plan([$finding], [])) implements \Guard\Policy\Policy {
            /** @param array<string, \Guard\Input\Input> $inputs
             * @param Closure(\Guard\Input\InputSet, \Guard\Policy\Context): Plan $callback
             */
            public function __construct(private array $inputs, private Closure $callback)
            {
            }
            public function inputs(\Guard\Policy\Context $context): array
            {
                return $this->inputs;
            }
            public function evaluate(\Guard\Input\InputSet $inputs, \Guard\Policy\Context $context): Plan
            {
                return ($this->callback)($inputs, $context);
            }
        });
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
