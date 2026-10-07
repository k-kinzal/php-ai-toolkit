<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

use Guard\Cli\Command\GuardCommand;
use Guard\Cli\Command\InitCommand;
use Guard\Init\Initializer;
use Guard\Init\PresetCatalog;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Tester\CommandCompletionTester;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Guard\Cli\Command\InitCommand
 * @uses \Guard\Cli\Command\GuardCommand
 * @uses \Guard\Init\Initializer
 * @uses \Guard\Init\PresetCatalog
 * @uses \Guard\Policy\PolicyException
 */
#[CoversClass(InitCommand::class)]
#[UsesClass(GuardCommand::class)]
#[UsesClass(Initializer::class)]
#[UsesClass(PresetCatalog::class)]
#[UsesClass(PolicyException::class)]
final class InitCommandTest extends TestCase
{
    public function testConfigureListsThePresetsInTheHelp(): void
    {
        $command = new InitCommand('/project');

        self::assertSame('init', $command->getName());
        self::assertTrue($command->getDefinition()->getOption('import')->isArray());
        self::assertFalse($command->getDefinition()->hasOption('format'));
        self::assertStringContainsString('Presets: ' . implode(', ', (new PresetCatalog())->names()), $command->getHelp());
    }

    public function testCompleteSuggestsThePresetNames(): void
    {
        self::assertSame((new PresetCatalog())->names(), (new CommandCompletionTester(new InitCommand('/project')))->complete(['--import=']));
    }

    public function testImportsAddsUpCommaSeparatedAndRepeatedNames(): void
    {
        $command = new InitCommand('/project');

        self::assertSame(['metrics', 'structure', 'composer'], $command->imports(new ArgvInput(['guard', '--import=metrics,structure', '--import', 'composer'], $command->getDefinition())));
        self::assertNull($command->imports(new ArgvInput(['guard'], $command->getDefinition())));
    }

    public function testImportsRejectsAnEmptyName(): void
    {
        $command = new InitCommand('/project');

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Provide one or more preset names for --import, separated by commas.');

        $command->imports(new ArgvInput(['guard', '--import=metrics,'], $command->getDefinition()));
    }

    public function testExecuteRefusesToOverwriteAPolicyFile(): void
    {
        $root = sys_get_temp_dir() . '/guard-init-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\n");
        $tester = new CommandTester(new InitCommand($root));

        self::assertSame(2, $tester->execute([], ['capture_stderr_separately' => true]));
        self::assertSame("version: 1\n", file_get_contents($root . '/guard.yaml'));
        self::assertStringStartsWith('Guard error: Cannot create ' . $root . '/guard.yaml: it already exists.', $tester->getErrorOutput());
    }
}
