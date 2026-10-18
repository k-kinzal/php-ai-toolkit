<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

use DecisionsLint\Cli\Command\LintCommand;
use DecisionsLint\Cli\Command\ValidateCommand;
use DecisionsLint\Config\Configuration;
use DecisionsLint\Config\ConfigurationLoader;
use DecisionsLint\Config\DocumentLoader;
use DecisionsLint\Config\DocumentMerger;
use DecisionsLint\Config\ImportResolver;
use DecisionsLint\Config\ProviderReader;
use DecisionsLint\Config\RuleReader;
use DecisionsLint\Config\SchemaCatalog;
use DecisionsLint\Config\SchemaValidator;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Provider\ProviderSettings;
use DecisionsLint\Rule\DecisionSpec;
use DecisionsLint\Rule\Rule;
use DecisionsLint\Rule\TargetSelector;
use DecisionsLint\Rule\Template;
use DecisionsLint\Rule\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \DecisionsLint\Cli\Command\ValidateCommand
 * @uses \DecisionsLint\Cli\Command\LintCommand
 * @uses \DecisionsLint\Config\Configuration
 * @uses \DecisionsLint\Config\ConfigurationLoader
 * @uses \DecisionsLint\Config\DocumentLoader
 * @uses \DecisionsLint\Config\DocumentMerger
 * @uses \DecisionsLint\Config\ImportResolver
 * @uses \DecisionsLint\Config\ProviderReader
 * @uses \DecisionsLint\Config\RuleReader
 * @uses \DecisionsLint\Config\SchemaCatalog
 * @uses \DecisionsLint\Config\SchemaValidator
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Provider\ProviderSettings
 * @uses \DecisionsLint\Rule\DecisionSpec
 * @uses \DecisionsLint\Rule\Rule
 * @uses \DecisionsLint\Rule\TargetSelector
 * @uses \DecisionsLint\Rule\Template
 * @uses \DecisionsLint\Rule\Violation
 */
#[CoversClass(ValidateCommand::class)]
#[UsesClass(LintCommand::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationLoader::class)]
#[UsesClass(DocumentLoader::class)]
#[UsesClass(DocumentMerger::class)]
#[UsesClass(ImportResolver::class)]
#[UsesClass(ProviderReader::class)]
#[UsesClass(RuleReader::class)]
#[UsesClass(SchemaCatalog::class)]
#[UsesClass(SchemaValidator::class)]
#[UsesClass(LintException::class)]
#[UsesClass(ProviderSettings::class)]
#[UsesClass(DecisionSpec::class)]
#[UsesClass(Rule::class)]
#[UsesClass(TargetSelector::class)]
#[UsesClass(Template::class)]
#[UsesClass(Violation::class)]
final class ValidateCommandTest extends TestCase
{
    public function testExecuteReportsEachValidDocumentAndStopsAtTheFirstInvalidOne(): void
    {
        $root = sys_get_temp_dir() . '/decisions-validate-' . uniqid();
        mkdir($root . '/rules', 0777, true);
        file_put_contents($root . '/rules/good.yaml', "version: 1\nrules:\n  g.one:\n    target: {kind: method}\n    decision: {type: predicate, instructions: 'Q?'}\n    violations: [{answer: false, message: m, fix: f}]\n");
        file_put_contents($root . '/rules/bad.yaml', "version: 1\nrules:\n  b.one:\n    target: {kind: method}\n    decision: {type: choice, instructions: 'Pick.', choices: {a: A, b: B}}\n    violations: [{answer: c, message: m, fix: f}]\n");
        file_put_contents($root . '/decisions.yaml', "version: 1\nimports: [rules/good.yaml]\nrules:\n  g.one: {enabled: false}\n");
        try {
            $tester = new CommandTester(new ValidateCommand($root));
            self::assertSame(0, $tester->execute(['files' => ['rules/good.yaml']]));
            self::assertSame("decisions.yaml is valid: 1 rule, 0 enabled, provider jev (jev-latest).\nrules/good.yaml is valid: 1 rule.\n", $tester->getDisplay());
            self::assertSame(LintCommand::INVALID, $tester->execute(['files' => ['rules/bad.yaml']]));
            self::assertStringContainsString('Decisions error: Rule "b.one" (rules/bad.yaml).violations[0]: answer must name one of the decision.choices (a, b).', $tester->getDisplay());
        } finally {
            unlink($root . '/rules/good.yaml');
            unlink($root . '/rules/bad.yaml');
            unlink($root . '/decisions.yaml');
            rmdir($root . '/rules');
            rmdir($root);
        }
    }

    public function testFilesReadsTheArguments(): void
    {
        $command = new ValidateCommand('/project');
        self::assertSame(['a.yaml', 'b.yaml'], $command->files(new ArrayInput(['files' => ['a.yaml', ' b.yaml ', '']], $command->getDefinition())));
        self::assertSame([], $command->files(new ArrayInput([], $command->getDefinition())));
    }

    public function testMappingCastsKeysToStrings(): void
    {
        self::assertSame(['1' => 'a', 'b' => 'c'], (new ValidateCommand('/project'))->mapping([1 => 'a', 'b' => 'c']));
    }
}
