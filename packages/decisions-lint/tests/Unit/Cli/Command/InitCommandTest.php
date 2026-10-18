<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

use DecisionsLint\Cli\Command\InitCommand;
use DecisionsLint\Cli\Command\LintCommand;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Provider\ProviderSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \DecisionsLint\Cli\Command\InitCommand
 * @uses \DecisionsLint\Cli\Command\LintCommand
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Provider\ProviderSettings
 */
#[CoversClass(InitCommand::class)]
#[UsesClass(LintCommand::class)]
#[UsesClass(LintException::class)]
#[UsesClass(ProviderSettings::class)]
final class InitCommandTest extends TestCase
{
    public function testExecuteWritesAConfigurationOnceAndRefusesToOverwrite(): void
    {
        $root = sys_get_temp_dir() . '/decisions-init-' . uniqid();
        mkdir($root . '/vendor/k-kinzal/decisions-lint/rules', 0777, true);
        file_put_contents($root . '/vendor/k-kinzal/decisions-lint/rules/cohesion.yaml', "version: 1\nrules: {}\n");
        try {
            $tester = new CommandTester(new InitCommand($root, $root . '/vendor/k-kinzal/decisions-lint/rules'));
            self::assertSame(0, $tester->execute(['--provider' => 'openai', '--path' => ['app', 'lib']]));
            self::assertStringContainsString('Wrote ' . $root . '/decisions.yaml. Export OPENAI_API_KEY', $tester->getDisplay());
            $written = (string) file_get_contents($root . '/decisions.yaml');
            self::assertStringContainsString("# yaml-language-server: \$schema=vendor/k-kinzal/decisions-lint/schema/decisions.schema.json\n", $written);
            self::assertStringContainsString("provider:\n  name: openai\n  model: gpt-6-luna\n  api_key_env: OPENAI_API_KEY\n", $written);
            self::assertStringContainsString("paths:\n  - app\n  - lib\n", $written);
            self::assertStringContainsString("imports:\n  - vendor/k-kinzal/decisions-lint/rules/cohesion.yaml\n", $written);
            self::assertSame(LintCommand::INVALID, $tester->execute([]));
            self::assertStringContainsString('already exists. Edit it, or pass --config to write another file.', $tester->getDisplay());
            self::assertSame(LintCommand::INVALID, $tester->execute(['--config' => 'other.yaml', '--provider' => 'anthropic']));
            self::assertStringContainsString('Unsupported --provider "anthropic". Use --provider=jev, openai, ollama, localai, openai-compatible.', $tester->getDisplay());
            self::assertSame(0, $tester->execute(['--config' => 'local.yaml', '--provider' => 'ollama']));
            self::assertStringContainsString('Make sure a ollama server answers at http://localhost:11434, review the imported rules', $tester->getDisplay());
            self::assertStringContainsString("provider:\n  name: ollama\n  model: llama3.1\n", (string) file_get_contents($root . '/local.yaml'));
        } finally {
            unlink($root . '/decisions.yaml');
            unlink($root . '/local.yaml');
            unlink($root . '/vendor/k-kinzal/decisions-lint/rules/cohesion.yaml');
            rmdir($root . '/vendor/k-kinzal/decisions-lint/rules');
            rmdir($root . '/vendor/k-kinzal/decisions-lint');
            rmdir($root . '/vendor/k-kinzal');
            rmdir($root . '/vendor');
            rmdir($root);
        }
    }

    public function testDocumentWritesTheStartingConfiguration(): void
    {
        $document = (new InitCommand('/project'))->document(ProviderSettings::defaults('jev'), ['src'], []);
        self::assertStringStartsWith("# yaml-language-server: \$schema=https://k-kinzal.github.io/php-ai-toolkit/decisions-lint/decisions.schema.json\n", $document);
        self::assertStringContainsString("version: 1\nprovider:\n  name: jev\n  model: jev-latest\n  api_key_env: TYPESAFE_API_KEY\npaths:\n  - src\nexclude: []\nfail_on: error\nimports: []\nrules: {}\n", $document);
    }

    public function testImportsListTheShippedRuleFilesRelativeToTheProject(): void
    {
        $imports = (new InitCommand('/project'))->imports(dirname(__DIR__, 4));
        self::assertSame(['rules/cohesion.yaml', 'rules/ddd.yaml', 'rules/naming.yaml'], $imports);
    }

    public function testSchemaPathSitsNextToTheImportedRules(): void
    {
        $command = new InitCommand('/project');
        self::assertSame('vendor/k-kinzal/decisions-lint/schema/decisions.schema.json', $command->schemaPath(['vendor/k-kinzal/decisions-lint/rules/cohesion.yaml']));
        self::assertSame('https://k-kinzal.github.io/php-ai-toolkit/decisions-lint/decisions.schema.json', $command->schemaPath([]));
    }

    public function testRelativeWalksUpAndDown(): void
    {
        $command = new InitCommand('/project');
        self::assertSame('../../lib/rules', $command->relative('/a/b/c', '/a/lib/rules'));
        self::assertSame('rules', $command->relative('/a', '/a/rules'));
    }
}
