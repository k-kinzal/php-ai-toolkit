<?php

declare(strict_types=1);

namespace Tests\Unit\Cli\Command;

use DecisionsLint\Cli\Command\LintCommand;
use DecisionsLint\Cli\Command\PlanCommand;
use DecisionsLint\Cli\FormatDetector;
use DecisionsLint\Config\Configuration;
use DecisionsLint\Config\ConfigurationLoader;
use DecisionsLint\Config\DocumentLoader;
use DecisionsLint\Config\DocumentMerger;
use DecisionsLint\Config\ImportResolver;
use DecisionsLint\Config\ProviderReader;
use DecisionsLint\Config\RuleReader;
use DecisionsLint\Config\SchemaCatalog;
use DecisionsLint\Config\SchemaValidator;
use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Lint\Assignment;
use DecisionsLint\Lint\Plan;
use DecisionsLint\Lint\Planner;
use DecisionsLint\Provider\ProviderSettings;
use DecisionsLint\Reporting\PlanReporter;
use DecisionsLint\Rule\DecisionSpec;
use DecisionsLint\Rule\QuestionBuilder;
use DecisionsLint\Rule\Rule;
use DecisionsLint\Rule\TargetSelector;
use DecisionsLint\Rule\Template;
use DecisionsLint\Rule\Violation;
use DecisionsLint\Source\Declaration;
use DecisionsLint\Source\DeclarationScanner;
use DecisionsLint\Source\FileFinder;
use DecisionsLint\Source\GlobMatcher;
use DecisionsLint\Source\Target;
use DecisionsLint\Source\TargetExtractor;
use DecisionsLint\Source\Tokens;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \DecisionsLint\Cli\Command\PlanCommand
 * @uses \DecisionsLint\Cli\Command\LintCommand
 * @uses \DecisionsLint\Cli\FormatDetector
 * @uses \DecisionsLint\Config\Configuration
 * @uses \DecisionsLint\Config\ConfigurationLoader
 * @uses \DecisionsLint\Config\DocumentLoader
 * @uses \DecisionsLint\Config\DocumentMerger
 * @uses \DecisionsLint\Config\ImportResolver
 * @uses \DecisionsLint\Config\ProviderReader
 * @uses \DecisionsLint\Config\RuleReader
 * @uses \DecisionsLint\Config\SchemaCatalog
 * @uses \DecisionsLint\Config\SchemaValidator
 * @uses \DecisionsLint\Decision\Inquiry
 * @uses \DecisionsLint\Decision\Question
 * @uses \DecisionsLint\Diagnostic\LintException
 * @uses \DecisionsLint\Lint\Assignment
 * @uses \DecisionsLint\Lint\Plan
 * @uses \DecisionsLint\Lint\Planner
 * @uses \DecisionsLint\Provider\ProviderSettings
 * @uses \DecisionsLint\Reporting\PlanReporter
 * @uses \DecisionsLint\Rule\DecisionSpec
 * @uses \DecisionsLint\Rule\QuestionBuilder
 * @uses \DecisionsLint\Rule\Rule
 * @uses \DecisionsLint\Rule\TargetSelector
 * @uses \DecisionsLint\Rule\Template
 * @uses \DecisionsLint\Rule\Violation
 * @uses \DecisionsLint\Source\Declaration
 * @uses \DecisionsLint\Source\DeclarationScanner
 * @uses \DecisionsLint\Source\FileFinder
 * @uses \DecisionsLint\Source\GlobMatcher
 * @uses \DecisionsLint\Source\Target
 * @uses \DecisionsLint\Source\TargetExtractor
 * @uses \DecisionsLint\Source\Tokens
 */
#[CoversClass(PlanCommand::class)]
#[UsesClass(LintCommand::class)]
#[UsesClass(FormatDetector::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationLoader::class)]
#[UsesClass(DocumentLoader::class)]
#[UsesClass(DocumentMerger::class)]
#[UsesClass(ImportResolver::class)]
#[UsesClass(ProviderReader::class)]
#[UsesClass(RuleReader::class)]
#[UsesClass(SchemaCatalog::class)]
#[UsesClass(SchemaValidator::class)]
#[UsesClass(Inquiry::class)]
#[UsesClass(Question::class)]
#[UsesClass(LintException::class)]
#[UsesClass(Assignment::class)]
#[UsesClass(Plan::class)]
#[UsesClass(Planner::class)]
#[UsesClass(ProviderSettings::class)]
#[UsesClass(PlanReporter::class)]
#[UsesClass(DecisionSpec::class)]
#[UsesClass(QuestionBuilder::class)]
#[UsesClass(Rule::class)]
#[UsesClass(TargetSelector::class)]
#[UsesClass(Template::class)]
#[UsesClass(Violation::class)]
#[UsesClass(Declaration::class)]
#[UsesClass(DeclarationScanner::class)]
#[UsesClass(FileFinder::class)]
#[UsesClass(GlobMatcher::class)]
#[UsesClass(Target::class)]
#[UsesClass(TargetExtractor::class)]
#[UsesClass(Tokens::class)]
final class PlanCommandTest extends TestCase
{
    public function testExecutePrintsThePlanWithoutContactingTheProvider(): void
    {
        $root = sys_get_temp_dir() . '/decisions-plan-' . uniqid();
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/A.php', "<?php\nclass A {\n    public function f(): void {}\n}\n");
        file_put_contents($root . '/decisions.yaml', "version: 1\nrules:\n  r:\n    target: {kind: method}\n    decision: {type: predicate, instructions: 'Q?'}\n    violations: [{answer: false, message: m, fix: f}]\n");
        try {
            $tester = new CommandTester(new PlanCommand($root));
            self::assertSame(0, $tester->execute(['--format' => 'text']));
            self::assertSame("src/A.php:3:5 method A::f (class) <- r\n1 files, 1 targets, 1 requests, 1 questions. Nothing was sent.\n", $tester->getDisplay());
            self::assertSame(LintCommand::INVALID, $tester->execute(['--rule' => ['missing']]));
            self::assertStringContainsString('Decisions error: Unknown rule "missing".', $tester->getDisplay());
        } finally {
            unlink($root . '/src/A.php');
            unlink($root . '/decisions.yaml');
            rmdir($root . '/src');
            rmdir($root);
        }
    }
}
